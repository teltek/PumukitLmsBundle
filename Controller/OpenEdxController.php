<?php

namespace Pumukit\OpenEdxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $id = $request->get('id');
        $locale = $this->getLocale($request->get('lang'));
        $multimediaObject = $mmobjRepo->find($id);
        $contactEmail = $this->getParameter('pumukit2.info')['email'];

        if ($multimediaObject) {
            if ($multimediaObject->containsTagWithCod('PUCHWEBTV') || $multimediaObject->containsTagWithCod('PUCHOPENEDX')) {
                return $this->renderIframe($multimediaObject, $request);
            } else {
                $response = new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:403forbidden.html.twig', array('openedx_locale' => $locale, 'email' => $contactEmail)), 403);

                return $response;
            }
        }

        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');
        $profileService = $this->get('pumukitencoder.profile');
        $displayProfiles = $profileService->getProfiles(true);
        $profileNames = array_keys($displayProfiles);

        $job = $jobRepo->findOneBy(array('mm_id' => $id, 'profile' => array('$in' => $profileNames)));
        if ($job) {
            return new Response($this->renderView('PumukitOpenEdxBundle:OpenEdx:400job.html.twig', array('id' => $id, 'job' => $job, 'email' => $contactEmail, 'openedx_locale' => $locale)), 400);
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
