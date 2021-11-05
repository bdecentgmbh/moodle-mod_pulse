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
 * Pulse instance Presets feature test cases defined.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

/**
 * Pulse resource preset create and customize settings phpunit test cases defined.
 */
class mod_pulse_preset_testcase extends advanced_testcase {

    /**
     * Setup the course and admin user to test the presets.
     *
     * @return void
     */
    public function setUp(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course(null, ['createsections' => true]);
        $this->coursecontext = \context_course::instance($this->course->id);
    }

    /**
     * Test case to insert method for presets, this function creates the default already defined presets
     * which is shipped with plugin source installation.
     *
     * @return void
     */
    public function test_pulse_create_presets() {
        global $DB;
        pulse_create_presets();

        $records = $DB->get_records('pulse_presets');
        $this->assertCount(3, $records);
        $this->assertEquals('Welcome Message', reset($records)->title);
    }

    /**
     * Test the create pulse activity using the preset method apply and save with custom configurable params.
     *
     * @return void
     */
    public function test_apply_save_preset(): void {
        global $DB;
        pulse_create_presets();
        $records = $DB->get_records('pulse_presets');
        $record = reset($records);

        $preset = new mod_pulse\preset($record->id, $this->course->id, $this->coursecontext);
        $configdata = ['importmethod' => 'save', 'presetid' => $record->id, 'name' => 'Welcome Message'];
        $result = $preset->apply_presets($configdata);
        $result = json_decode($result);
        $courseurl = new \moodle_url('/course/view.php', ['id' => $this->course->id]);
        $this->assertEquals($courseurl, $result->url);

        $cm = $DB->get_record('pulse', ['id' => $result->pulseid]);
        $this->assertEquals('Welcome Message', $cm->name);
    }

    /**
     * Test the create pulse activity using the preset method apply and customize with custom configurable params.
     *
     * @return void
     */
    public function test_apply_customize_preset() {
        global $DB;
        pulse_create_presets();
        $records = $DB->get_records('pulse_presets');
        $record = reset($records);
        $customname = 'Welcome Message';
        $subject = 'Preset pulse subject - customize';
        $preset = new mod_pulse\preset($record->id, $this->course->id, $this->coursecontext);

        $configdata = [
            'importmethod' => 'customize', 'presetid' => $record->id, 'name' => $customname, 'pulse_subject' => $subject
        ];
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'pulse']);
        $pageparams = [
            'section' => '1', 'add' => 'pulse', 'module' => $moduleid,
            'modulename' => 'pulse', 'id' => '', 'instance' => '', 'update' => 0
        ];
        $preset->set_modformdata($pageparams);
        $result = $preset->apply_presets($configdata);
        $this->assertEquals($customname, $preset->pulseform->_form->_defaultValues['name']);
        $this->assertEquals($subject, $preset->pulseform->_form->_defaultValues['pulse_subject']);
    }
}
