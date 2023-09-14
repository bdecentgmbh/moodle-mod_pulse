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
 * Mod pulse - Edit template.
 *
 * @package    mod_pulse
 * @copyright  2023 bdecent GmbH <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pulse\automation\condition_base;

// Require config.
require(__DIR__.'/../../../../config.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

require_sesskey();

// Automation template ID to edit.
$templateid = optional_param('templateid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$instanceid = optional_param('instanceid', null, PARAM_INT);

if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
} else {
    $instanceid = required_param('instanceid', PARAM_INT);
    if ($instance = $DB->get_record('pulse_autoinstances', ['id' => $instanceid], '*', MUST_EXIST)) {
        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
    }
}

// Extend the features of admin settings.
// admin_externalpage_setup('pulseautomation');

// Create the page url.
$url = new moodle_url('/mod/pulse/automation/instances/edit.php', ['sesskey' => sesskey()]);

// Include the instance id to the page url params.
if ($instanceid) {
    $url->param('instanceid', $instanceid);
}
// Include the course id to the page url params if exists.
if (isset($course->id)) {
    $url->param('courseid', $course->id);
}

if ($templateid) {
    $url->params(['templateid' => $templateid]);
}

// Page values.
$context = \context_course::instance($course->id);
// Verify the user capability.
require_capability('mod/pulse:addtemplateinstance', $context);

// Setup page values.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course(get_course($course->id));

// Edit automation templates form.
$templatesform = new \mod_pulse\forms\automation_instance_form(null, ['templateid' => $templateid, 'courseid' => $course->id, 'instanceid' => $instanceid]);

// Instance list page url for this course.
$overviewurl = new moodle_url('/mod/pulse/automation/instances/list.php', ['courseid' => $course->id, 'sesskey' => sesskey()]);

// Instance form submitted, handel the submitted data.
if ($formdata = $templatesform->get_data()) {
    $result = mod_pulse\automation\instances::manage_instance($formdata);
    // Redirect to instances list.
    redirect($overviewurl);

} else if ($templatesform->is_cancelled()) {
    // Form cancelled redirect to list page.
    redirect($overviewurl);
}

// Setup the tempalte data to the form, if the form id param available.
if ($instanceid !== null && $instanceid > 0) {

    if ($record = mod_pulse\automation\instances::create($instanceid)->get_instance_formdata()) {
        // print_object($record); exit;
        // Set the template data to the templates edit form.
        $templatesform->set_data($record);
    } else {
        // Direct the user to list page with error message, when the requested menu is not available.
        \core\notification::error(get_string('templatesrecordmissing', 'pulse'));
        redirect($overviewurl);
    }

} else {
    // Instance not created and initiated the new instance from the template,
    // Then fetch the template data with actions data and assign to the template form.
    $courseid = required_param('courseid', PARAM_INT);
    $templateid = required_param('templateid', PARAM_INT);

    if ($record = mod_pulse\automation\templates::create($templateid)->get_template()) {
        // Attach the course id to the templates.
        $record->courseid = $courseid;
        // Convert the trigger conditions to separate element.
        $conditions = $record->triggerconditions;
        foreach ($conditions as $condition) {
            $record->{'condition['.$condition.'][status]'} = condition_base::ALL;
        }
        // Set the template data to the templates edit form.
        $templatesform->set_data($record);
        // exit;
    } else {
        // Direct the user to list page with error message, when the requested template instance is not available.
        \core\notification::error(get_string('templatesrecordmissing', 'pulse'));
        redirect($overviewurl);
    }

}
// Template edit page heading.
$PAGE->set_heading(format_string($course->fullname));
// PAGE breadcrumbs.
$PAGE->navbar->add(get_string('mycourses', 'core'), new moodle_url('/course/index.php'));
$PAGE->navbar->add(format_string($course->shortname), new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add(get_string('autotemplates', 'pulse'), new moodle_url('/mod/pulse/automation/instances/list.php', ['courseid' => $course->id]));
$PAGE->navbar->add(get_string('autoinstances', 'pulse'));

// Page content display started.
echo $OUTPUT->header();

// Template heading.
echo $OUTPUT->heading(get_string('editinstance', 'pulse'));

// Display the template form for create or edit.
echo $templatesform->display();

// Footer.
echo $OUTPUT->footer();
