<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Atto text editor integration version file.
 *
 * @package    atto_pumukitpr
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Initialise the strings required for js
 */
function atto_pumukitpr_strings_for_js() {
    global $PAGE;

    $strings = array(
        'dialogtitle',
    );

    $PAGE->requires->strings_for_js($strings, 'atto_pumukitpr');
}


/**
 * Return the js params required for this module.
 * @return array of additional params to pass to javascript init function for this module.
 */
function atto_pumukitpr_params_for_js($elementid, $options, $fpoptions) {
    global $USER;
    
    $params = array();

    $params['pumukitprurl'] = get_config('atto_pumukitpr', 'pumukitprurl');
    $params['dialogtitle'] = get_config('atto_pumukitpr', 'dialogtitle');
    $params['username'] = $USER->username;
    $params['email'] = $USER->email;

    $date = date('d/m/Y');
    $password = get_config('atto_pumukitpr', 'password');
    $domain = parse_url($params['pumukitprurl'], PHP_URL_HOST);
    $hash = md5($USER->username.$password.$date.$domain);
    $params['hash'] = $hash;

    return $params;
}
