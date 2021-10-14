<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\TagInterface;
use Pumukit\WebTVBundle\Controller\SearchController as BaseSearchController;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\SearchService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchController extends BaseSearchController
{
    public function __construct(
        TranslatorInterface $translator,
        BreadcrumbsService $breadcrumbsService,
        SearchService $searchService,
        DocumentManager $documentManager,
        RequestStack $requestStack,
        PaginationService $paginationService,
        $menuSearchTitle,
        $columnsObjsSearch,
        $pumukitNewAdminLicenses,
        $limitObjsSearch
    ) {
        parent::__construct(
            $translator,
            $breadcrumbsService,
            $searchService,
            $documentManager,
            $requestStack,
            $paginationService,
            $menuSearchTitle,
            $columnsObjsSearch,
            $pumukitNewAdminLicenses,
            $limitObjsSearch
        );
    }

    /**
     * @Route("/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}", defaults={"tagCod": null, "useTagAsGeneral": false})
     * @Route("/search/public/multimediaobjects")
     * @ParamConverter("blockedTag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("@PumukitLms/Search/index.html.twig")
     */
    public function multimediaObjectsAction(Request $request, TagInterface $blockedTag = null, bool $useTagAsGeneral = false): array
    {
        $request->attributes->set('only_public', true);

        return parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);
    }
}
