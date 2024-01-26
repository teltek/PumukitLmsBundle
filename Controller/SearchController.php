<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Controller\SearchController as BaseSearchController;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\SearchService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/openedx")
 * @Route("/lms")
 */
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
     *
     * @ParamConverter("blockedTag", options={"mapping": {"tagCod": "cod"}})
     */
    public function multimediaObjectsAction(Request $request, Tag $blockedTag = null, bool $useTagAsGeneral = false): Response
    {
        $request->attributes->set('only_public', true);

        return parent::multimediaObjectsAction($request, $blockedTag, $useTagAsGeneral);
    }

    protected function renderedTemplate(): string
    {
        return '@PumukitLms/Search/template.html.twig';
    }
}
