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
            array(
                'contextid' => new \external_value(PARAM_INT, 'The context id for the course'),
                'formdata' => new \external_value(PARAM_RAW, 'The data from the user notes'),
            )
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

    /**
     * Bulk manage instances on the instance managemente table.
     *
     * @return array bulk actions parameters.
     */
    public static function manage_instances_parameters() {

        return new \external_function_parameters(
            [
                'templateid' => new \external_value(PARAM_INT, 'The automation template id'),
                'courseids' => new \external_multiple_structure(new \external_value(PARAM_INT, 'course ID',
                        VALUE_REQUIRED, '', NULL_NOT_ALLOWED), 'Array of Course IDs', VALUE_DEFAULT, array()),
                'action' => new \external_value(PARAM_TEXT, 'The bulk action'),
            ]
        );
    }

    /**
     * Manage the instance on the selected courses use the bulk action in the instance management table.
     * 
     * @param int $templateid Automation template ID.
     * @param array $courseids Course ID.
     * 
     * @return array $message
     */
    public static function manage_instances(int $templateid, array $courseids, string $action) {
        global $DB;

        $vaildparams = self::validate_parameters(self::manage_instances_parameters(), 
        ['templateid' => $templateid, 'courseids' => $courseids, 'action' => $action]);
        
        $message = '';
        switch ($vaildparams['action']) {

            case 'delete':
                if (!empty($vaildparams['courseids'])) {
                    foreach ($vaildparams['courseids'] as $courseid) {
                        $management = \mod_pulse\automation\manage::create($templateid, $courseid);
                        if ($management->delete_course_instance()) {
                            $message = get_string('templatedeleted', 'pulse');
                        }
                    }
                }
            break;

            case 'add':
                if (!empty($vaildparams['courseids'])) {
                    foreach ($vaildparams['courseids'] as $courseid) {
                        $management = \mod_pulse\automation\manage::create($templateid, $courseid);
                        if ($management->add_course_instance()) {
                            $message = get_string('templateinsertsuccess', 'pulse');
                        }
                    }
                }
            break;

            case 'disable':
                if (!empty($vaildparams['courseids'])) {
                    foreach ($vaildparams['courseids'] as $courseid) {
                        $management = \mod_pulse\automation\manage::create($templateid, $courseid);
                        if ($management->update_instance_status(false)) {
                            $message = get_string('templatedisablesuccess', 'pulse');
                        }
                    }
                }
            break;

            case 'enable':
                if (!empty($vaildparams['courseids'])) {
                    foreach ($vaildparams['courseids'] as $courseid) {
                        $management = \mod_pulse\automation\manage::create($templateid, $courseid);
                        if ($management->update_instance_status(true)) {
                            $message = get_string('templateenablesuccess', 'pulse');
                        }
                    }
                }
            break;
        }
        return [
            'message' => $message
        ];
    }

    /**
     * Retuns the redirect message for manage instances.
     *
     * @return array message.
     */
    public static function manage_instances_returns() {
        return new \external_single_structure(
            [
                'message' => new \external_value(PARAM_TEXT, 'Return status message'),
            ]
        );
    }

}
