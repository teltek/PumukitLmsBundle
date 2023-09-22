<?php

namespace Pumukit\LmsBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TagService;

class LmsService
{
    public const LMS_TAG_CODE = 'PUCHLMS';

    /** @var TagService */
    private $tagService;

    /** @var array */
    private $domainsPatterns;
    private $allowedLocales;
    private $defaultLocale;

    public function __construct(
        TagService $tagService,
        array $domainsPatterns,
        array $allowedLocales,
        $defaultLocale
    ) {
        $this->tagService = $tagService;
        $this->domainsPatterns = $domainsPatterns;
        $this->allowedLocales = $allowedLocales;
        $this->defaultLocale = $defaultLocale;
    }

    public function getCurrentLocale(string $queryLocale): string
    {
        $locale = strtolower($queryLocale);
        if ((!$locale) || (!in_array($locale, $this->allowedLocales))) {
            return $this->defaultLocale;
        }

        return $locale;
    }

    public function addPublicationChannelToMultimediaObject(MultimediaObject $multimediaObject): void
    {
        if ($multimediaObject->getProperty('openedx') || $multimediaObject->getProperty('lms')) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, self::LMS_TAG_CODE);
        }
    }

    public function validateAccessDomain(string $referer): bool
    {
        $currentDomain = parse_url($referer, PHP_URL_HOST);

        return $this->validateRegexDomain($this->domainsPatterns, $currentDomain);
    }

    private function validateRegexDomain(array $domainsPatterns, string $currentDomain): bool
    {
        foreach ($domainsPatterns as $pattern) {
            if ('*' === $pattern) {
                return true;
            }

            if (1 === preg_match('/'.$pattern.'/i', $currentDomain)) {
                return true;
            }
        }

        return false;
    }
}
