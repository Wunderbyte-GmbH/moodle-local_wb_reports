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
 * Wunderbyte reports: egusers class.
 *
 * @package     wbreport_egusers
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_egusers\output;

use cache_helper;
use context_course;
use context_system;
use core_course_category;
use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;
use local_wunderbyte_table\filters\types\datepicker;
use stdClass;
use local_wunderbyte_table\filters\types\standardfilter;
use renderer_base;
use renderable;
use templatable;
use wbreport_egusers\local\table\egusers_table;

/**
 * This class prepares data for the report.
 *
 * @package     wbreport_egusers
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class egusers implements renderable, templatable, wbreport_interface {

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
        $inparams2 = [];
        $courseids = [];

        // Get a list of all EG e-training courses.
        $topcategory = core_course_category::get(1); // ID: 1 is category "EG Group - eTraining".

        // Get all courses within top category.
        $courses = $topcategory->get_courses();
        foreach ($courses as $course) {
            $courseids[] = $course->id;
        }
        // Now get all courses within sub-categories too.
        $categoryids = $topcategory->get_all_children_ids();
        foreach ($categoryids as $categoryid) {
            $coursecat = core_course_category::get($categoryid);
            $courses = $coursecat->get_courses();
            foreach ($courses as $course) {
                $courseids[] = $course->id;
            }
        }

        [$insql, $inparams1] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');

        // Create instance of transactions wb_table and specify columns and headers.
        $table = new egusers_table('egusers_table');

        // Define SQL here.
        $fields = "m.*";

        $from = "(SELECT " . $DB->sql_concat("c.id", "'-'", "u.id") .
                " AS uniqueid,
                c.id courseid, c.fullname, l.timeaccess,
                u.id userid, u.firstname, u.lastname,
                s1.pbl, s4.pp, s5.tenant,
                CASE
                    WHEN s6.ispartner IS NULL THEN '0'
                    ELSE s6.ispartner
                END ispartner,
                s2.complcount, s3.modcount
                FROM {course} c
                JOIN {enrol} e ON c.id = e.courseid
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {user_lastaccess} l
                ON l.userid = u.id AND l.courseid = c.id
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
                    SELECT cm.course, cmc.userid, COUNT(cmc.completionstate) AS complcount
                    FROM {course_modules} cm
                    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
                    WHERE cmc.completionstate = 1
                    GROUP BY cm.course, cmc.userid
                ) s2
                ON s2.userid = u.id AND s2.course = c.id
                LEFT JOIN (
                    SELECT course, count(*) modcount
                    FROM {course_modules}
                    WHERE visible = 1 AND completion > 0
                    GROUP BY course
                ) s3
                ON s3.course = c.id
                LEFT JOIN (
                    SELECT userid, data AS pp
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    -- Partnerprogramm, use pattern to be safe.
                    WHERE shortname LIKE '%artner%ogram%'
                    LIMIT 1)
                ) s4
                ON s4.userid = u.id
                LEFT JOIN (
                    SELECT userid, data AS tenant
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE shortname = 'tenant'
                    LIMIT 1)
                ) s5
                ON s5.userid = u.id
                LEFT JOIN (
                    SELECT userid, data AS ispartner
                    FROM {user_info_data} uid
                    WHERE fieldid = (SELECT id
                    FROM {user_info_field} uif
                    WHERE shortname = 'ispartner'
                    LIMIT 1)
                ) s6
                ON s6.userid = u.id
                WHERE c.id $insql
            ) m";

        // Determine the $where part.
        $where = "1=0"; // By default, we show nothing.
        if (has_capability('local/wb_reports:admin', $syscontext)) {

            // Admins of Wunderbyte reports will always be allowed to see everything.
            $where = "1=1";

            // For report admins, we always show PBLs.
            $showpbls = true;

        } else if (has_capability('local/wb_reports:view', $syscontext)) {
            $where = "1=1";
            $showpbls = false; // By default, we do not show PBLs for non-admins.

            // Else we need to check if the logged-in user has the right to view the users.
            // There is a custom user profile field called 'allowedpbls' storing the PBLs...
            // ...for users which the user is allowed to see.
            $user = $USER;
            profile_load_custom_fields($user);

            if (!empty($user->profile['allowedpbls'])) {
                $allowedpbls = $user->profile['allowedpbls'];
                $allowedpbls = str_replace(' ', '', $allowedpbls);
                $pbls = preg_split('/\s+/', $allowedpbls, -1, PREG_SPLIT_NO_EMPTY);
                // By default, a user can see all users having the same PBL as himself, so add it if it exists.
                if (!empty($user->profile['partnerid'])) { // Shortname for PBL is partnerid.
                    $pbls[] = $user->profile['partnerid'];
                }
                if (!empty($pbls)) {
                    [$inpbls, $inparams2] = $DB->get_in_or_equal($pbls, SQL_PARAMS_NAMED, 'pbl');
                    $where .= " AND m.pbl $inpbls";

                    // If it's no admin, we only show the PBLs if there is more than one.
                    // So we need a flag for this.
                    if (count($pbls) > 1) {
                        $showpbls = true;
                    }
                }
            } else {
                // No allowedpbls, so use only the PBL of the user himself.
                if (!empty($user->profile['partnerid'])) { // Shortname for PBL is partnerid.
                    $pbl = $user->profile['partnerid'];
                    $where .= " AND m.pbl = '{$pbl}'";
                }
            }

            // A user needs to be part of a tenant, e.g. "Esso".
            if (empty($tenant = $user->profile['tenant'])) {
                $where = "1=0"; // A user without a tenant cannot see anything in this report.
            } else {
                $where .= " AND m.tenant = '{$tenant}'";
            }
        }

        // Headers.
        $headers = [];
        $headers[] = get_string('coursename', 'local_wb_reports');
        $headers[] = get_string('lastaccess', 'local_wb_reports');
        $headers[] = get_string('firstname', 'core');
        $headers[] = get_string('lastname', 'core');
        if ($showpbls) {
            $headers[] = get_string('pbl', 'wbreport_egusers');
        }
        if (has_capability('local/wb_reports:admin', $syscontext)) {
            $headers[] = get_string('tenant', 'wbreport_egusers');
            $headers[] = get_string('pp', 'wbreport_egusers');
            $headers[] = get_string('ispartner', 'wbreport_egusers');
        }
        $headers[] = get_string('complcount', 'wbreport_egusers');
        $table->define_headers($headers);

        // Columns.
        $columns = [];
        $columns[] = 'fullname';
        $columns[] = 'timeaccess';
        $columns[] = 'firstname';
        $columns[] = 'lastname';
        if ($showpbls) {
            $columns[] = 'pbl';
        }
        if (has_capability('local/wb_reports:admin', $syscontext)) {
            $columns[] = 'tenant';
            $columns[] = 'pp';
            $columns[] = 'ispartner';
        }
        $columns[] = 'complcount';
        $table->define_columns($columns);

        // Merge params.
        $params = array_merge($inparams1, $inparams2);

        $table->set_filter_sql($fields, $from, $where, '', $params ?? []);

        $table->sortable(true, 'fullname', SORT_ASC);

        // Define Filters.
        $standardfilter = new standardfilter('fullname', get_string('coursename', 'local_wb_reports'));
        $table->add_filter($standardfilter);

        if ($showpbls) {
            $standardfilter = new standardfilter('pbl', get_string('pbl', 'wbreport_egusers'));
            $table->add_filter($standardfilter);
        }

        if (has_capability('local/wb_reports:admin', $syscontext)) {
            $standardfilter = new standardfilter('tenant', get_string('tenant', 'wbreport_egusers'));
            $table->add_filter($standardfilter);

            $standardfilter = new standardfilter('pp', get_string('pp', 'wbreport_egusers'));
            $table->add_filter($standardfilter);

            $standardfilter = new standardfilter('ispartner', get_string('ispartner', 'wbreport_egusers'));
            $standardfilter->add_options([
                '1' => '✅',
                '0' => '❌',
            ]);
            $table->add_filter($standardfilter);
        }

        $standardfilter = new standardfilter('complcount', get_string('complcount', 'wbreport_egusers'));
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

        // Full text search columns.
        $fulltextsearchcols = [];
        $fulltextsearchcols[] = 'fullname';
        $fulltextsearchcols[] = 'firstname';
        $fulltextsearchcols[] = 'lastname';
        if ($showpbls) {
            $fulltextsearchcols[] = 'pbl';
        }
        if (has_capability('local/wb_reports:admin', $syscontext)) {
            $fulltextsearchcols[] = 'tenant';
            $fulltextsearchcols[] = 'pp';
            $fulltextsearchcols[] = 'ispartner';
        }
        $table->define_fulltextsearchcolumns($fulltextsearchcols);

        // Sortable columns.
        $sortablecols = [];
        $sortablecols[] = 'fullname';
        $sortablecols[] = 'firstname';
        $sortablecols[] = 'lastname';
        if ($showpbls) {
            $sortablecols[] = 'pbl';
        }
        if (has_capability('local/wb_reports:admin', $syscontext)) {
            $sortablecols[] = 'tenant';
            $sortablecols[] = 'pp';
            $sortablecols[] = 'ispartner';
        }
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
            get_string('infotext:tableheader', 'wbreport_egusers') .
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
