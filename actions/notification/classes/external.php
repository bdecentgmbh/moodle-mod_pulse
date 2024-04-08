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
 * Notification pulse action external functions defined.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulseaction_notification;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');

/**
 * Pulse Notification action external methods.
 */
class external extends \external_api {

    /**
     * Get list of chapters for the book module function parameters.
     * @return object type of the badge type.
     */
    public static function get_chapters_parameters() {
        return new \external_function_parameters(
            ['mod' => new \external_value(PARAM_INT, 'Book module cmid ', VALUE_OPTIONAL)]
        );
    }

    /**
     * Get list of badges based on the requested type.
     *
     * @param  string $mod ID of the course module.
     * @return array $type List of badge types.
     */
    public static function get_chapters($mod = null) {
        global $CFG;

        if (isset($mod)) {
            $cmid = $mod;
            $chapters = \pulseaction_notification\notification::load_book_chapters($cmid);
            foreach ($chapters as $chapterid => $chapter) {
                $list[] = ['value' => $chapter->id, 'label' => $chapter->title];
            }
        }

        return $list ?? [];
    }

    /**
     * Return chapters list data definition.
     *
     * @return array list of chapaters.
     */
    public static function get_chapters_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                [
                    'value' => new \external_value(PARAM_INT, 'Chapter ID', VALUE_OPTIONAL),
                    'label' => new \external_value(PARAM_TEXT, 'Chapter title', VALUE_OPTIONAL),
                ]
            ), '', VALUE_OPTIONAL
        );
    }

}
