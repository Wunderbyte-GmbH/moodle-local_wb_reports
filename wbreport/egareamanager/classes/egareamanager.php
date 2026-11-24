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
 * Class egareamanager.
 *
 * @package     wbreport_egareamanager
 * @copyright   2024 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @author      Thomas Winkler
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace wbreport_egareamanager;

use local_wb_reports\plugininfo\wbreport;
use local_wb_reports\plugininfo\wbreport_interface;

class egareamanager extends wbreport implements wbreport_interface {

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
    public static function get_area_pbls(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $customfields = profile_user_record($userid, false);
        $raw = $customfields->{self::PBLFIELD} ?? '';
        if (empty($raw)) {
            return [];
        }
        $pbls = preg_split('/[\\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique($pbls));
    }
}
