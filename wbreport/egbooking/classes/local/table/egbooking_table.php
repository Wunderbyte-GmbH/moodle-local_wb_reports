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

namespace wbreport_egbooking\local\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/tablelib.php');

use local_wunderbyte_table\wunderbyte_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Definitions for transactionstable iteration of wb_table
 */
class egbooking_table extends wunderbyte_table {

    /**
     * This function is called for each data row to allow processing of the
     * "ispartner" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string showing the completion count
     * @throws coding_exception
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

    /*
     * This function is called for each data row to allow processing of the
     * "bookedoptions" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string showing the completion count
     * @throws coding_exception
     */
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /* public function col_bookedoptions($values) {
        $bookedoptions = $values->bookedoptions;
        if ($this->is_downloading()) {
            return $bookedoptions;
        }
        $ret = str_replace(';', '<br>', $bookedoptions);
        return $ret;
    } */

    /*
     * This function is called for each data row to allow processing of the
     * "canceledoptions" value.
     *
     * @param object $values Contains object with all the values of record.
     * @return string a string showing the completion count
     * @throws coding_exception
     */
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /* public function col_canceledoptions($values) {
        $canceledoptions = $values->canceledoptions;
        if ($this->is_downloading()) {
            return $canceledoptions;
        }
        $ret = str_replace(';', '<br>', $canceledoptions);
        return $ret;
    } */
}
