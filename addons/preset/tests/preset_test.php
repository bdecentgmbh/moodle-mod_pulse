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
 * Pulse instance test cases defined.
 *
 * @package   pulseaddon_preset
 * @copyright 2024 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaddon_preset;

/**
 * Pulse resource phpunit test cases defined.
 */
final class preset_test extends \advanced_testcase {

    /**
     * Course instance data
     *
     * @var stdclass
     */
    public $course;

    /**
     * Pulsepro test content generator.
     *
     * @var stdclass
     */
    public $generator;

    /**
     * Course module instance data
     *
     * @var stdclass
     */
    public $cm;

    /**
     * Course context data
     *
     * @var \context_course
     */
    public $coursecontext;

    /**
     * Setup the course and admin user to test the presets.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->coursecontext = \context_course::instance($this->course->id);
        $this->generator = $this->getDataGenerator()->get_plugin_generator('pulseaddon_preset');
    }

    /**
     * Test pulsepro create preset insert the pro presets.
     * @covers ::pulseaddon_preset\instance::create_presets
     *
     * @return void
     */
    public function test_create_demo_preset(): void {
        global $DB;

        \pulseaddon_preset\instance::create_presets();
        $records = $DB->get_records('pulse_presets');
        $this->assertCount(2, $records);
        $this->assertEquals('Demo pro preset 1', reset($records)->title);
    }

    /**
     * Returns an array of reminder fields.
     *
     * @return array List of reminder fields.
     */
    public function reminder_fields() {
        return [
            'invitation_recipients', 'first_reminder', 'first_subject', 'first_content_editor', 'first_recipients',
            'first_schedule_arr', 'first_fixeddate', 'first_relativedate', 'second_reminder', 'second_subject',
            'second_content_editor', 'second_recipients', 'second_schedule_arr', 'second_fixeddate', 'second_relativedate',
            'recurring_reminder', 'recurring_subject', 'recurring_content_editor',
            'recurring_recipients', 'recurring_relativedate',
        ];
    }

    /**
     * Test is all the pulsepro fields are fetched for custom configurable fields.
     * @covers ::pulsepro_fields
     * @return void
     */
    public function test_get_config_list(): void {
        $presetform = new \pulseaddon_preset\presets\preset_form(); // Dont remove this.
        $fields = \pulseaddon_preset\presets\preset_form::get_pulse_config_list();

        $exists = !array_diff($this->reminder_fields(), array_keys($fields));
        $this->assertTrue($exists);

        $exists = !array_diff(['options[reactiontype]', 'options[reactiondisplay]'], array_keys($fields));
        $this->assertTrue($exists);
    }

    /**
     * Test apply and save method updates the pulse pro fields custom config options.
     * @covers ::apply_save_preset
     *
     * @return void
     */
    public function test_apply_save_preset(): void {
        global $DB;
        $this->generator->create_presets();
        $records = $DB->get_records('pulse_presets');
        $record = reset($records);

        $preset = new \mod_pulse\preset($record->id, $this->course->id, $this->coursecontext);
        $configdata = ['importmethod' => 'save', 'presetid' => $record->id];
        $prodata = [
            'first_content' => 'First reminder - test case content',
            'second_content' => 'Second reminder - test case content',
        ];
        $result = $preset->apply_presets($configdata + $prodata);
        $result = json_decode($result);
        $courseurl = new \moodle_url('/course/view.php', ['id' => $this->course->id]);
        $this->assertEquals($courseurl, $result->url);

        $cm = $DB->get_record('pulseaddon_reminder', ['pulseid' => $result->pulseid]);
        foreach ($prodata as $key => $data) {
            $this->assertEquals($data, $cm->$key);
        }
    }
}
