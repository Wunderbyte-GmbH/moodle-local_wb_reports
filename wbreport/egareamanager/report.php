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
 * @package     wbreport_egareamanager
 * @copyright   2024 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @author      Thomas Winkler
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use wbreport_egareamanager\output\egareamanager;

require_once(__DIR__ . '/../../../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

$areapbls = \wbreport_egareamanager\local\areapbls::get($USER->id);
if (empty($areapbls)) {
    throw new moodle_exception('noaccess');
}


$PAGE->set_context($context);
$title = get_string('pluginname', 'wbreport_egareamanager');
$pagetitle = $title;
$url = new moodle_url("/local/wb_reports/wbreport/egareamanager/report.php");

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'wbreport_egareamanager') . ': ' . implode(', ', $areapbls));

$output = $PAGE->get_renderer('local_wb_reports');

echo $output->header();

$data = new egareamanager();
echo $output->render_report($data);

echo $output->footer();
