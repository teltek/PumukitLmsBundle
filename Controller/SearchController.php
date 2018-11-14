<?php

namespace Pumukit\LmsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\Tag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\WebTVBundle\Controller\SearchController as BaseSearchController;

class SearchController extends BaseSearchController
{
    /**
     * @param Request  $request
     * @param Tag|null $blockedTag
     * @param bool     $useTagAsGeneral
     *
     * @return array
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @Route("/search/public/multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitLmsBundle:Search:index.html.twig")
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false)
    {
        $response = parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);

        return $response;
    }
}
