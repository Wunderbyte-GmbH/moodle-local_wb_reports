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

namespace local_wb_reports\output;
use plugin_renderer_base;
use local_wb_reports\plugininfo\wbreport_interface;

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /** Function to render the dashboard
     * @param dashboard $data
     * @return string
     */
    public function render_dashboard(dashboard $data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_wb_reports/dashboard', $data);
        return $o;
    }

    /** Function to render a report
     * @param wbreport_interface $data
     * @return string
     */
    public function render_report(wbreport_interface $data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('local_wb_reports/report', $data);
        return $o;
    }
}
