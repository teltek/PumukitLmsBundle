<?php

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TagService;

class LmsService
{
    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var TagService
     */
    private $tagService;
    /**
     * @var array
     */
    private $domainsPatterns;

    /**
     * @var array
     */
    private $allowedLocales;

    private $defaultLocale;

    const LmsTagCode = 'PUCHLMS';

    /**
     * LmsService constructor.
     *
     * @param DocumentManager $dm
     * @param TagService      $tagService
     * @param array           $domainsPatterns
     * @param array           $allowedLocales
     * @param                 $defaultLocale
     */
    public function __construct(DocumentManager $dm, TagService $tagService, array $domainsPatterns, array $allowedLocales, $defaultLocale)
    {
        $this->dm = $dm;
        $this->tagService = $tagService;
        $this->domainsPatterns = $domainsPatterns;
        $this->allowedLocales = $allowedLocales;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param $queryLocale
     *
     * @return string
     */
    public function getCurrentLocale($queryLocale)
    {
        $locale = strtolower($queryLocale);
        if ((!$locale) || (!in_array($locale, $this->allowedLocales))) {
            return $this->defaultLocale;
        }

        return $locale;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @throws \Exception
     */
    public function addPublicationChannelToMultimediaObject(MultimediaObject $multimediaObject)
    {
        if ($multimediaObject->getProperty('openedx') || $multimediaObject->getProperty('lms')) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, self::LmsTagCode);
        }
    }

    /**
     * @param $referer
     *
     * @return bool
     */
    public function validateAccessDomain($referer)
    {
        $currentDomain = parse_url($referer, PHP_URL_HOST);

        return $this->validateRegexDomain($this->domainsPatterns, $currentDomain);
    }

    /**
     * @param array $domainsPatterns
     * @param       $currentDomain
     *
     * @return bool
     */
    private function validateRegexDomain(array $domainsPatterns, $currentDomain)
    {
        foreach ($domainsPatterns as $pattern) {
            if (1 === preg_match('/'.$pattern.'/i', $currentDomain)) {
                return true;
            }
        }

        return false;
    }
}
