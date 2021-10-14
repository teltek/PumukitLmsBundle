<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Utils;

class SeriesUtils
{
    public static function buildI18nTitle(string $titleParam, array $locales): array
    {
        $title = [];
        foreach ($locales as $locale) {
            $title[$locale] = $titleParam;
        }

        return $title;
    }

    public static function buildParams($i18nTitle, $seriesId): string
    {
        $data = [];
        $data['mmobjData'] = [];
        $data['mmobjData']['properties'] = ['openedx' => true];
        if ($i18nTitle) {
            $data['seriesData'] = [];
            $data['seriesData']['title'] = $i18nTitle;
        }
        $values = ['externalData' => $data];

        if ($seriesId) {
            $values['series'] = $seriesId;
        }

        return http_build_query($values);
    }
}
