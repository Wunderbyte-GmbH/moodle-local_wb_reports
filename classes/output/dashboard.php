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
 * Wunderbyte reports dashboard class.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_reports\output;

use core_plugin_manager;
use renderer_base;
use renderable;
use templatable;

/**
 * This class prepares data for the Wunderbyte Reports dashboard.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements renderable, templatable {

    /** @var array $data */
    public $data = null;

    /**
     * In the Constructor, we gather all the data we need ans store it in the data property.
     */
    public function __construct() {

        $data = [];
        $data['reports'] = [];

        foreach (core_plugin_manager::instance()->get_plugins_of_type('wbreport') as $plugin) {
            $report['title'] = $plugin->get_report_title($plugin->name);
            $report['description'] = $plugin->get_report_description($plugin->name);
            $report['link'] = $plugin->get_report_link($plugin->name);

            $data['reports'][] = $report;
        }

        $this->data = $data;
    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        return $this->data;
    }
}
