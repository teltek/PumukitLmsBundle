<?php

namespace Pumukit\LmsBundle\Controller;

use Pumukit\EncoderBundle\Document\Job;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/openedx")
 */
class LmsController extends SSOController
{
    private $templateErrors = [
        400 => 'PumukitLmsBundle:Lms:400job.html.twig',
        403 => 'PumukitLmsBundle:Lms:403forbidden.html.twig',
        404 => 'PumukitLmsBundle:Lms:404notfound.html.twig',
    ];

    /**
     * @Route("/embed", name="pumukit_lms_openedx_embed")
     * @Route("/embed/", name="pumukit_lms_openedx_embed")
     * @Route("/embed/{id}", name="pumukit_lms_openedx_embed_3")
     */
    public function iframeAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        $options = $this->getOptionsParameters($request);
        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneBy([
            '_id' => $options['id'],
        ]);

        $user = $this->getUser();
        if (!$multimediaObject || !$user || (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$multimediaObjectService->isUserOwner($user, $multimediaObject))) {
            $validateAccess = $this->validateAccess($request, $options);
            if ($validateAccess instanceof Response) {
                return $validateAccess;
            }
        }
        $jobRepo = $dm->getRepository(Job::class);
        $profileService = $this->get('pumukitencoder.profile');
        $displayProfiles = $profileService->getProfiles(true);
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
        $job = $jobRepo->findOneBy(['mm_id' => $options['id'], 'profile' => ['$in' => $profileNames]]);
        if ($multimediaObject && $multimediaObject->isPublished()) {
            if ($multimediaObject->containsAnyTagWithCodes(['PUCHWEBTV', PumukitLmsBundle::LMS_TAG_CODE])) {
                if ((!$multimediaObjectService->hasPlayableResource($multimediaObject)) || ($job && Job::STATUS_FINISHED !== $job->getStatus())) {
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
     * @Route("/playlist/embed/{id}", name="pumukit_lms_openedx_playlist_embed_3")
     */
    public function iFramePlaylistAction(Request $request)
    {
        $options = $this->getOptionsParameters($request);
        $validateAccess = $this->validateAccess($request, $options);
        if ($validateAccess instanceof Response) {
            return $validateAccess;
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $series = $dm->getRepository(Series::class)->findOneBy([
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
        $lmsService = $this->container->get('pumukit_lms.lms');

        return [
            'current_locale' => $lmsService->getCurrentLocale($request),
            'email' => $this->getParameter('pumukit.info')['email'],
            'id' => $request->get('id'),
        ];
    }

    private function validateAccess(Request $request, array $options)
    {
        // NOTE: Check TTK-16603
        $lmsService = $this->container->get('pumukit_lms.lms');
        if ('dev' != $this->get('kernel')->getEnvironment()) {
            $referer = $request->headers->get('referer');
            if (!$referer) {
                $options['error_message'] = 'Referer is null';

                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
            if (!$lmsService->validateAccessDomain($referer)) {
                $options['error_message'] = 'Referer is not a valid access domain';

                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
        }
        $ssoService = $this->container->get('pumukit_lms.sso');
        if (!$ssoService->validateHash($request->get('hash'), '')) {
            $options['error_message'] = 'Hash not valid';

            return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
        }

        return true;
    }

    private function renderTemplateError($statusCode, array $options)
    {
        $template = $this->templateErrors[$statusCode];

        return new Response($this->renderView($template, $options), $statusCode);
    }
}
