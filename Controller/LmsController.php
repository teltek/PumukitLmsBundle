<?php

namespace Pumukit\LmsBundle\Controller;

use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\EncoderBundle\Document\Job;

/**
 * @Route("/openedx")
 */
class LmsController extends SSOController
{
    /**
     * @var array
     */
    private $templateErrors = [
        400 => 'PumukitLmsBundle:Lms:400job.html.twig',
        403 => 'PumukitLmsBundle:Lms:403forbidden.html.twig',
        404 => 'PumukitLmsBundle:Lms:404notfound.html.twig',
    ];

    /**
     * @Route("/embed", name="pumukit_lms_openedx_embed")
     * @Route("/embed/", name="pumukit_lms_openedx_embed")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function iframeAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        $options = $this->getOptionsParameters($request);
        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy([
            '_id' => $options['id'],
        ]);

        $user = $this->getUser();
        if (!$multimediaObject || !$user || (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$multimediaObjectService->isUserOwner($user, $multimediaObject))) {
            $validateAccess = $this->validateAccess($request, $options);
            if ($validateAccess instanceof Response) {
                return $validateAccess;
            }
        }
        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');
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
            if ($multimediaObject->containsAnyTagWithCodes(['PUCHWEBTV', 'PUCHLMS'])) {
                if (!$job || ($job && Job::STATUS_FINISHED !== $job->getStatus())) {
                    $options['job'] = $job;

                    return $this->renderTemplateError(Response::HTTP_BAD_REQUEST, $options);
                } else {
                    return $this->renderIframe($multimediaObject, $request);
                }
            } else {
                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
        }

        return $this->renderTemplateError(Response::HTTP_NOT_FOUND, $options);
    }

    /**
     * @Route("/playlist/embed", name="pumukit_lms_openedx_playlist_embed")
     * @Route("/playlist/embed/", name="pumukit_lms_openedx_playlist_embed")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function iFramePlaylistAction(Request $request)
    {
        $options = $this->getOptionsParameters($request);
        $validateAccess = $this->validateAccess($request, $options);
        if ($validateAccess instanceof Response) {
            return $validateAccess;
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $series = $dm->getRepository('PumukitSchemaBundle:Series')->findOneBy([
            '_id' => $options['id'],
        ]);

        return $this->renderPlaylistIframe($series, $request);
    }

    /**
     * Render iframe.
     *
     * @param MultimediaObject $multimediaObject
     * @param Request          $request
     *
     * @return Response
     */
    protected function renderIframe(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward(
            'PumukitBasePlayerBundle:BasePlayer:index',
            [
                'request' => $request,
                'multimediaObject' => $multimediaObject,
            ]
        );
    }

    /**
     * @param Series  $series
     * @param Request $request
     *
     * @return Response
     */
    protected function renderPlaylistIframe(Series $series, Request $request)
    {
        return $this->redirectToRoute(
            'pumukit_playlistplayer_index',
            [
                'request' => $request,
                'id' => $series->getId(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getOptionsParameters(Request $request)
    {
        $lmsService = $this->container->get('pumukit_lms.lms');
        $options = [
            'current_locale' => $lmsService->getCurrentLocale($request),
            'email' => $this->getParameter('pumukit2.info')['email'],
            'id' => $request->get('id'),
        ];

        return $options;
    }

    /**
     * @param Request $request
     * @param array   $options
     *
     * @return bool|Response
     */
    private function validateAccess(Request $request, array $options)
    {
        // NOTE: Check TTK-16603
        $lmsService = $this->container->get('pumukit_lms.lms');
        if ('dev' != $this->get('kernel')->getEnvironment()) {
            $refererUrl = $request->headers->get('referer');
            if (!$lmsService->validateAccessDomain($refererUrl)) {
                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
            $refererUrl = parse_url($refererUrl, PHP_URL_HOST);
            if (!$lmsService->validateAccessDomain($refererUrl)) {
                return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
            }
        }
        $ssoService = $this->container->get('pumukit_lms.sso');
        if (!$ssoService->validateHash($request->get('hash'), '')) {
            return $this->renderTemplateError(Response::HTTP_FORBIDDEN, $options);
        }

        return true;
    }

    /**
     * @param       $statusCode
     * @param array $options
     *
     * @return Response
     */
    private function renderTemplateError($statusCode, array $options)
    {
        $template = $this->templateErrors[$statusCode];

        return new Response($this->renderView($template, $options), $statusCode);
    }
}
