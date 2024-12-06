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
 * Wunderbyte reports: egpbl class.
 *
 * @package     wbreport_egpbl
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Thomas Winkler
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_egdepartmenthead\output;

use cache_helper;
use context_course;
use context_system;
use core_analytics\user;
use core_course_category;
use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;
use local_wunderbyte_table\filters\types\datepicker;
use stdClass;
use local_wunderbyte_table\filters\types\standardfilter;
use renderer_base;
use renderable;
use templatable;
use wbreport_egdepartmenthead\local\table\egdepartmenthead_table;

/**
 * This class prepares data for the report.
 *
 * @package     wbreport_egpbl
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Thomas Winkler
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class egdepartmenthead implements renderable, templatable, wbreport_interface {

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

        // Initialize params.
        $inparams1 = [];

        $courses = get_courses('all', 'c.sortorder ASC', 'c.id');


        [$insql, $inparams1] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'cid');

        // Create instance of transactions wb_table and specify columns and headers.
        $table = new egdepartmenthead_table('egdepartmenthead_table' . $USER->id . 'test');

        $usercohorts = cohort_get_user_cohorts($USER->id);
        $userids = [$USER->id];
        if (!empty($usercohorts)) {
            $firstcohort = reset($usercohorts); // Takes the first cohort.
            $cohortid = $firstcohort->id;
            $userids = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$cohortid]);
        } 

        [$insql2, $inparams2] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');

        // Define SQL here.
        $fields = "m.*";

        $from = "(SELECT " . $DB->sql_concat("u.id", "'-'", "c.id") .
                " AS uniqueid,
                c.id courseid, c.fullname, l.timeaccess,
                u.id userid, u.firstname, u.lastname,
                " . $DB->sql_concat("u.firstname", "' '" ,"u.lastname") .  " as name,
                s2.complcount, s3.modcount
                FROM {course} c
                JOIN {enrol} e ON c.id = e.courseid
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {user_lastaccess} l
                ON l.userid = u.id AND l.courseid = c.id
                LEFT JOIN (
                    SELECT uid1.userid, uid1.data AS pbl
                    FROM {user_info_data} uid1
                    WHERE uid1.fieldid = (SELECT uif1.id
                    FROM {user_info_field} uif1
                    WHERE uif1.name LIKE '%PBL%'
                    LIMIT 1)
                ) s1
                ON s1.userid = u.id
                LEFT JOIN (
                    SELECT cm.course, cmc.userid, COUNT(cmc.completionstate) AS complcount
                    FROM {course_modules} cm
                    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
                    WHERE cmc.completionstate = 1
                    GROUP BY cm.course, cmc.userid
                ) s2
                ON s2.userid = u.id AND s2.course = c.id
                LEFT JOIN (
                    SELECT cm1.course, count(*) modcount
                    FROM {course_modules} cm1
                    WHERE cm1.visible = 1 AND cm1.completion > 0
                    GROUP BY cm1.course
                ) s3
                ON s3.course = c.id
                WHERE c.id $insql AND c.id != 26
                AND u.id $insql2
            ) m";

        // Determine the $where part.
         // By default, we show nothing.
        $where = "1=0"; // By default, we show nothing.
        $user = $USER;
        profile_load_custom_fields($user);
        if (!empty($user->profile['departmenthead'])) {
            $where = "1 = 1";
        }
        // Headers.
        $headers = [];
        $headers[] = get_string('coursename', 'local_wb_reports');
        $headers[] = get_string('lastaccess', 'local_wb_reports');
        $headers[] = get_string('name', 'core');
        $headers[] = get_string('firstname', 'core');
        $headers[] = get_string('lastname', 'core');
        $headers[] = get_string('pbl', 'wbreport_egpbl');
        $headers[] = get_string('complcount', 'wbreport_egpbl');
        $table->define_headers($headers);

        // Columns.
        $columns = [];
        $columns[] = 'fullname';
        $columns[] = 'timeaccess';
        $columns[] = 'name';
        $columns[] = 'firstname';
        $columns[] = 'lastname';
        $columns[] = 'pbl';
        $columns[] = 'complcount';
        $table->define_columns($columns);

        // Merge params.
        $params = array_merge($inparams1, $inparams2);

        $table->set_filter_sql($fields, $from, $where, '', $params ?? []);

        $table->sortable(true, 'fullname', SORT_ASC);

        // Define Filters.
        $standardfilter = new standardfilter('fullname', get_string('coursename', 'local_wb_reports'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('name', get_string('fullname', ''));
        $table->add_filter($standardfilter);
        
        $standardfilter = new standardfilter('lastname', get_string('lastname', ''));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('complcount', get_string('complcount', 'wbreport_egpbl'));
        $standardfilter->add_options([
            1 => '✅',
            2 => trim(str_repeat('✅ ', 2)),
            3 => trim(str_repeat('✅ ', 3)),
            4 => trim(str_repeat('✅ ', 4)),
            5 => trim(str_repeat('✅ ', 5)),
            6 => trim(str_repeat('✅ ', 6)),
            7 => trim(str_repeat('✅ ', 7)),
            8 => trim(str_repeat('✅ ', 8)),
            9 => trim(str_repeat('✅ ', 9)),
        ]);
        $table->add_filter($standardfilter);

        $datepicker = new datepicker(
            'timeaccess',
            get_string('lastaccess', 'local_wb_reports'),
        );
        $datepicker->add_options(
            'in between',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            'now',
            'now + 1 year'
        );
        $table->add_filter($datepicker);

        // Sortable columns.
        $sortablecols = [];
        $sortablecols[] = 'fullname';
        $sortablecols[] = 'firstname';
        $sortablecols[] = 'lastname';

        $sortablecols[] = 'complcount';
        $table->define_sortablecolumns($sortablecols);

        $table->define_cache('local_wb_reports', 'wbreportscache');

        $table->pageable(true);

        // Count label.
        $table->showcountlabel = true;

        // Download button.
        $table->showdownloadbutton = true;

        // Reload button.
        $table->showreloadbutton = true;

        // Apply filter on download.
        $table->applyfilterondownload = false;

        $this->tabledata = $table->outhtml(50, false);
    }

    /**
     * Use this function to render any HTML in the report header.
     * @return string the html for the table header
     * */
    public function get_table_header_html(): string {
        return '<div class="alert alert-secondary">' .
            get_string('infotext:tableheader', 'wbreport_egpbl') .
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
