<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Helper to read the PBLs of an area manager.
 *
 * @package     wbreport_egareamanager
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_egareamanager\local;

/**
 * Helper to read the PBLs of an area manager.
 */
class areapbls {

    /** @var string Shortname of the custom profile field holding the PBLs of the area manager's field. */
    const PBLFIELD = 'allowedpbls';

    /**
     * Returns the PBLs an area manager is responsible for.
     *
     * The values in the profile field may be separated by spaces, commas, semicolons or line breaks.
     *
     * @param int $userid
     * @return array list of unique PBLs, empty if the user is no area manager
     */
    public static function get(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $customfields = profile_user_record($userid, false);
        $raw = (string) ($customfields->{self::PBLFIELD} ?? '');
        $pbls = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique($pbls));
    }
}
