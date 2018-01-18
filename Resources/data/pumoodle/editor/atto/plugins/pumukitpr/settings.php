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
 * pumukitpr settings.
 *
 * @package   atto_pumukitpr
 * @copyright COPYRIGHTINFO
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_pumukitpr', new lang_string('pluginname', 'atto_pumukitpr')));

$settings = new admin_settingpage('atto_pumukitpr_settings', new lang_string('settings', 'atto_pumukitpr'));
if ($ADMIN->fulltree) {
    // An option setting
    $settings->add(new admin_setting_configtext('atto_pumukitpr/pumukitprurl',
        get_string('pumukitprurl', 'atto_pumukitpr'), get_string('pumukitprurldesc', 'atto_pumukitpr'), 'https://naked-pr-up2u.teltek.es', PARAM_URL));

    $settings->add(new admin_setting_configtext('atto_pumukitpr/dialogtitle',
        get_string('dialogtitle', 'atto_pumukitpr'), get_string('dialogtitledesc', 'atto_pumukitpr'), get_string('dialogtitledefval', 'atto_pumukitpr'), PARAM_TEXT));

    $settings->add(new admin_setting_configtext('atto_pumukitpr/password',
        get_string('password', 'atto_pumukitpr'), get_string('passworddesc', 'atto_pumukitpr'), get_string('passworddefval', 'atto_pumukitpr'), PARAM_TEXT));
}
