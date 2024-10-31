<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\LmsBundle\Utils\SeriesUtils;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;

class SeriesService
{
    public const LMS_SERIES_CRITERIA = ['properties.lms' => true];
    private $documentManager;
    private $factoryService;
    private $personalSeriesService;
    private $defaultSeriesTitle;
    private $locales;

    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        PersonalSeriesService $personalSeriesService,
        string $defaultSeriesTitle,
        array $locales
    ) {
        $this->documentManager = $documentManager;
        $this->factoryService = $factoryService;
        $this->personalSeriesService = $personalSeriesService;
        $this->defaultSeriesTitle = $defaultSeriesTitle;
        $this->locales = $locales;
    }

    public function getSeriesToUpload(): Series
    {
        $personalSeries = $this->personalSeriesService->find();
        if ($personalSeries) {
            return $personalSeries;
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy(self::LMS_SERIES_CRITERIA);
        if (!$series instanceof Series) {
            $series = $this->createLmsSeries();
        }

        return $series;
    }

    private function createLmsSeries(): Series
    {
        $series = $this->factoryService->createSeries(null, SeriesUtils::buildI18nTitle($this->defaultSeriesTitle, $this->locales));
        $series->setProperty(PumukitLmsBundle::PROPERTY_LMS, true);

        $this->documentManager->flush();

        return $series;
    }
}
