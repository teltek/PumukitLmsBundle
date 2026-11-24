<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\WebTVBundle\Services\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/openedx')]
class SearchController extends AbstractController implements WebTVControllerInterface
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly DocumentManager $documentManager,
        private readonly PaginationService $paginationService,
        private readonly ?string $menuSearchTitle,
        private readonly int|string $columnsObjsSearch,
        private readonly array $pumukitNewAdminLicenses,
        private readonly int|string $limitObjsSearch
    ) {}

    #[Route('/searchmultimediaobjects/{tagCod}/{useTagAsGeneral}', defaults: ['tagCod' => null, 'useTagAsGeneral' => false])]
    #[Route('/search/public/multimediaobjects')]
    public function multimediaObjectsAction(Request $request, #[MapDocument(mapping: ['tagCod' => 'cod'])] ?Tag $blockedTag = null, bool $useTagAsGeneral = false): Response
    {
        [$parentTag, $parentTagOptional] = $this->searchService->getSearchTags();

        $searchFound = trim($request->query->getString('search'));
        $tagsFound = $request->query->all('tags');

        $queryBuilder = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $queryBuilder->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $this->searchService->addSearchQueryBuilder($queryBuilder, $request->getLocale(), $searchFound);
        $this->searchService->addTypeQueryBuilder($queryBuilder, $request->query->get('type'));
        $this->searchService->addDurationQueryBuilder($queryBuilder, $request->query->get('duration'));
        $this->searchService->addDateQueryBuilder($queryBuilder, $request->query->get('start'), $request->query->get('end'), $request->query->get('year'));
        $this->searchService->addLanguageQueryBuilder($queryBuilder, $request->query->get('language'));
        $this->searchService->addTagsQueryBuilder($queryBuilder, $tagsFound, $blockedTag, $useTagAsGeneral);
        $this->searchService->addLicenseQueryBuilder($queryBuilder, $request->query->get('license'));

        $templateListGrouped = '' === $searchFound;
        if (isset($queryBuilder->getQueryArray()['$text'])) {
            $queryBuilder->sortMeta('score', 'textScore');
        } else {
            $queryBuilder->sort('record_date', 'desc');
        }

        $pager = $this->paginationService->createDoctrineODMMongoDBAdapter($queryBuilder, $request->query->getInt('page', 1), (int) $this->limitObjsSearch);

        return $this->render('@PumukitLms/Search/template.html.twig', [
            'type' => 'multimediaObject',
            'template_title' => $this->menuSearchTitle ?? 'Multimedia objects search',
            'template_list_grouped' => $templateListGrouped,
            'objects' => $pager,
            'parent_tag' => $parentTag,
            'parent_tag_optional' => $parentTagOptional,
            'tags_found' => $tagsFound,
            'objectByCol' => $this->columnsObjsSearch,
            'licenses' => $this->pumukitNewAdminLicenses,
            'languages' => $this->searchService->getLanguages(),
            'blocked_tag' => $blockedTag,
            'search_years' => $this->searchService->getYears(SearchService::MULTIMEDIA_OBJECT),
            'total_objects' => $pager->getNbResults(),
            'class' => 'searchmultimediaobjects',
            'show_info' => true,
            'with_publicdate' => true,
        ]);
    }
}
