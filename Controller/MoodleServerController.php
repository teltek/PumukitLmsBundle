<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\CoreBundle\Utils\BlackListExtensions;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\SSOService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Middleware\Cors;
use TusPhp\Tus\Server as TusServer;

/**
 * @Route("/moodle")
 */
class MoodleServerController extends AbstractController
{
    private $logger;
    private $ssoService;
    private $inboxService;
    private $uploadDispatcherService;
    private $configurationService;

    public function __construct(LoggerInterface $logger, SSOService $ssoService, InboxService $inboxService, UploadDispatcherService $uploadDispatcherService, ConfigurationService $configurationService)
    {
        $this->logger = $logger;
        $this->ssoService = $ssoService;
        $this->inboxService = $inboxService;
        $this->uploadDispatcherService = $uploadDispatcherService;
        $this->configurationService = $configurationService;
    }

    /**
     * @Route("/tus", name="moodle_tus_post")
     * @Route("/tus/{token}", name="moodle_tus_post_token", requirements={"token"=".+"})
     */
    public function moodleServer(Request $request): Response
    {
        try {
            $method = strtoupper($request->getMethod());
            if ('DELETE' === $method) {
                return new Response('File deletion not allowed', 403);
            }

            $basePath = realpath($this->inboxService->inboxPath());
            if (!$basePath) {
                return new Response('Base upload directory does not exist', 500);
            }

            $mapPath = $basePath.DIRECTORY_SEPARATOR.'_moodle_map';
            if (!file_exists($mapPath)) {
                mkdir($mapPath, 0755, true);
            }

            $server = new TusServer();
            $server->setApiPath('/moodle/tus');
            $server->middleware()->skip(Cors::class);

            if ('OPTIONS' === $method) {
                return $server->serve();
            }

            $token = (string) $request->attributes->get('token');
            $seriesPath = null;

            if ('' !== $token) {
                $mapFile = $mapPath.DIRECTORY_SEPARATOR.$token.'.json';
                if (!file_exists($mapFile)) {
                    return new Response('Upload not found', 404);
                }

                $data = json_decode((string) file_get_contents($mapFile), true);
                $series = $data['series'] ?? null;
                if (!$series) {
                    return new Response('Invalid mapping', 400);
                }

                $seriesPath = $basePath.DIRECTORY_SEPARATOR.$series;
            } else {
                if ('POST' !== $method) {
                    return new Response('Method not allowed', 405);
                }

                $username = $request->query->get('username');
                $hash = $request->query->get('hash');
                $email = $request->query->get('email');
                $series = $request->get('series');

                if (!$username && !$email) {
                    return new Response('Missing username and email', 400);
                }
                if (!$series) {
                    return new Response('Missing series', 400);
                }

                if (!$hash) {
                    $hash = $this->configurationService->generateHashWithValue($email, $username);
                }

                $referer = $request->headers->get('referer');
                $user = $this->ssoService->getAndValidateUser(
                    (string) $email,
                    (string) $username,
                    $referer,
                    (string) $hash,
                    $request->isSecure()
                );

                if (!$user || $user instanceof Response) {
                    return new Response('Unauthorized', 401);
                }

                $this->validateFileExtension($request);

                $cleanSeries = $this->sanitizeSeries((string) $series);
                if ('' === $cleanSeries) {
                    return new Response('Invalid series', 400);
                }

                $seriesPath = $basePath.DIRECTORY_SEPARATOR.$cleanSeries;
                if (!file_exists($seriesPath)) {
                    mkdir($seriesPath, 0755, true);
                }

                $metadata = $request->headers->get('Upload-Metadata');
                if ($metadata && preg_match('/filename (?P<name>[^\s,]+)/', $metadata, $matches)) {
                    $decodedName = base64_decode($matches['name'], true);
                    if (false === $decodedName) {
                        return new Response('Invalid filename encoding', 400);
                    }

                    // Sanitize filename to prevent directory traversal and unsafe characters
                    $filename = basename($decodedName);
                    $filename = str_replace(['/', '\\'], '_', $filename);
                    $filename = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', $filename);
                    $filename = trim((string) $filename);

                    if ('' === $filename) {
                        return new Response('Invalid filename', 400);
                    }

                    $targetFile = $seriesPath.DIRECTORY_SEPARATOR.$filename;

                    if (file_exists($targetFile)) {
                        return new Response("A file with the name '{$filename}' already exists in this folder.", 409);
                    }
                }
            }

            if ($seriesPath) {
                $server->setUploadDir($seriesPath);
            }

            $response = $server->serve();

            if (!$token && 201 === $response->getStatusCode()) {
                $location = $response->headers->get('Location');
                $uuid = $location ? basename($location) : null;

                if ($uuid) {
                    $cleanSeries = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $series);
                    file_put_contents(
                        $mapPath.DIRECTORY_SEPARATOR.$uuid.'.json',
                        json_encode(['series' => $cleanSeries])
                    );
                }
            }

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('Moodle TUS - Exception: '.$e->getMessage());

            return new Response('Error: '.$e->getMessage(), 400);
        }
    }

    /**
     * @Route("/dispatchImport", name="moodle_dispatch_import")
     */
    public function moodleDispatchImport(Request $request): JsonResponse
    {
        $username = $request->get('username');
        $hash = $request->get('hash');
        $email = $request->get('email');
        $uuid = $request->get('uuid');

        if (!$username && !$email) {
            return new JsonResponse(['success' => false, 'error' => 'Missing username and email'], 400);
        }
        if (!$uuid) {
            return new JsonResponse(['success' => false, 'error' => 'Missing uuid'], 400);
        }

        if (!$hash) {
            $hash = $this->configurationService->generateHashWithValue($email, $username);
        }

        $user = $this->ssoService->getAndValidateUser(
            $email,
            $username,
            $request->headers->get('referer'),
            $hash,
            $request->isSecure()
        );

        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        try {
            $basePath = realpath($this->inboxService->inboxPath());
            $mapPath = $basePath.DIRECTORY_SEPARATOR.'_moodle_map';
            $mapFile = $mapPath.DIRECTORY_SEPARATOR.$uuid.'.json';

            if (!file_exists($mapFile)) {
                return new JsonResponse(['success' => false, 'error' => 'Upload not found'], 404);
            }

            $data = json_decode((string) file_get_contents($mapFile), true);
            $series = $data['series'] ?? null;

            if (!$series) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid mapping'], 400);
            }

            $fileName = $request->get('fileName');
            if (!is_string($fileName) || '' === $fileName) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid file name'], 400);
            }

            // Prevent directory traversal and enforce simple file names
            if (false !== strpos($fileName, '..') || false !== strpbrk($fileName, "/\\")) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid file name'], 400);
            }

            $fileName = basename($fileName);

            $this->uploadDispatcherService->dispatchUploadFromInbox(
                $user,
                $fileName,
                $series,
                $request->get('profile', 'master_copy')
            );

            unlink($mapFile);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }

        return new JsonResponse(['success' => true]);
    }

    private function validateFileExtension(Request $request): void
    {
        $metadata = $request->headers->get('Upload-Metadata');
        if (!$metadata) {
            throw new \Exception('Missing file metadata.');
        }

        $filename = '';
        $declaredMimeType = '';

        if (preg_match('/filename (?P<name>[^\s,]+)/', $metadata, $matches)) {
            $decodedName = base64_decode($matches['name'], true);
            if (false === $decodedName) {
                throw new \Exception('Invalid filename encoding.');
            }

            $filename = basename($decodedName);
            $filename = str_replace(['/', '\\'], '_', $filename);
            $filename = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', $filename);
            $filename = trim((string) $filename);
        }

        if (preg_match('/filetype (?P<type>[^\s,]+)/', $metadata, $matches)) {
            $declaredMimeType = base64_decode($matches['type']);
            if (false === $declaredMimeType) {
                throw new \Exception('Invalid MIME type encoding.');
            }

            $declaredMimeType = preg_replace('/[^a-zA-Z0-9\-\/\.\+]/', '', $declaredMimeType);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (BlackListExtensions::isBlackListed($extension)) {
            $this->logger->error("MOODLE TUS ERROR: Blocked malicious extension: {$extension} (File: {$filename})");

            throw new \Exception('File type strictly forbidden for security reasons.');
        }

        if (!MediaMimeTypeUtils::isAllowed($declaredMimeType, $extension)) {
            $this->logger->warning("MOODLE TUS ERROR: Mimetype not allowed: {$filename} ({$declaredMimeType})");

            throw new \Exception('File type not allowed by policy.');
        }
    }

    private function sanitizeSeries(string $series): string
    {
        $cleanSeries = preg_replace('/[^a-zA-Z0-9_\-]/', '', $series);

        return $cleanSeries ?? '';
    }
}
