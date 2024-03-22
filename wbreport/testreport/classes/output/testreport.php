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
 * Wunderbyte reports: testreport class.
 *
 * @package     wbreport_testreport
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_testreport\output;

use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;
use stdClass;
use local_wunderbyte_table\filters\types\standardfilter;
use renderer_base;
use renderable;
use templatable;
use wbreport_testreport\table\testreport_table;

/**
 * This class prepares data for the report.
 *
 * @package     wbreport_testreport
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testreport implements renderable, templatable, wbreport_interface {

    /**
     * @var array $tabledata
     */
    private $tabledata = [];

    /**
     * In the constructor, we gather all the data we need.
     */
    public function __construct() {

        // Create instance of transactions wb_table and specify columns and headers.
        $table = new testreport_table('testreport_table');

        // Headers.
        $table->define_headers([
            'ID',
            get_string('firstname', 'core'),
            get_string('lastname', 'core'),
        ]);

        // Columns.
        $table->define_columns(['id', 'firstname', 'lastname']);

        // Define SQL here.
        $fields = 'id, firstname, lastname';
        $from = '{user}';
        $where = '1 = 1';

        $table->set_filter_sql($fields, $from, $where, '');

        $table->sortable(true, 'lastname', SORT_ASC);

        // Define Filters.
        $standardfilter = new standardfilter('firstname', get_string('firstname', 'core'));
        $table->add_filter($standardfilter);

        $standardfilter = new standardfilter('lastname', get_string('lastname', 'core'));
        $table->add_filter($standardfilter);

        // Full text search columns.
        $table->define_fulltextsearchcolumns(['id', 'firstname', 'lastname']);

        // Sortable columns.
        $table->define_sortablecolumns(['id', 'firstname', 'lastname']);

        $table->define_cache('wbreport_testreport', 'wbreportstestreportcache');

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
