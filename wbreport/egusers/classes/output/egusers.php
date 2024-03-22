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

use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;
use stdClass;
use local_wunderbyte_table\filters\types\standardfilter;
use renderer_base;
use renderable;
use templatable;
use wbreport_egusers\table\egusers_table;

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

        // Create instance of transactions wb_table and specify columns and headers.
        $table = new egusers_table('egusers_table');

        // Headers.
        $table->define_headers([
            get_string('firstname', 'core'),
            get_string('lastname', 'core'),
            'PBL',
        ]);

        // Columns.
        $table->define_columns(['firstname', 'lastname', 'pbl']);

        // Define SQL here.
        $fields = 'u.id, u.firstname, u.lastname, s1.pbl';
        $from = "{user} u
            LEFT JOIN (
                SELECT userid, data AS pbl
                FROM {user_info_data} uid
                WHERE fieldid = (SELECT id
                FROM {user_info_field} uif
                WHERE name LIKE '%PBL%'
                LIMIT 1)
            ) s1
            ON s1.userid = u.id";
        $where = '1 = 1';

        $table->set_filter_sql($fields, $from, $where, '');

        $table->sortable(true, 'lastname', SORT_ASC);

        // Define Filters.
        $standardfilter = new standardfilter('firstname', get_string('firstname', 'core'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('lastname', get_string('lastname', 'core'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('pbl', 'PBL');
        $table->add_filter($standardfilter);

        // Full text search columns.
        $table->define_fulltextsearchcolumns(['firstname', 'lastname', 'pbl']);

        // Sortable columns.
        $table->define_sortablecolumns(['firstname', 'lastname', 'pbl']);

        $table->define_cache('wbreport_egusers', 'wbreportseguserscache');

        $table->pageable(true);

        // Pass html to render.
        list($idstring, $encodedtable, $html) = $table->lazyouthtml(50, true);
        $this->tabledata = $html;

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
        $data->table = $this->tabledata;
        return $data;
    }
}
