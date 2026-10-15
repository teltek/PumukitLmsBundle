<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Services\UploadDispatcherService;
use Pumukit\CoreBundle\Utils\BlackListExtensions;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\SchemaBundle\Document\User;
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
    private $documentManager;
    private $logger;
    private $ssoService;
    private $inboxService;
    private $uploadDispatcherService;
    private $configurationService;
    private $secret;

    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, SSOService $ssoService, InboxService $inboxService, UploadDispatcherService $uploadDispatcherService, ConfigurationService $configurationService, string $secret)
    {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->ssoService = $ssoService;
        $this->inboxService = $inboxService;
        $this->uploadDispatcherService = $uploadDispatcherService;
        $this->configurationService = $configurationService;
        $this->secret = $secret;
    }

    /**
     * @Route("/tus", name="moodle_tus_post")
     * @Route("/tus/lti/{userId}/{expires}/{signature}", name="moodle_tus_lti", methods={"POST"}, priority=10, requirements={"userId"="[a-fA-F0-9]+", "expires"="\d+", "signature"="[a-fA-F0-9]{64}"})
     * @Route("/tus/{token}", name="moodle_tus_post_token", requirements={"token"=".+"}, priority=-10)
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
            $isLtiUpload = null !== $request->attributes->get('signature');
            $seriesPath = null;

            if ('' !== $token && !preg_match('/^[a-zA-Z0-9-]+$/', $token)) {
                return new Response('Invalid upload token', 400);
            }

            if ('' !== $token && !$isLtiUpload) {
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

                if (!$series) {
                    return new Response('Missing series', 400);
                }

                if ($isLtiUpload) {
                    $user = $this->validateLtiUpload(
                        (string) $request->attributes->get('userId'),
                        (string) $request->attributes->get('expires'),
                        (string) $request->attributes->get('signature')
                    );

                    if (!$user) {
                        return new Response('Unauthorized LTI upload', 401);
                    }
                } else {
                    // Moodle tradicional
                    if (!$username && !$email) {
                        return new Response('Missing username and email', 400);
                    }

                    if (!$hash) {
                        return new Response('Missing authentication hash', 401);
                    }

                    $user = $this->ssoService->getAndValidateUser(
                        (string) $email,
                        (string) $username,
                        $request->headers->get('referer'),
                        (string) $hash,
                        $request->isSecure()
                    );
                }

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

                $filename = null;
                $metadata = $request->headers->get('Upload-Metadata');

                if (!$metadata || !preg_match('/(?:^|,)filename (?P<name>[^,]+)/', $metadata, $matches)) {
                    return new Response('Missing filename', 400);
                }

                $filename = base64_decode($matches['name'], true);

                if (!is_string($filename)
                    || '' === $filename
                    || '.' === $filename
                    || '..' === $filename
                    || false !== strpos($filename, '..')
                    || false !== strpbrk($filename, '/\\')
                    || !preg_match('/^[a-zA-Z0-9._ -]+$/', $filename)) {
                    return new Response('Invalid filename', 400);
                }

                $targetFile = $seriesPath.DIRECTORY_SEPARATOR.$filename;

                if (file_exists($targetFile)) {
                    return new Response('File already exists', 409);
                }
            }

            if ($seriesPath) {
                $server->setUploadDir($seriesPath);
            }

            $response = $server->serve();

            if (!$token && 201 === $response->getStatusCode()) {
                $location = $response->headers->get('Location');
                $uuid = $location ? basename($location) : null;

                if ($uuid && preg_match('/^[a-zA-Z0-9-]+$/', $uuid)) {
                    $data = [
                        'series' => $cleanSeries,
                        'userId' => (string) $user->getId(),
                        'authType' => $isLtiUpload ? 'lti' : 'sso',
                        'fileName' => $filename,
                        'uploadLength' => (int) $request->headers->get('Upload-Length'),
                    ];

                    file_put_contents(
                        $mapPath.DIRECTORY_SEPARATOR.$uuid.'.json',
                        json_encode($data),
                        LOCK_EX
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
     * @Route("/dispatchImport", name="moodle_dispatch_import", methods={"POST"})
     */
    public function moodleDispatchImport(Request $request): JsonResponse
    {
        $uuid = (string) $request->request->get('uuid', '');

        if (!preg_match('/^[a-zA-Z0-9-]+$/', $uuid)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid uuid'], 400);
        }

        try {
            $basePath = realpath($this->inboxService->inboxPath());

            if (!$basePath) {
                return new JsonResponse(['success' => false, 'error' => 'Upload directory unavailable'], 500);
            }

            $mapFile = $basePath.DIRECTORY_SEPARATOR.'_moodle_map'
                .DIRECTORY_SEPARATOR.$uuid.'.json';

            if (!is_file($mapFile)) {
                return new JsonResponse(['success' => false, 'error' => 'Upload not found'], 404);
            }

            $data = json_decode((string) file_get_contents($mapFile), true);

            if (!is_array($data) || empty($data['series']) || empty($data['userId'])) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid mapping'], 400);
            }

            $userId = (string) $data['userId'];
            $authType = $data['authType'] ?? null;

            if ('lti' === $authType) {
                if (!$request->hasPreviousSession()) {
                    return new JsonResponse(['success' => false, 'error' => 'LTI session required'], 401);
                }

                $sessionUserId = (string) $request->getSession()->get('lti_upload_user_id', '');

                if ('' === $sessionUserId || !hash_equals($userId, $sessionUserId)) {
                    return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
                }

                $user = $this->documentManager->getRepository(User::class)->find($userId);
            } elseif ('sso' === $authType) {
                $username = (string) $request->request->get('username', '');
                $email = (string) $request->request->get('email', '');
                $hash = (string) $request->request->get('hash', '');

                if ((!$username && !$email) || !$hash) {
                    return new JsonResponse(['success' => false, 'error' => 'Missing authentication'], 401);
                }

                $user = $this->ssoService->getAndValidateUser(
                    $email,
                    $username,
                    $request->headers->get('referer'),
                    $hash,
                    $request->isSecure()
                );

                if (!$user || $user instanceof Response || (string) $user->getId() !== $userId) {
                    return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
                }
            } else {
                return new JsonResponse(['success' => false, 'error' => 'Invalid authentication type'], 401);
            }

            if (!$user) {
                return new JsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $fileName = $request->request->get('fileName');

            if (!is_string($fileName) || '' === $fileName
                || false !== strpos($fileName, '..')
                || false !== strpbrk($fileName, '/\\')) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid file name'], 400);
            }

            if (!isset($data['fileName'], $data['uploadLength'])
                || $fileName !== $data['fileName']) {
                return new JsonResponse(['success' => false, 'error' => 'File does not match upload'], 400);
            }

            $filePath = $basePath.DIRECTORY_SEPARATOR.$data['series']
                .DIRECTORY_SEPARATOR.$fileName;

            if (!is_file($filePath)
                || filesize($filePath) !== (int) $data['uploadLength']) {
                return new JsonResponse(['success' => false, 'error' => 'Upload is incomplete'], 409);
            }

            $this->uploadDispatcherService->dispatchUploadFromInbox(
                $user,
                $fileName,
                $data['series'],
                $request->request->get('profile', 'master_copy')
            );

            unlink($mapFile);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->logger->error('Moodle dispatch import: '.$e->getMessage());

            return new JsonResponse(['success' => false, 'error' => 'Import failed'], 400);
        }
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

    private function validateLtiUpload(string $userId, string $expires, string $signature): ?User
    {
        $expiration = (int) $expires;

        if ($expiration <= time() || $expiration > time() + 3600) {
            return null;
        }

        $payload = 'lti_upload:'.$userId.':'.$expires;

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $this->documentManager->getRepository(User::class)->find($userId);
    }
}
