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
 * This file contains the class for backup of this submission plugin
 *
 * @package pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup submission files
 *
 * This just adds its filearea to the annotations and records the number of files
 *
 * @package pulsecondition_events
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_pulsecondition_events_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element
     */
    protected function define_pulse_autoinstances_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();

        // Automation templates.
        $events = new \backup_nested_element('pulseconditionevents');
        $eventsfields = new \backup_nested_element('pulsecondition_events', ['id'], [
            "instanceid", "eventname", "notifyuser",
        ]);

        $subplugin->add_child($events);
        $events->add_child($eventsfields);

        $eventsfields->set_source_table('pulsecondition_events', ['instanceid' => \backup::VAR_PARENTID]);

        return $subplugin;
    }

}
