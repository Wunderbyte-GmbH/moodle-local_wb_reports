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
 * Subplugin info class.
 *
 * @package   local_wb_reports
 * @copyright Wunderbyte GmbH 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_wb_reports\plugininfo;

use core\plugininfo\base;
use part_of_admin_tree;
use admin_settingpage;
use coding_exception;
use moodle_url;

/**
 * Models subplugin define classes.
 */
class wbreport extends base {

    /**
     * Returns the information about plugin availability
     *
     * True means that the plugin is enabled. False means that the plugin is
     * disabled. Null means that the information is not available, or the
     * plugin does not support configurable availability or the availability
     * can not be changed.
     *
     * @return null|bool
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * By default uninstallation is not allowed, plugin developers must enable it explicitly!
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Returns the node name used in admin settings menu for this plugin settings (if applicable)
     *
     * @return null|string node name or null if plugin does not create settings node (default)
     */
    public function get_settings_section_name() {
        return 'wbreport_' . $this->name . '_settings';
    }
    /**
     * Loads plugin settings to the settings tree
     *
     * This function usually includes settings.php file in plugins folder.
     * Alternatively it can create a link to some settings page (instance of admin_externalpage)
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig whether the current user has moodle/site:config capability
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {

        $ADMIN = $adminroot; // May be used in settings.php.
        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $page = new admin_settingpage($section, $this->displayname);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($page) {
            $ADMIN->add($parentnodename, $page);
        }
    }

    /**
     * Pre-uninstall hook.
     */
    public function uninstall_cleanup() {
        global $CFG;

        parent::uninstall_cleanup();
    }

    /**
     * Get report title.
     * @param string $reportidentifier
     * @return string the report title
     * @throws coding_exception
     */
    public function get_report_title(string $reportidentifier) {
        return get_string('pluginname', 'wbreport_' . $reportidentifier);
    }

    /**
     * Get report description.
     * @param string $reportidentifier
     * @return string the report description
     * @throws coding_exception
     */
    public function get_report_description(string $reportidentifier) {
        return get_string('description', 'wbreport_' . $reportidentifier);
    }

    /**
     * Get report link.
     * @param string $reportidentifier
     * @return string the report link
     * @throws coding_exception
     */
    public function get_report_link(string $reportidentifier) {
        $moodleurl = new moodle_url('/local/wb_reports/wbreport/' . $reportidentifier . '/report.php');
        return $moodleurl->out(false);
    }

    /**
     * Get dashboard link.
     * @return string the dashboard link
     * @throws coding_exception
     */
    public function get_dashboard_link() {
        $moodleurl = new moodle_url('/local/wb_reports/dashboard.php');
        return $moodleurl->out(false);
    }

    /**
     * Helper function for string aggregation
     * (copied from Moodle report builder and slightly adapted to pass $separator).
     *
     * @param string $field
     * @param string $separator
     * @return string
     */
    public static function string_agg(string $field, string $separator): string {
        global $DB;

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        $fieldsort = self::sql_group_concat_sort($field);

        // Postgres handles group concatenation differently in that it requires the expression to be cast to char, so we can't
        // simply pass "DISTINCT {$field}" to the {@see \moodle_database::sql_group_concat} method in all cases.
        if ($DB->get_dbfamily() === 'postgres') {
            $field = $DB->sql_cast_to_char($field);
            if ($fieldsort !== '') {
                $fieldsort = "ORDER BY {$fieldsort}";
            }

            return "STRING_AGG(DISTINCT {$field}, '" . $separator . "' {$fieldsort})";
        } else {
            return $DB->sql_group_concat("DISTINCT {$field}", $fieldsort);
        }
    }

    /**
     * Generate SQL expression for sorting group concatenated fields
     *
     * @param string $field The original field or SQL expression
     * @param string|null $sort A valid SQL ORDER BY to sort the concatenated fields, if omitted then $field will be used
     * @return string
     */
    public static function sql_group_concat_sort(string $field, string $sort = null): string {
        global $DB;

        // Fallback to sorting by the specified field, unless it contains parameters which would be duplicated.
        if ($sort === null && !preg_match('/[:?$]/', $field)) {
            $fieldsort = $field;
        } else {
            $fieldsort = $sort;
        }

        // Nothing to sort by.
        if ($fieldsort === null) {
            return '';
        }

        // If the sort specifies a direction, we need to handle that differently in Postgres.
        if ($DB->get_dbfamily() === 'postgres') {
            $fieldsortdirection = '';

            preg_match('/(?<direction>ASC|DESC)?$/i', $fieldsort, $matches);
            if (array_key_exists('direction', $matches)) {
                $fieldsortdirection = $matches['direction'];
                $fieldsort = core_text::substr($fieldsort, 0, -(core_text::strlen($fieldsortdirection)));
            }

            // Cast sort, stick the direction on the end.
            $fieldsort = $DB->sql_cast_to_char($fieldsort) . ' ' . $fieldsortdirection;
        }

        return $fieldsort;
    }
}
