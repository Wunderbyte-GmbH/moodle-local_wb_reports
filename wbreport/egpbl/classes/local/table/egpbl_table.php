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

namespace wbreport_egpbl\local\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/tablelib.php');

use local_wunderbyte_table\wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Definitions for transactionstable iteration of wb_table
 */
class egpbl_table extends wunderbyte_table {

    /**
     * This function is called for each data row to allow processing of the
     * "timeaccess" (=last access) value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string containing the start date
     */
    public function col_timeaccess($values) {
        $lastaccess = $values->timeaccess;
        if (empty($lastaccess)) {
            return '';
        }
        switch (current_language()) {
            case 'de':
                $renderedlastaccess = date('d.m.Y', $lastaccess);
                break;
            default:
                $renderedlastaccess = date('M d, Y', $lastaccess);
                break;
        }
        return $renderedlastaccess;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "complcount" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string showing the completion count
     */
    public function col_complcount($values) {
        $complcount = $values->complcount ?? 0; // Number of completed modules.
        $modcount = $values->modcount ?? 0; // Number of modules.
        if (empty($complcount) && empty($modcount)) {
            return '';
        }
        if ($this->is_downloading()) {
            return "$complcount/$modcount";
        }
        $ret = '';
        if (!empty($complcount) && $complcount > 0) {
            $ret .= str_repeat('✅ ', $complcount);
            $ret .= str_repeat('◯ ', $modcount - $complcount);
        } else if (!empty($modcount) && $modcount > 0) {
            $ret .= str_repeat('◯ ', $modcount);
        }

        return $ret;
    }

    /**
     * This function is called for each data row to allow processing of the
     * "ispartner" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string showing the completion count
     */
    public function col_ispartner($values) {
        $ispartner = $values->ispartner;
        if ($this->is_downloading()) {
            return $ispartner;
        }
        if ($ispartner == '1') {
            $ret = '✅';
        } else {
            $ret = '❌';
        }

        return $ret;
    }
}
