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
 * Override user groups in modinfo. Group availablity condition doesn't check the passed user groups.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Custom restore step to fetch the data from preset templates extracted XML files.
 */
class preset_customize_restore extends restore_structure_step {

    /**
     * Data fetched from XML.
     *
     * @var array
     */
    public $data;

    /**
     * Structure definition for the path elements to restore. Using normal xml parse methods moodle raise the permission issue.
     *
     * @return array List of element paths to restore.
     */
    public function define_structure() {
        $module = new restore_path_element('module', '/module');
        $paths[] = $module;
        $paths[] = new restore_path_element('pulse', '/activity/pulse');
        if ($this->get_name() == 'pulsepro') {
            $paths[] = new restore_path_element('local_pulsepro', '/activity/pulse/pulse_pro/local_pulsepro');
        }

        return $paths;
    }

    /**
     * Process the module element restore.
     * When the module element processed then the data is set as class level using this we can able to fetch the data from XML.
     *
     * @param array $data
     * @return void
     */
    public function process_module($data) {
        $this->data = $data;
    }

    /**
     * Process the pusle element restore.
     * When the pluse element processed then the data is set as class level using this we can able to fetch the pulse data from XML.
     *
     * @param array $data
     * @return void
     */
    public function process_pulse($data) {
        $this->data = $data;
    }

    /**
     * Process the puslepro element restore.
     * The data is set as class level using this we can able to fetch the pulsepro data from XML.
     *
     * @param array $data
     * @return void
     */
    public function process_local_pulsepro($data) {
        $this->data = $data;
    }
}
