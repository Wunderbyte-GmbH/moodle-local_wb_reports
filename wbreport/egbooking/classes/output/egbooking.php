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
 * Wunderbyte reports: egbooking class.
 *
 * @package     wbreport_egbooking
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_egbooking\output;

use cache_helper;
use context_course;
use context_system;
use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;
use local_wunderbyte_table\filters\types\datepicker;
use stdClass;
use local_wunderbyte_table\filters\types\standardfilter;
use renderer_base;
use renderable;
use templatable;
use wbreport_egbooking\local\table\egbooking_table;
use core_reportbuilder\local\aggregation\groupconcatdistinct;

/**
 * This class prepares data for the report.
 *
 * @package     wbreport_egbooking
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class egbooking implements renderable, templatable, wbreport_interface {

    /**
     * @var array $tabledata
     */
    private $tabledata = [];

    /**
     * In the constructor, we gather all the data we need.
     */
    public function __construct() {
        global $DB, $USER;

        cache_helper::purge_by_event('setbackwbreportscache');
        $syscontext = context_system::instance();

        // Create instance of transactions wb_table and specify columns and headers.
        $table = new egbooking_table('egbooking_table');

        // Headers.
        $table->define_headers([
            get_string('userid', 'local_wb_reports'),
            get_string('firstname', 'core'),
            get_string('lastname', 'core'),
            get_string('pbl', 'wbreport_egbooking'),
            get_string('tenant', 'wbreport_egbooking'),
            get_string('pp', 'wbreport_egbooking'),
            get_string('ispartner', 'wbreport_egbooking'),
            get_string('countbooked', 'wbreport_egbooking'),
            get_string('bookedoptions', 'wbreport_egbooking'),
            get_string('countcanceled', 'wbreport_egbooking'),
            get_string('canceledoptions', 'wbreport_egbooking'),
        ]);

        // Columns.
        $table->define_columns([
            'userid',
            'firstname',
            'lastname',
            'pbl',
            'tenant',
            'pp',
            'ispartner',
            'countbooked',
            'bookedoptions',
            'countcanceled',
            'canceledoptions',
        ]);

        // Define SQL here.
        $fields = "m.*";

        $from = "(SELECT u.id userid, u.firstname, u.lastname,
                s1.pbl, s2.pp, s3.tenant,
                CASE
                    WHEN s4.ispartner IS NULL THEN '0'
                    ELSE s4.ispartner
                END ispartner,
                s5.countbooked, s6.bookedoptions,
                s7.countcanceled, s8.canceledoptions
                FROM {user} u
                LEFT JOIN (
                    SELECT userid, data AS pbl
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE name LIKE '%PBL%'
                    LIMIT 1)
                ) s1
                ON s1.userid = u.id
                LEFT JOIN (
                    SELECT userid, data AS pp
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE shortname LIKE '%artner%ogram%' -- Partnerprogramm, use pattern to be safe.
                    LIMIT 1)
                ) s2
                ON s2.userid = u.id
                LEFT JOIN (
                    SELECT userid, data AS tenant
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE shortname = 'tenant'
                    LIMIT 1)
                ) s3
                ON s3.userid = u.id
                LEFT JOIN (
                    SELECT userid, data AS ispartner
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE shortname = 'ispartner'
                    LIMIT 1)
                ) s4
                ON s4.userid = u.id
                LEFT JOIN (
                    SELECT userid, COUNT(DISTINCT optionid) countbooked
                    FROM {booking_answers}
                    WHERE waitinglist = 0
                    GROUP BY userid
                ) s5
                ON s5.userid = u.id
                LEFT JOIN (
                    SELECT DISTINCT ba.userid, " .
                    wbreport::string_agg($DB->sql_concat('bo.text', "' (ID: '", 'bo.id', "')'"), '&emsp;<br>') . "
                    AS bookedoptions
                    FROM {booking_answers} ba
                    JOIN {booking_options} bo
                    ON bo.id = ba.optionid
                    WHERE waitinglist = 0
                    GROUP BY ba.userid
                ) s6
                ON s6.userid = u.id
                LEFT JOIN (
                    SELECT userid, COUNT(DISTINCT optionid) countcanceled
                    FROM {booking_answers}
                    WHERE waitinglist = 5
                    GROUP BY userid
                ) s7
                ON s7.userid = u.id
                LEFT JOIN (
                    SELECT DISTINCT ba.userid, " .
                    wbreport::string_agg($DB->sql_concat('bo.text', "' (ID: '", 'bo.id', "')'"), '&emsp;<br>') . "
                    AS canceledoptions
                    FROM {booking_answers} ba
                    JOIN {booking_options} bo
                    ON bo.id = ba.optionid
                    WHERE waitinglist = 5
                    GROUP BY ba.userid
                ) s8
                ON s8.userid = u.id
            ) m";

        // Determine the $where part.
        $where = "1=1"; // No $where is needed in this report currently.

        $table->set_filter_sql($fields, $from, $where, '', $params ?? []);

        $table->sortable(true, 'lastname', SORT_ASC);

        // Define Filters.
        $standardfilter = new standardfilter('pbl', get_string('pbl', 'wbreport_egbooking'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('tenant', get_string('tenant', 'wbreport_egbooking'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('pp', get_string('pp', 'wbreport_egbooking'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('ispartner', get_string('ispartner', 'wbreport_egbooking'));
        $standardfilter->add_options([
            '1' => '✅',
            '0' => '❌',
        ]);
        $table->add_filter($standardfilter);

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $datepicker = new datepicker(
            'startdate',
            get_string('coursestart', 'local_wb_reports'),
        );
        $datepicker->add_options(
            'in between',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            'now',
            'now + 1 year'
        );
        $table->add_filter($datepicker); */

        // Full text search columns.
        $table->define_fulltextsearchcolumns([
            'userid',
            'firstname',
            'lastname',
            'pbl',
            'tenant',
            'pp',
            'ispartner',
            'bookedoptions',
            'canceledoptions',
        ]);

        // Sortable columns.
        $table->define_sortablecolumns([
            'userid',
            'firstname',
            'lastname',
            'pbl',
            'tenant',
            'pp',
            'ispartner',
            'countbooked',
            'countcanceled',
        ]);

        $table->define_cache('local_wb_reports', 'wbreportscache');

        $table->pageable(true);

        // Count label.
        $table->showcountlabel = true;

        // Download button.
        $table->showdownloadbutton = true;

        // Reload button.
        $table->showreloadbutton = true;

        // Apply filter on download.
        $table->applyfilterondownload = true;

        // Pass html to render.
        list($idstring, $encodedtable, $html) = $table->lazyouthtml(50, true);
        $this->tabledata = $html;

    }

    /**
     * Use this function to render any HTML in the report header.
     * @return string the html for the table header
     * */
    public function get_table_header_html(): string {
        return '<div class="alert alert-secondary">' .
            get_string('infotext:tableheader', 'wbreport_egbooking') .
        '</div>';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $wbreport = new wbreport();
        $data->dashboardlink = $wbreport->get_dashboard_link();
        $data->tableheader = $this->get_table_header_html();
        $data->table = $this->tabledata;
        return $data;
    }
}
