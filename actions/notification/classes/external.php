<?php

namespace pulseaction_notification;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

require_once($CFG->libdir.'/externallib.php');

class external extends \external_api {

     /**
     * Get list of chapters for the book module function parameters.
     * @return object type of the badge type.
     */
    public static function get_chapters_parameters() {
        return new \external_function_parameters(
            array('mod' => new \external_value(PARAM_INT, 'Book module cmid ', VALUE_OPTIONAL))
        );
    }

    /**
     * Get list of badges based on the requested type.
     *
     * @param  string $type Type of badge.
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
                array(
                    'value' => new \external_value(PARAM_INT, 'Chapter ID', VALUE_OPTIONAL),
                    'label' => new \external_value(PARAM_TEXT, 'Chapter title', VALUE_OPTIONAL),
                )
            ), '', VALUE_OPTIONAL
        );
    }

}
