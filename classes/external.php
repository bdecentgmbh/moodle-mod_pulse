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
 * Load preset fragments.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');

/**
 * Pulse preset external definitions.
 */
class external extends \external_api {

    /**
     * Create module using selected preset template with the preset configdata.
     *
     * @return void
     */
    public static function apply_presets_parameters() {

        return new \external_function_parameters(
            [
                'contextid' => new \external_value(PARAM_INT, 'The context id for the course'),
                'formdata' => new \external_value(PARAM_RAW, 'The data from the user notes'),
            ]
        );
    }

    /**
     * Service helps to replace the current add module with preset template data.
     *
     * @param int $contextid Id for module/course context.
     * @param string $formdata Custom configurable fields data.
     * @param string $pageparams Current module form data.
     * @return string
     */
    public static function apply_presets(int $contextid, string $formdata, $pageparams = null) {
        global $PAGE;
        parse_str($formdata, $data);
        foreach ($data as $key => $value) {
            if (strpos($key, 'preseteditor_') !== false) {
                $newkey = str_replace('preseteditor_', '', $key);
                $data[$newkey] = $value;
                unset($data[$key]);
            }
        }
        $context = \context::instance_by_id($contextid);
        $PAGE->set_context($context);
        $preset = new \mod_pulse\preset($data['presetid'], $data['courseid'], $context);
        if ($pageparams !== null) {
            parse_str($pageparams, $params);
            $preset->set_modformdata($params);
        }
        $result = $preset->apply_presets($data);
        return $result;
    }

    /**
     * Retuns the redirect course url and created pulse id for save method.
     *
     * @return void
     */
    public static function apply_presets_returns() {
        return new \external_value(PARAM_RAW, 'Count of Page user notes');
    }
}
