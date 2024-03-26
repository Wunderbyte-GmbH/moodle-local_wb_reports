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
 * Interface for a single report.
 *
 * All reports must extend this class.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_wb_reports\plugininfo;

use renderer_base;
use stdClass;

/**
 * Interface for a single report.
 *
 * All reports must extend this class.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface wbreport_interface {

    /**
     * Export this data so it can be used as the context for a mustache template.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass;

    /**
     * Use this function to render any HTML in the report header.
     * @return string the html for the table header
     * */
    public function get_table_header_html(): string;

}
