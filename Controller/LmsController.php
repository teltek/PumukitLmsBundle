<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/openedx")
 */
class LmsController extends AbstractController
{
    private $templateErrors = [
        400 => '@PumukitLmsBundle/Lms/400job.html.twig',
        403 => '@PumukitLmsBundle/Lms/403forbidden.html.twig',
        404 => '@PumukitLmsBundle/Lms/404notfound.html.twig',
    ];

    private $documentManager;
    private $multimediaObjectService;
    private $profileService;
    private $configurationService;
    private $pumukitInfo;

    public function __construct(
        DocumentManager $documentManager,
        MultimediaObjectService $multimediaObjectService,
        ProfileService $profileService,
        ConfigurationService $configurationService,
        array $pumukitInfo
    ) {
        $this->documentManager = $documentManager;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->profileService = $profileService;
        $this->configurationService = $configurationService;
        $this->pumukitInfo = $pumukitInfo;
    }

    /**
     * @Route("/embed", name="pumukit_lms_openedx_embed")
     * @Route("/embed/", name="pumukit_lms_openedx_embed")
     */
    public function iframeAction(Request $request): Response
    {
        $options = $this->getOptionsParameters($request);
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => $options['id'],
        ]);

        $user = $this->getUser();
        if (!$multimediaObject || !$user || (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->multimediaObjectService->isUserOwner($user, $multimediaObject))) {
            $validateAccess = $this->validateAccess($request, $options);
            if ($validateAccess instanceof Response) {
                return $validateAccess;
            }
        }

        $displayProfiles = $this->profileService->getProfiles(true);
        $profileNames = array_keys($displayProfiles);
        // Review no wait for recode_webm_screen and recode_webm_camera.
        $key = array_search('recode_webm_screen', $profileNames, true);
        if (false !== $key) {
            unset($profileNames[$key]);
        }
        $key = array_search('recode_webm_camera', $profileNames, true);
        if (false !== $key) {
            unset($profileNames[$key]);
        }
        if ($profileNames) {
            $profileNames = array_values($profileNames);
        }
        $job = $this->documentManager->getRepository(Job::class)->findOneBy([
            'mm_id' => $options['id'],
            'profile' => ['$in' => $profileNames],
        ]);
        if ($multimediaObject && $multimediaObject->isPublished()) {
            if ($multimediaObject->containsAnyTagWithCodes(['PUCHWEBTV', 'PUCHLMS'])) {
                if ((!$this->multimediaObjectService->hasPlayableResource($multimediaObject)) || ($job && Job::STATUS_FINISHED !== $job->getStatus())) {
                    $options['job'] = $job;

                    return $this->renderTemplateError(Response::HTTP_BAD_REQUEST, $options);
                }

                return $this->renderIframe($request, $multimediaObject);
            }

            return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
        }

        return $this->renderTemplateError(Response::HTTP_NOT_FOUND, $options);
    }

    /**
     * @Route("/playlist/embed", name="pumukit_lms_openedx_playlist_embed")
     * @Route("/playlist/embed/", name="pumukit_lms_openedx_playlist_embed")
     */
    public function iFramePlaylistAction(Request $request): Response
    {
        $options = $this->getOptionsParameters($request);
        $validateAccess = $this->validateAccess($request, $options);
        if ($validateAccess instanceof Response) {
            return $validateAccess;
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => $options['id'],
        ]);

        return $this->renderPlaylistIframe($request, $series);
    }

    protected function renderIframe(Request $request, MultimediaObject $multimediaObject)
    {
        $playerController = $this->get('pumukit_baseplayer.player_service')->getPublicControllerPlayer($multimediaObject);

        return $this->forward($playerController, ['request' => $request, 'multimediaObject' => $multimediaObject]);
    }

    protected function renderPlaylistIframe(Request $request, Series $series)
    {
        return $this->redirectToRoute(
            'pumukit_playlistplayer_index',
            [
                'request' => $request,
                'id' => $series->getId(),
            ]
        );
    }

    private function getOptionsParameters(Request $request): array
    {
        return [
            'email' => $this->pumukitInfo['email'],
            'id' => $request->get('id'),
        ];
    }

    private function validateAccess(Request $request, array $options)
    {
        // NOTE: Check TTK-16603
        if ('dev' !== getenv('APP_ENV')) {
            $referer = $request->headers->get('referer');
            if (!$referer) {
                $options['error_message'] = 'Referer is null';

                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
            if (!$this->configurationService->isAllowedDomain($referer)) {
                $options['error_message'] = 'Referer is not a valid access domain';

                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
        }

        if (!$this->configurationService->isValidHash($request->get('hash'), '')) {
            $options['error_message'] = 'Hash not valid';

            return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
        }

        return true;
    }

    private function renderTemplateError($statusCode, array $options): Response
    {
        $template = $this->templateErrors[$statusCode];

        return new Response($this->renderView($template, $options), $statusCode);
    }
}
