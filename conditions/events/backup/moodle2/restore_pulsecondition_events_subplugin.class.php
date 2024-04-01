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
 * This file contains the class for restore of this pulse condition events plugin
 *
 * @package   pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore pulse action subplugin class.
 *
 */
class restore_pulsecondition_events_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin.
     * @return array
     */
    protected function define_pulse_autoinstances_subplugin_structure() {

        $paths = array();

        // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element('pulsecondition_events',
            '/activity/pulse_autoinstances/notificationcondition/pulsecondition_events'
        );

        return $paths;
    }

    /**
     * Processes one pulsecondition_events element
     * @param mixed $data
     * @return void
     */
    public function process_pulsecondition_events($data) {
        global $DB;

        $data = (object) $data;
        // Get the new template id.
        $data->instanceid = $this->get_new_parentid('pulse_autoinstances');

        if (!$DB->record_exists('pulsecondition_events', ['instanceid' => $data->instanceid])) {
            $DB->insert_record('pulsecondition_events', $data);
        }

    }
}
