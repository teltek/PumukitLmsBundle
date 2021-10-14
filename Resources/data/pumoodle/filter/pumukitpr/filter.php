<?php

/**
 *  Pumukitpr link filtering.
 *
 * This filter will replace any link generated with pumukitpr repository
 * with an iframe that will retrieve the content served by pumukitpr.
 *
 * It uses ideas from the mediaplugin filter and the helloworld filter template.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || exit();
defined('SECRET') || define('SECRET', 'ThisIsASecretPasswordChangeMe');

require_once $CFG->libdir.'/filelib.php';

class filter_pumukitpr extends moodle_text_filter
{
    public const PLAYLIST_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const VIDEO_SEARCH_REGEX = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)".*?>.*?<\\/iframe>/is';
    public const LEGACY_VIDEO_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)["\']>.*?<\\/a>/is';
    public const LEGACY_PLAYLIST_SEARCH_REGEX = '/<a\\s[^>]*href=["\'](https?:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)["\']>.*?<\\/a>/is';

    public function filter($text, array $options = []): string
    {
        global $CFG;

        if (!filter_is_valid_text($text)) {
            return $text;
        }

        if (filter_is_legacy_url($text)) {
            $parsedUrl = filter_convert_legacy_url($text);
            $search = (filter_is_a_playlist($parsedUrl)) ? self::LEGACY_PLAYLIST_SEARCH_REGEX : self::LEGACY_VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, 'filter_pumukitpr_callback', $parsedUrl);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        if (filter_is_an_iframe($text)) {
            $search = (filter_is_a_playlist($text)) ? self::PLAYLIST_SEARCH_REGEX : self::VIDEO_SEARCH_REGEX;
            $iframe = preg_replace_callback($search, 'filter_pumukitpr_openedx_callback', $text);
            if (filter_validate_returned_iframe($text, $iframe)) {
                return $iframe;
            }
        }

        return $text;
    }
}

function filter_convert_legacy_url(string $text): string
{
    if (false !== stripos($text, 'playlist')) {
        return str_replace('pumoodle/embed/playlist', 'openedx/openedx/playlist/embed', $text);
    }

    return str_replace('pumoodle/embed', 'openedx/openedx/embed', $text);
}

function filter_validate_returned_iframe(string $oldText, string $newText): bool
{
    return !empty($newText) && $newText !== $oldText;
}

function filter_is_a_playlist(string $text): bool
{
    return false !== stripos($text, 'playlist');
}

function filter_is_an_iframe(string $text): bool
{
    return false !== stripos($text, '<iframe');
}

function filter_is_an_link(string $text): bool
{
    return false !== stripos($text, '<a');
}

function filter_is_legacy_url(string $text): bool
{
    return false !== stripos($text, 'pumoodle');
}

function filter_is_valid_text(string $text): bool
{
    $isValidText = false;
    if (is_string($text) && !empty($text)) {
        $isValidText = true;
    }

    if (filter_is_an_link($text) && filter_is_an_iframe($text)) {
        $isValidText = true;
    }

    return $isValidText;
}

function filter_pumukitpr_openedx_callback(array $link): string
{
    global $CFG;
    //Get arguments from url.

    $link_params = [];
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);
    //Initialized needed arguments.
    $multistream = isset($link_params['multistream']) ? ('1' == $link_params['multistream']) : false;
    $mm_id = $link_params['id'] ?? null;
    if (!$mm_id) {
        $mm_id = $link_params['playlist'] ?? null;
    }
    $email = $link_params['email'] ?? null;

    $extra_arguments = [
        'professor_email' => $email,
        'hash' => filter_create_ticket($mm_id, $email ?: '', parse_url($link[1], PHP_URL_HOST)),
    ];
    $new_url_arguments = '?'.http_build_query(array_merge($extra_arguments, $link_params), '', '&');

    $url = preg_replace('/(\\?.*)/i', $new_url_arguments, $link[1]);

    return str_replace($link[1], $url, $link[0]);
}

function filter_pumukitpr_callback(array $link): string
{
    global $CFG;
    //Get arguments from url.
    $link_params = [];
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);
    //Initialized needed arguments.
    $multistream = isset($link_params['multistream']) ? ('1' == $link_params['multistream']) : false;
    $mm_id = $link_params['id'] ?? null;
    $email = $link_params['email'] ?? null;
    //Prepare new parameters.
    $extra_arguments = [
        'professor_email' => $email,
        'hash' => filter_create_ticket($mm_id, $email ?: '', parse_url($link[1], PHP_URL_HOST)),
    ];
    $new_url_arguments = '?'.http_build_query(array_merge($extra_arguments, $link_params), '', '&');
    //Create new url with ticket and correct email.
    $url = preg_replace('/(\\?.*)/i', $new_url_arguments, $link[1]);
    //Prepare and return iframe with correct sizes to embed on webpage.
    if ($multistream) {
        $iframe_width = $CFG->iframe_multivideo_width ?: '100%';
        $iframe_height = $CFG->iframe_multivideo_height ?: '333px';
    } else {
        $iframe_width = $CFG->iframe_singlevideo_width ?: '592px';
        $iframe_height = $CFG->iframe_singlevideo_height ?: '333px';
    }
    $iframe_html = '<iframe src="'.$url.'"'.
                   '        style="border:0px #FFFFFF none; width:'.$iframe_width.'; height:'.$iframe_height.';"'.
                   '        scrolling="no" frameborder="0" webkitallowfullscreen="true" mozallowfullscreen="true" allowfullscreen="true" >'.
                   '</iframe>';

    return $iframe_html;
}

function filter_create_ticket($id, $email, $domain): string
{
    global $CFG;

    $secret = empty($CFG->filter_pumukitpr_secret) ? SECRET : $CFG->filter_pumukitpr_secret;

    $date = date('d/m/Y');

    return md5($email.$secret.$date.$domain);
}
