<?php

namespace Pumukit\LmsBundle\Controller;

use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Controller\SearchController as BaseSearchController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends BaseSearchController
{
    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @Route("/search/public/multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitLmsBundle:Search:index.html.twig")
     *
     * @param mixed $useTagAsGeneral
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, $useTagAsGeneral = false): array
    {
        $request->attributes->set('only_public', true);

        return parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);
    }
}
