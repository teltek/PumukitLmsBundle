<?php

namespace Pumukit\OpenEdxBundle\Controller;

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
     * @Route("/embed", name="pumukit_openedx_openedx_embed")
     * @Route("/embed/", name="pumukit_openedx_openedx_embed")
     */
    public function iframeAction(Request $request)
    {
        $locale = $this->getLocale($request->get('lang'));
        $contactEmail = $this->getParameter('pumukit2.info')['email'];

        $openEdxLmsHost = $this->container->getParameter('pumukit_openedx.open_edx_lms_host');
        $openEdxCmsHost = $this->container->getParameter('pumukit_openedx.open_edx_cms_host');
        $moodleHost = $this->container->getParameter('pumukit_openedx.moodle_host');

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $id = $request->get('id');
        $multimediaObject = $mmobjRepo->find($id);

        $user = $this->getUser();

        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        if (!$multimediaObject || !$user || (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$multimediaObjectService->isUserOwner($user, $multimediaObject))) {

            if('dev' != $this->get('kernel')->getEnvironment()) {
                $refererUrl = $request->headers->get('referer');
                if (!$refererUrl) {
                    return new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
                }
                $refererUrl = parse_url($refererUrl, PHP_URL_HOST);
                if (!in_array($refererUrl, array($openEdxLmsHost, $openEdxCmsHost, $moodleHost))) {
                    return new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
                }
            }

            $ssoService = $this->container->get('pumukit_open_edx.sso');
            if (!$ssoService->validateHash($request->get('hash'), '')) {
                return new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);
            }
        }

        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');
        //$profileService = $this->get('pumukitencoder.profile');
        //$displayProfiles = $profileService->getProfiles(true);
        //$profileNames = array_keys($displayProfiles);
        $profileNames = array('master_webm_camera', 'master_webm_screen'); //TODO check if is published and not the jobs.

        $job = $jobRepo->findOneBy(array('mm_id' => $id, 'profile' => array('$in' => $profileNames)));

        if ($multimediaObject) {
            if ($multimediaObject->containsTagWithCod('PUCHWEBTV') || $multimediaObject->containsTagWithCod('PUCHOPENEDX')) {

                if (!$job || ($job && $job->getStatus() !== Job::STATUS_FINISHED)) {
                    return new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:400job.html.twig', array('id' => $id, 'job' => $job, 'email' => $contactEmail, 'openedx_locale' => $locale)), 400);
                } else {
                    return $this->renderIframe($multimediaObject, $request);
                }
            } else {
                $response = new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);

                return $response;
            }
        }

        $response = new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:404notfound.html.twig', array('id' => $id, 'openedx_locale' => $locale)), 404);

        return $response;
    }

    /**
     * Render iframe.
     */
    protected function renderIframe(MultimediaObject $multimediaObject, Request $request)
    {
        return $this->forward('PumukitBasePlayerBundle:BasePlayer:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
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
