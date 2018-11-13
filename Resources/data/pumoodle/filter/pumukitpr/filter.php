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
defined('MOODLE_INTERNAL') || die();
defined('SECRET') || define('SECRET', 'ThisIsASecretPasswordChangeMe');

require_once $CFG->libdir.'/filelib.php';

class filter_pumukitpr extends moodle_text_filter
{
    public function filter($text, array $options = array())
    {
        global $CFG;

        // discard empty texts and texts without links
        if (!is_string($text) or empty($text)) {
            return $text;
        }

        if (false === stripos($text, '<iframe') && false === stripos($text, '<a')) {
            return $text;
        }

        if (false !== stripos($text, '<iframe')) {
            // Look for '/pumoodle/embed', replace the entire <a... </a> tag and send the url as $link[1]
            if (false !== stripos($text, 'openedx/playlist/embed/')) {
                $search = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/playlist\\/embed.*?)".*?>.*?<\\/iframe>/is';
            } else {
                $search = '/<iframe[^>]*?src=\"(https:\\/\\/[^>]*?\\/openedx\\/openedx\\/embed.*?)".*?>.*?<\\/iframe>/is';
            }
            $newText = preg_replace_callback($search, 'filter_pumukitpr_openedx_callback', $text);
        }

        if (!empty($newText) && $newText !== $text) {
            $text = $newText;
        }

        if (false !== stripos($text, '<a')) {
            // Look for '/pumoodle/embed', replace the entire <a... </a> tag and send the url as $link[1]
            $search = '/<a\\s[^>]*href=\"(https?:\\/\\/[^>]*?\\/pumoodle\\/embed.*?)\">.*?<\\/a>/is';
            $newText = preg_replace_callback($search, 'filter_pumukitpr_callback', $text);
        }

        if (!empty($newText) && $newText !== $text) {
            $text = $newText;
        }

        return $text;
    }
}

function filter_pumukitpr_openedx_callback($link)
{
    global $CFG;
    //Get arguments from url.

    $link_params = array();
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);
    //Initialized needed arguments.
    $multistream = isset($link_params['multistream']) ? ('1' == $link_params['multistream']) : false;
    $mm_id = isset($link_params['id']) ? $link_params['id'] : null;
    if(!$mm_id) {
        $mm_id = isset($link_params['playlist']) ? $link_params['playlist'] : null;
    }
    $email = isset($link_params['email']) ? $link_params['email'] : null;
    //Prepare new parameters.

    $extra_arguments = array(
        'professor_email' => $email,
        'hash' => filter_pumukitpr_create_ticket_openedx($mm_id, $email ? $email : '', parse_url($link[1], PHP_URL_HOST)),
    );
    $new_url_arguments = '?'.http_build_query(array_merge($extra_arguments, $link_params), '', '&');
    //Create new url with ticket and correct email.

    $url = preg_replace("/(\?.*)/i", $new_url_arguments, $link[1]);

    return str_replace($link[1], $url, $link[0]);
}

function filter_pumukitpr_create_ticket_openedx($id, $email, $domain)
{
    global $CFG;

    $pumukitsecret = empty($CFG->filter_pumukitpr_secret) ? SECRET : $CFG->filter_pumukitpr_secret;

    $date = date('d/m/Y');
    // At the moment, the IP is not checked on PuMuKit's side
    $ip = $_SERVER['REMOTE_ADDR'];
    $ticket = md5($email.$pumukitsecret.$date.$domain);

    return $ticket;
}

function filter_pumukitpr_callback($link)
{
    global $CFG;
    //Get arguments from url.
    $link_params = array();
    parse_str(html_entity_decode(parse_url($link[1], PHP_URL_QUERY)), $link_params);
    //Initialized needed arguments.
    $multistream = isset($link_params['multistream']) ? ('1' == $link_params['multistream']) : false;
    $mm_id = isset($link_params['id']) ? $link_params['id'] : null;
    $email = isset($link_params['email']) ? $link_params['email'] : null;
    //Prepare new parameters.
    $extra_arguments = array(
        'professor_email' => $email,
        'ticket' => filter_pumukitpr_create_ticket($mm_id, $email),
    );
    $new_url_arguments = '?'.http_build_query(array_merge($extra_arguments, $link_params), '', '&');
    //Create new url with ticket and correct email.
    $url = preg_replace("/(\?.*)/i", $new_url_arguments, $link[1]);
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

function filter_pumukitpr_create_ticket($id, $email)
{
    global $CFG;

    $pumukitsecret = empty($CFG->filter_pumukitpr_secret) ? SECRET : $CFG->filter_pumukitpr_secret;
    $date = date('Y-m-d');
    // At the moment, the IP is not checked on PuMuKit's side
    $ip = $_SERVER['REMOTE_ADDR'];
    $ticket = md5($pumukitsecret.$date.$id.$email);

    return $ticket;
}
