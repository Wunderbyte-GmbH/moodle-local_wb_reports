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
 * Library functions for wbreport_egareamanager.
 *
 * @package    wbreport_egareamanager
 * @copyright  2024 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The area manager report is visible to users who have at least one PBL
 * in their "allowedpbls" custom profile field, independent of the local/wb_reports capabilities.
 *
 * @param \stdClass $user
 * @param \context $context
 * @return bool
 */
function wbreport_egareamanager_is_visible_in_navbar(\stdClass $user, \context $context): bool {
    global $CFG;
    require_once($CFG->dirroot . '/user/profile/lib.php');

    // Called on every page via the navbar, so do not rely on class autoloading here
    // (it fails while the component cache is stale, e.g. right after a checkout).
    $customfields = profile_user_record($user->id, false);
    $raw = (string) ($customfields->allowedpbls ?? '');
    return !empty(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY));
}
