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
 * @package     wbreport_egregionalmanager
 * @copyright   2024 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use wbreport_egregionalmanager\output\egregionalmanager;

require_once(__DIR__ . '/../../../../config.php');

// No guest autologin.
require_login(0, false);

global $DB, $PAGE, $OUTPUT, $USER;

if (!$context = context_system::instance()) {
    throw new moodle_exception('badcontext');
}

// If it's no WB reports admin, we have to check for the view capability.
if (!has_capability('local/wb_reports:admin', $context)) {
    require_capability('local/wb_reports:view', $context);
}

$PAGE->set_context($context);
$title = get_string('pluginname', 'wbreport_egregionalmanager');
$pagetitle = $title;
$url = new moodle_url("/local/wb_reports/wbreport/egregionalmanager/report.php");

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading(get_string('pluginname', 'wbreport_egregionalmanager'));

$output = $PAGE->get_renderer('local_wb_reports');

echo $output->header();

$data = new egregionalmanager();
echo $output->render_report($data);

echo $output->footer();
