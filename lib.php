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
 * Pulse instance libarary file. Contains pro feature extended methods
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core_user\output\myprofile\tree;

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

define( 'MAX_PULSE_NAME_LENGTH', 50);

global $PAGE;

require_once($CFG->libdir."/completionlib.php");
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');
require_once($CFG->dirroot.'/mod/pulse/classes/table/manage_instance.php');

/**
 * Add pulse instance.
 *
 * @param  mixed $pulse
 * @return void
 */
function pulse_add_instance($pulse) {
    global $DB;

    $context = context_module::instance($pulse->coursemodule);

    $pulse->timemodified = time();

    if (isset($pulse->pulse_content_editor)) {
        $pulse->pulse_content = file_save_draft_area_files($pulse->pulse_content_editor['itemid'],
                                                    $context->id, 'mod_pulse', 'pulse_content', 0,
                                                    ['subdirs' => true], $pulse->pulse_content_editor['text']);
        $pulse->pulse_contentformat = $pulse->pulse_content_editor['format'];
        unset($pulse->pulse_content_editor);
    }
    // Insert the instance in DB.
    $pulseid = $DB->insert_record('pulse', $pulse);
    // Extend the pro features.
    \mod_pulse\extendpro::pulse_extend_add_instance($pulseid, $pulse);

    // Retrun new instance id.
    return $pulseid;
}

/**
 * Update pulse instance.
 *
 * @param  mixed $pulse formdata in object.
 * @return bool Update record instance result.
 */
function pulse_update_instance($pulse) {
    global $DB;

    $context = context_module::instance($pulse->coursemodule);

    $pulse->id = $pulse->instance;
    $pulse->timemodified = time();
    if (isset($pulse->pulse_content_editor)) {
        // Save pulse content areafiles.
        $pulse->pulse_content = file_save_draft_area_files($pulse->pulse_content_editor['itemid'],
                                                    $context->id, 'mod_pulse', 'pulse_content', 0,
                                                    ['subdirs' => true], $pulse->pulse_content_editor['text']);
        $pulse->pulse_contentformat = $pulse->pulse_content_editor['format'];
        unset($pulse->pulse_content_editor);
    }

    if (!isset($pulse->boxicon) && isset($pulse->displaymode)) {
        $pulse->boxicon = '';
    }

    // If module resend triggred then set the notified status to null for instance.
    if (isset($pulse->resend_pulse)) {
        $DB->set_field('pulse_users', 'status', 0, ['pulseid' => $pulse->id]);
        // Reschedule the notification if resend notification enabled.
        $message = get_string('resendnotificationdesc', 'mod_pulse');
        \core\notification::add($message, 'info');
    }
    // Update instance data.
    $updates = $DB->update_record('pulse', $pulse);
    // Extend the updated module instance pro features.
    \mod_pulse\extendpro::pulse_extend_update_instance($pulse, $context);
    return $updates;
}

/**
 * Delete Pulse instnace
 *
 * @param  mixed $pulseid
 * @return bool
 */
function pulse_delete_instance($pulseid) {
    global $DB;
    if ($DB->record_exists('pulse', ['id' => $pulseid])) {
        $cm = get_coursemodule_from_instance('pulse', $pulseid);

        if ($DB->delete_records('pulse', ['id' => $pulseid])) {
            \mod_pulse\extendpro::pulse_extend_delete_instance($cm->id, $pulseid);
            return true;
        }
    }
    return false;
}

/**
 * Features that supports by pulse module.
 *
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_MOD_PURPOSE
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function pulse_supports($feature) {
    if (defined('FEATURE_MOD_PURPOSE') && $feature == FEATURE_MOD_PURPOSE) {
        return MOD_PURPOSE_ADMINISTRATION;
    }
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return false;
        default:
            return null;
    }
}

/**
 * To make the stealth enable to pulse, it must have view link. So pulse supports the view option on features.
 * On dynamic check, removed the view link. if module hidden from students then pulse should has view support.
 *
 * @param cm_info $cm
 * @return void
 */
function mod_pulse_cm_info_dynamic(cm_info &$cm) {
    if ($cm->visible) {
        $cm->set_no_view_link();
    }
}

/**
 * Serve the files from the Pulse file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function pulse_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE && $context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }
    // Get extended plugins fileareas.
    $availablefiles = \mod_pulse\extendpro::pulse_extend_filearea();
    $availablefiles += ['pulse_content', 'intro', 'notificationheader', 'notificationfooter'];
    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, $availablefiles)) {
        return false;
    }

    // Item id is 0.
    $itemid = array_shift($args);

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // ...$args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // ...$args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_pulse', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Add a get_coursemodule_info function in case any pulse type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function pulse_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionavailable, completionself, completionapproval, completionapprovalroles';
    if (!$pulse = $DB->get_record('pulse', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $pulse->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('pulse', $pulse, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionself'] = $pulse->completionself;
        $result->customdata['customcompletionrules']['completionwhenavailable'] = $pulse->completionavailable;
        $result->customdata['customcompletionrules']['completionapproval'] = $pulse->completionapproval;
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($pulse->completionapprovalroles ) {
        $result->customdata['completionapprovalroles'] = $pulse->completionapprovalroles;

    }
    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_pulse_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionwhenavailable':
                $descriptions[] = get_string('completionwhenavailable', 'pulse');
                break;
            case 'completionself':
                $descriptions[] = get_string('completionself', 'pulse');
                break;
            case 'completionapproval':
                $descriptions[] = get_string('completionrequireapproval', 'pulse');
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Obtains the automatic completion state for this pulse based on any conditions
 * in forum settings.
 *
 * @param  object $course Course data record
 * @param  object $cm Course-module data object
 * @param  int $userid User ID
 * @param  bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @param  mixed $pulse Pulse instance data record
 * @param  mixed $completion Completion data.
 * @param  mixed $modinfo Module info class object.
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function pulse_get_completion_state($course, $cm, $userid, $type, $pulse=null, $completion=null, $modinfo=null) {
    global $CFG, $DB;

    if ($pulse == null) {
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance], "*", MUST_EXIST);
    }

    if ($completion == null) {
        $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulse->id]);
    }
    $status = $type;
    // Module availablity completion for student.
    if ($pulse->completionavailable) {
        if ($modinfo == null) {
            $modinfo = get_fast_modinfo($course->id, $userid);
            $cm = $modinfo->get_cm($cm->id);
            $isvisble = $cm->uservisible;
        } else {
            $cm = $modinfo->get_cm($cm->id);
            $info = new \core_availability\info_module($cm);
            $str = '';
            // Get section info for cm.
            // Check section is accessable by user.
            $section = $cm->get_section_info();
            $sectioninfo = new \core_availability\info_section($section);
            $isvisble = \mod_pulse\helper::pulse_mod_uservisible($cm, $userid, $sectioninfo, $modinfo, $info);
        }
        if ($isvisble) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }

    // Completion by any selected role user.
    if ($pulse->completionapproval) {
        if (!empty($completion) && $completion->approvalstatus == 1) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }
    // Self completion by own.
    if ($pulse->completionself) {
        if (!empty($completion) && $completion->selfcompletion == 1) {
            $status = COMPLETION_COMPLETE;
        } else {
            return COMPLETION_INCOMPLETE;
        }
    }
    return $status;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_pulse_core_calendar_provide_event_action(calendar_event $event,
                                                     \core_calendar\action_factory $factory,
                                                     int $userid = 0) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['pulse'][$event->instance];
    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $context = context_module::instance($cm->id);
    if (!has_capability('mod/pulse:notifyuser', $context, $userid)) {
        return null;
    }

    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/pulse/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Update the pulse content with bootstrap box before rendered in course page.
 *
 * @param cm_info $cm
 * @return void
 */
function mod_pulse_cm_info_view(cm_info $cm) {
    global $DB, $USER;

    $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
    $content = $cm->get_formatted_content();
    $course = $cm->get_course();
    $senderdata = \mod_pulse\task\sendinvitation::get_sender($course->id, $cm->context->id);
    $sender = \mod_pulse\task\sendinvitation::find_user_sender($senderdata, $USER->id);
    $user = clone $USER; // Prevent the cache issues.
    list($subject, $content) = \mod_pulse\helper::update_emailvars($content, '', $course,
                            $user, $pulse, $sender);
    $cm->set_content($content);
    if (isset($pulse->cssclass) && $pulse->cssclass) {
        $cm->set_extra_classes($pulse->cssclass);
    }
    if (isset($pulse->displaymode) && $pulse->displaymode == 1) {
        $boxtype = ($pulse->boxtype) ? $pulse->boxtype : 'primary';
        $boxicon = ($pulse->boxicon) ? $pulse->boxicon : '';
        $content = $cm->get_formatted_content();
        $content = \mod_pulse\helper::pulse_render_content($content, $boxicon, $boxtype);
        $cm->set_content($content);
    }

    $completionbtn = \mod_pulse\helper::cm_completionbuttons($cm, $pulse);
    if (!empty($completionbtn)) {
        $content = $cm->get_formatted_content();
        $content .= html_writer::tag('div', $completionbtn, ['class' => 'pulse-completion-btn']);
        $cm->set_content($content);
    }
}

/**
 * Custom method to prevent the mtrace logs based on admin config.
 *
 * @param string $message Message to log on cron.
 * @param bool $detail Need to display this in log even detailedlog config disable state.
 * @return void
 */
function pulse_mtrace($message, $detail=false) {
    $showdetail = get_config('mod_pulse', 'detailedlog');
    if ($showdetail || $detail) {
        mtrace($message);
    }
}

/**
 * Fragement output to preview the selected preset. Loads all the available informations and configurable params as form elements.
 *
 * @param array $args Preset ID and Course ID with context.
 */
function mod_pulse_output_fragment_get_preset_preview(array $args) : ?string {
    global $CFG;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $presetid = $args['presetid'];
    $courseid = $args['courseid'];
    $sectionid = $args['section'];
    $preset = new \mod_pulse\preset($presetid, $courseid, $context, $sectionid);
    return $preset->output_fragment();
}

/**
 * Fragement output to result of apply methods on selected preset.
 * Trigger the apply preset method in preset to create the pulse module using the selected preset and apply method.
 *
 * @param array $args Custom config data and Current module form data with context.
 */
function mod_pulse_output_fragment_apply_preset(array $args) : ?string {
    global $CFG;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $formdata = $args['formdata'];
    $pageparams = $args['pageparams'];
    $external = new \mod_pulse\external();
    $result = $external->apply_presets($context->id, $formdata, $pageparams);
    return $result;
}

/**
 * Fragement output to list all the presets in the pulse module add/edit form.
 *
 * @param array $args context and Course ID with context.
 */
function mod_pulse_output_fragment_get_presetslist(array $args) {
    global $OUTPUT;
    $context = $args['context'];

    if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
        return null;
    }
    $courseid = $args['courseid'];
    $presets = \mod_pulse\preset::generate_presets_list($courseid);
    return $OUTPUT->render_from_template('mod_pulse/presets_list', $presets);
}

/**
 * Generate approval buttons and self mark completions buttons based on user roles and availability.
 *
 * @param  array $args List of modules id available in the course page
 * @return string encoded html string.
 */
function mod_pulse_output_fragment_completionbuttons($args) {
    global $CFG, $DB, $USER;

    $modules = json_decode($args['modules']);
    list($insql, $inparams) = $DB->get_in_or_equal($modules);
    $sql = "SELECT cm.*, nf.completionapproval, nf.completionapprovalroles, nf.completionself FROM {course_modules} cm
    JOIN {modules} md ON md.id = cm.module
    JOIN {pulse} nf ON nf.id = cm.instance WHERE cm.id $insql AND md.name = 'pulse'";
    $records = $DB->get_records_sql($sql, $inparams);

    $html = [];

    foreach ($modules as $moduleid) {
        if (isset($records[$moduleid])) {
            $data = $records[$moduleid];
            $html[$moduleid] = '';
            $extend = true;
            // Approval button generation for selected roles.
            if ($data->completionapproval == 1) {
                $roles = $data->completionapprovalroles;
                if (\mod_pulse\helper::pulse_has_approvalrole($roles, $moduleid)) {
                    $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid]);
                    $html[$moduleid] .= html_writer::tag('div',
                        html_writer::link($approvelink, get_string('approveuserbtn', 'pulse'),
                        ['class' => 'btn btn-primary pulse-approve-users']),
                        ['class' => 'approve-user-wrapper']
                    );
                } else if (\mod_pulse\helper::pulse_user_isstudent($moduleid)) {
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $message = \mod_pulse\helper::pulse_user_approved($records[$moduleid]->instance, $USER->id) ) {
                        $html[$moduleid] .= $message.'<br>';
                    }
                }
            }
            // Generate self mark completion buttons for students.
            if (\mod_pulse\helper::pulse_is_uservisible($moduleid, $USER->id, $data->course)) {
                if ($data->completionself == 1 && \mod_pulse\helper::pulse_user_isstudent($moduleid)
                    && !\mod_pulse\helper::pulse_isusercontext($data->completionapprovalroles, $moduleid)) {
                    // Add self mark completed informations.
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $date = \mod_pulse\helper::pulse_already_selfcomplete($records[$moduleid]->instance, $USER->id)) {
                        $selfmarked = get_string('selfmarked', 'pulse', ['date' => $date]).'<br>';
                        $html[$moduleid] .= html_writer::tag('div', $selfmarked,
                        ['class' => 'pulse-self-marked badge badge-success']);
                    } else {
                        $selfcomplete = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid, 'action' => 'selfcomplete']);
                        $selfmarklink = html_writer::link($selfcomplete, get_string('markcomplete', 'pulse'),
                            ['class' => 'btn btn-primary pulse-approve-users']
                        );
                        $html[$moduleid] .= html_writer::tag('div', $selfmarklink, ['class' => 'pulse-approve-users']);
                    }
                }
            } else {
                $extend = false;
            }
            // Extend the pro features if the logged in users has able to view the module.
            if ($extend) {
                $instance = new stdclass();
                $instance->pulse = $data;
                $instance->pulse->id = $data->instance;
                $instance->user = $USER;
                $html[$moduleid] .= \mod_pulse\extendpro::pulse_extend_reaction($instance, 'content');
            }
        }
    }
    return json_encode($html);
}

/**
 * Update the automation templates and instance title during edited directly on table using inplace editable.
 *
 * @param  string $itemtype Template or Instance which is edited.
 * @param  int $itemid ID of the edited template or instance
 * @param  string $newvalue New value to updated
 * @return string Updated title of template or instance.
 */
function mod_pulse_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;
    $context = \context_system::instance();
    $PAGE->set_context($context);
    require_login();

    if ($itemtype === 'templatetitle') {

        $record = $DB->get_record('pulse_autotemplates', ['id' => $itemid], '*', MUST_EXIST);
        // Check permission of the user to update this item.
        require_capability('mod/pulse:addtemplate', context_system::instance());
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $DB->update_record('pulse_autotemplates', ['id' => $itemid, 'title' => $newvalue]);
        // Prepare the element for the output.
        $record->title = $newvalue;
        return new \core\output\inplace_editable('mod_pulse', 'title', $record->id, true,
            format_string($record->title), $record->title, 'Edit template title',
            'New value for ' . format_string($record->title));

    } else if ($itemtype === 'instancetitle') {

        $record = $DB->get_record('pulse_autotemplates_ins', ['instanceid' => $itemid], '*', MUST_EXIST);
        // Check permission of the user to update this item.
        require_capability('mod/pulse:addtemplateinstance', context_system::instance());
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $DB->update_record('pulse_autotemplates_ins', ['id' => $record->id, 'title' => $newvalue]);
        // Prepare the element for the output.
        $record->title = $newvalue;
        return new \core\output\inplace_editable('mod_pulse', 'title', $record->id, true,
            format_string($record->title), $record->title, 'Edit template title',
            'New value for ' . format_string($record->title));
    }
}

/**
 * Add the link in course secondary navigation menu to open the automation instance list page.
 *
 * @param  navigation_node $navigation
 * @param  stdClass $course
 * @param  context_course $context
 * @return void
 */
function mod_pulse_extend_navigation_course(navigation_node $navigation, stdClass $course, $context) {
    global $PAGE;

    $addnode = $context->contextlevel === CONTEXT_COURSE;
    $addnode = $addnode && has_capability('mod/pulse:addtemplateinstance', $context); // TODO: Custom capability.
    if ($addnode) {
        $id = $context->instanceid;
        $url = new moodle_url('/mod/pulse/automation/instances/list.php', [
            'courseid' => $id,
        ]);
        $node = $navigation->create(get_string('automation', 'pulse'), $url, navigation_node::TYPE_SETTING, null, null);
        $node->add_class('automation-templates');
        $node->set_force_into_more_menu(false);
        $node->set_show_in_secondary_navigation(true);
        $node->key = 'automation-templates';
        $navigation->add_node($node, 'gradebooksetup');
        $PAGE->requires->js_call_amd('mod_pulse/automation', 'instanceMenuLink', []);
    }
}

/**
 * Defines pulse automation template list nodes for my profile navigation tree.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser is the user viewing profile, current user ?
 * @param stdClass $course course object
 *
 * @return bool
 */
function mod_pulse_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    // Get the pulse category.
    if (!array_key_exists('pulse', $tree->__get('categories'))) {
        // Create the category.
        $categoryname = get_string('pluginname', 'mod_pulse');
        $category = new core_user\output\myprofile\category('pulse', $categoryname, 'privacyandpolicies');
        $tree->add_category($category);
    } else {
        // Get the existing category.
        $category = $tree->__get('categories')['pulse'];
    }

    if ($iscurrentuser) {
        $systemcontext = \context_system::instance();
        if (has_capability('mod/pulse:viewtemplateslist', $systemcontext)) {
            $automationtemplate = new moodle_url('/mod/pulse/automation/templates/list.php');
            $pulsenode = new core_user\output\myprofile\node('pulse', 'pulse',
                get_string('pulsetemplink', 'mod_pulse'), null, $automationtemplate);
            $tree->add_node($pulsenode);
        }
    }
}

/**
 * Add email placeholder fields in form fields.
 *
 * @param string $editor
 * @param bool $automation
 * @return void
 */
function pulse_email_placeholders($editor, $automation=true) {
    global $OUTPUT;

    $vars = \pulse_email_vars::vars($automation);
    $i = 0;

    foreach ($vars as $key => $var) {
        $label = str_replace($key.'_', '', $var);
        // Help text added.
        $alt = get_string('description');
        $data = [
            'text' => get_string($key.'_vars_help', 'mod_pulse'),
            'alt' => $alt,
            'icon' => (new \pix_icon('help', $alt, 'core', ['class' => 'iconhelp']))->export_for_template($OUTPUT),
            'ltr' => !right_to_left(),
        ];
        $helptext = $OUTPUT->render_from_template('core/help_icon', $data);

        $list[] = [
            'key' => $key.'_',
            'name' => get_string($key.'_vars', 'mod_pulse'),
            'helptext' => $helptext,
            'vars' => $label,
            'showmore' => (count($label) > 6) ? true : false,
            'active' => $i,
        ];
        $i++;
    }

    $templatecontext['emailvars'] = $list ?? [];
    $templatecontext['editor'] = $editor;

    return $OUTPUT->render_from_template('mod_pulse/vars', $templatecontext);
}

/**
 * Get the management instance table form the manageinstable table and return to the fragment.
 *
 * @param array $params
 * @return void
 */
function mod_pulse_output_fragment_get_manageinstance_table(array $params) {
    global $OUTPUT;

    $templateid = $params['templateid'];
    ob_start();
    $table = new \mod_pulse\table\manage_instance($templateid);
    $table->out(20, true);
    $tablehtml = ob_get_contents();
    ob_end_clean();

    return $OUTPUT->render_from_template('mod_pulse/manageinstance_table', ['tablehtml' => $tablehtml]);
}
