<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_wb_reports
 * @category    admin
 * @copyright   Wunderbyte GmbH 2024 <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$componentname = 'local_wb_reports';

// Default for users that have site config.
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_wb_reports_settings',  get_string('pluginname', 'local_wb_reports'));
    $ADMIN->add('localplugins', $settings);

    foreach (core_plugin_manager::instance()->get_plugins_of_type('wbreport') as $plugin) {
            $plugin->load_settings($ADMIN, 'localplugins', $hassiteconfig);
    }
}
