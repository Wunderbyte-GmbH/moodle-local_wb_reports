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
 * Add dates to option.
 *
 * @package     local_wb_reports
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer-Sengseis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_wb_reports\output\dashboard;

require_once(__DIR__ . '/../../config.php');

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
$PAGE->set_url('/local/wb_reports/dashboard.php');

$title = get_string('pluginname', 'local_wb_reports');
$PAGE->navbar->add($title);
$PAGE->set_title(format_string($title));
$PAGE->set_heading($title);

$PAGE->set_pagelayout('standard');
if (is_siteadmin()) {
    $PAGE->add_body_class('wb_isadmin');
}
$PAGE->add_body_class('local_wb_reports-dashboard');

echo $OUTPUT->header();

// Render the page content via mustache templates.
$output = $PAGE->get_renderer('local_wb_reports');
$data = new dashboard();
echo $output->render_dashboard($data);

echo $OUTPUT->footer();
