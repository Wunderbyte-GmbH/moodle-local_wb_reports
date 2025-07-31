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
 * Moodle hooks for local_wb_reports
 * @package    local_wb_reports
 * @copyright  2024 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function local_wb_reports_render_navbar_output(\renderer_base $renderer) {
    global $CFG, $USER;

    $context = context_system::instance();

    $output = '';
    $dropdownitems = '';
    $customfields = profile_user_record($USER->id);
    if (isset($customfields->ispartner) && $customfields->ispartner == true) {
        $ispartner = true;
        $dropdownitems .= '<a class="dropdown-item" href="' . $CFG->wwwroot . '/local/wb_reports/wbreport/egpbl/report.php">' . get_string('pluginname', 'wbreport_egpbl') .
        '</a>';
        $output = '<div class="popover-region nav-link icon-no-margin dropdown">
        <button class="btn btn-light dropdown-toggle" type="button"
        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-table" aria-hidden="true"></i>' .
        '</button><div class="dropdown-menu" aria-labelledby="dropdownMenuButton">' .
        '<div class="dropdown-divider"></div>' .
        $dropdownitems . '</div></div>';
    }

    if (isset($customfields->departmenthead) && $customfields->departmenthead == true) {
        $ispartner = true;
        $dropdownitems .= '<a class="dropdown-item" href="' . $CFG->wwwroot . '/local/wb_reports/wbreport/egdepartmenthead/report.php">' . get_string('pluginname', 'wbreport_egpbl') .
        '</a>';
        $output = '<div class="popover-region nav-link icon-no-margin dropdown">
        <button class="btn btn-light dropdown-toggle" type="button"
        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-table" aria-hidden="true"></i>' .
        '</button><div class="dropdown-menu" aria-labelledby="dropdownMenuButton">' .
        '<div class="dropdown-divider"></div>' .
        $dropdownitems . '</div></div>';
    }

    if (!isloggedin() ||
        isguestuser() ||
        (!has_capability('local/wb_reports:view', $context) &&
        !has_capability('local/wb_reports:admin', $context))) {
        return $output;
    }

    $output = '';
    $dropdownitems = '';
    $skiplist = ['egpbl', 'egdepartmenthead'];
    foreach (core_plugin_manager::instance()->get_plugins_of_type('wbreport') as $plugin) {
        if (in_array($plugin->name, $skiplist)) {
            continue;
        }
        $dropdownitems .= '<a class="dropdown-item" href="' . $CFG->wwwroot . '/local/wb_reports/wbreport/' .
                $plugin->name . '/report.php">' . get_string('pluginname', 'wbreport_' . $plugin->name) .
            '</a>';
    }

    $output = '<div class="popover-region nav-link icon-no-margin dropdown">
        <button class="btn btn-light dropdown-toggle" type="button"
        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-table" aria-hidden="true"></i>' .
        '</button><div class="dropdown-menu" aria-labelledby="dropdownMenuButton">' .
        '<h6 class="dropdown-header">' . get_string('pluginname', 'local_wb_reports') . '</h6>' .
        '<a class="dropdown-item" href="' . $CFG->wwwroot . '/local/wb_reports/dashboard.php">' .
            get_string('dashboard', 'local_wb_reports') . '</a><div class="dropdown-divider"></div>' .
        $dropdownitems . '</div></div>';

    return $output;
}
