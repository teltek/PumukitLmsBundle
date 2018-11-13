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
class OpenEdxController extends SSOController
{
    /**
     * @Route("/embed", name="pumukit_lms_openedx_embed")
     * @Route("/embed/", name="pumukit_lms_openedx_embed")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function iframeAction(Request $request)
    {
        $locale = $this->getLocale($request->get('lang'));
        $contactEmail = $this->getParameter('pumukit2.info')['email'];

        $listHosts = $this->container->getParameter('pumukit_lms.domains');

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $id = $request->get('id');
        $multimediaObject = $mmobjRepo->find($id);

        $user = $this->getUser();

        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        if (!$multimediaObject || !$user || (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$multimediaObjectService->isUserOwner($user, $multimediaObject))) {
            //Check TTK-16603
            if ('dev' != $this->get('kernel')->getEnvironment()) {
                $refererUrl = $request->headers->get('referer');
                if (!$refererUrl) {
                    return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
                }
                $refererUrl = parse_url($refererUrl, PHP_URL_HOST);

                if (!in_array($refererUrl, $listHosts)) {
                    return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
                }
            }

            $ssoService = $this->container->get('pumukit_lms.sso');
            if (!$ssoService->validateHash($request->get('hash'), '')) {
                return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
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

        $job = $jobRepo->findOneBy(array('mm_id' => $id, 'profile' => array('$in' => $profileNames)));

        if ($multimediaObject && $multimediaObject->isPublished()) {
            if ($multimediaObject->containsAnyTagWithCodes(['PUCHWEBTV', 'PUCHLMS'])) {
                if (!$job || ($job && Job::STATUS_FINISHED !== $job->getStatus())) {
                    return new Response($this->renderView('PumukitLmsBundle:OpenEdx:400job.html.twig', array('id' => $id, 'job' => $job, 'email' => $contactEmail, 'openedx_locale' => $locale)), 400);
                } else {
                    return $this->renderIframe($multimediaObject, $request);
                }
            } else {
                $response = new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);

                return $response;
            }
        }

        $response = new Response($this->renderView('PumukitLmsBundle:OpenEdx:404notfound.html.twig', array('id' => $id, 'openedx_locale' => $locale)), 404);

        return $response;
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
        $locale = $this->getLocale($request->get('lang'));
        $contactEmail = $this->getParameter('pumukit2.info')['email'];

        if ('dev' != $this->get('kernel')->getEnvironment()) {
            $refererUrl = $request->headers->get('referer');
            if (!$refererUrl) {
                return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
            }
            $refererUrl = parse_url($refererUrl, PHP_URL_HOST);

            $listHosts = $this->container->getParameter('pumukit_lms.domains');
            if (!in_array($refererUrl, $listHosts)) {
                return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
            }

            $ssoService = $this->container->get('pumukit_lms.sso');
            if (!$ssoService->validateHash($request->get('hash'), '')) {
                return new Response($this->renderView('PumukitLmsBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
            }
        }

        $id = $request->get('id');
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $series = $dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(array('_id' => new \MongoId($id)));

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
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
    }

    /**
     * @param Series  $series
     * @param Request $request
     *
     * @return Response
     */
    protected function renderPlaylistIframe(Series $series, Request $request)
    {
        return $this->forward(
            'PumukitBasePlayerBundle:BasePlaylist:index',
            array(
                'request' => $request,
                'series' => $series,
            )
        );
    }

    private function getLocale($queryLocale)
    {
        $locale = strtolower($queryLocale);
        $defaultLocale = $this->container->getParameter('locale');
        $pumukitLocales = $this->container->getParameter('pumukit2.locales');
        if ((!$locale) || (!in_array($locale, $pumukitLocales))) {
            $locale = $defaultLocale;
        }

        return $locale;
    }
}
