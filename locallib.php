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

/**
 * Replace email template placeholders with dynamic datas.
 *
 * @param  mixed $templatetext Email Body content with placeholders
 * @param  mixed $subject Mail subject with placeholders.
 * @param  mixed $course Course object data.
 * @param  mixed $user User data object.
 * @param  mixed $mod Pulse module data object.
 * @param  mixed $sender Sender user data object. - sender is the first enrolled teacher in the course of module.
 * @return array Updated subject and message body content.
 */
function mod_pulse_update_emailvars($templatetext, $subject, $course, $user, $mod, $sender) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/pulse/lib/vars.php');
    $sender = $sender ? $sender : core_user::get_support_user(); // Support user.
    $amethods = pulse_email_vars::vars(); // List of available placeholders.
    $vars = new pulse_email_vars($user, $course, $sender, $mod);

    foreach ($amethods as $funcname) {
        $replacement = "{" . $funcname . "}";
        // Message text placeholder update.
        if (stripos($templatetext, $replacement) !== false) {
            $val = $vars->$funcname;
            // Placeholder found on the text, then replace with data.
            $templatetext = str_replace($replacement, $val, $templatetext);
        }
        // Replace message subject placeholder.
        if (stripos($subject, $replacement) !== false) {
            $val = $vars->$funcname;
            $subject = str_replace($replacement, $val, $subject);
        }
    }
    return [$subject, $templatetext];
}

/**
 * Find the course module is visible to current user.
 *
 * @param  mixed $cmid
 * @param  mixed $userid
 * @param  mixed $courseid
 * @return void
 */
function mod_pulse_is_uservisible($cmid, $userid, $courseid) {
    // Filter available users.
    if (!empty($cmid)) {
        $modinfo = get_fast_modinfo($courseid, $userid);
        $cm = $modinfo->get_cm($cmid);
        return $cm->uservisible;
    }
}

/**
 * Check the current users has role to approve the completion for students in current pulse module.
 *
 * @param  mixed $completionapprovalroles Completion approval roles select in the pulse instance.
 * @param  mixed $cmid Course module id.
 * @param  mixed $usercontext check user context(false it only check and return the coursecontetxt roles)
 * @param  mixed $userid
 * @return void
 */
function pulse_has_approvalrole($completionapprovalroles, $cmid, $usercontext=true, $userid=null) {
    global $USER, $DB;
    if ($userid == null) {
        $userid = $USER->id;
    }
    $modulecontext = context_module::instance($cmid);
    $approvalroles = json_decode($completionapprovalroles);
    $roles = get_user_roles($modulecontext, $userid);
    $hasrole = false;
    foreach ($roles as $key => $role) {
        if (in_array($role->roleid, $approvalroles)) {
            $hasrole = true;
        }
    }
    // Check if user has role in course context level role to approve.
    if (!$usercontext) {
        return $hasrole;
    }
    // Test user has user context.
    $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
            FROM {role_assignments} ra
            JOIN {context} c ON ra.contextid = c.id
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ? and c.contextlevel = ?
            ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
    $roleassignments = $DB->get_records_sql($sql, array($userid, CONTEXT_USER));
    if ($roleassignments) {
        foreach ($roleassignments as $role) {
            if (in_array($role->roleid, $approvalroles)) {
                return true;
            }
        }
    }
    return $hasrole;
}

/**
 * Check the pulse instance contains user context roles in completion approval roles
 *
 * @param  mixed $completionapprovalroles
 * @param  mixed $cmid
 * @return void
 */
function pulse_isusercontext($completionapprovalroles, $cmid) {
    global $DB, $USER;

    // Test user has user context.
    $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, ra.component, ra.itemid, c.path
            FROM {role_assignments} ra
            JOIN {context} c ON ra.contextid = c.id
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = ? and c.contextlevel = ?
            ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
    $roleassignments = $DB->get_records_sql($sql, array($USER->id, CONTEXT_USER));
    if ($roleassignments) {
        return true;
    }
    return false;
}

/**
 * Get mentees assigned students list.
 *
 * @return bool|mixed List of users assigned as child users.
 */
function pulse_user_getmentessuser() {
    global $DB, $USER;

    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid
                                            FROM {role_assignments} ra, {context} c, {user} u
                                            WHERE ra.userid = ?
                                                    AND ra.contextid = c.id
                                                    AND c.instanceid = u.id
                                                    AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {

        $users = [];
        foreach ($usercontexts as $usercontext) {
            $users[] = $usercontext->instanceid;
        }
        return $users;
    }
    return false;
}

/**
 * Check and generate user approved information for module.
 *
 * @param  mixed $pulseid
 * @param  mixed $userid
 * @return bool|string Returns approved user data as html.
 */
function pulse_user_approved($pulseid, $userid) {
    global $DB;
    $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulseid]);
    if (!empty($completion) && $completion->approvalstatus) {
        $date = userdate($completion->approvaltime, get_string('strftimedaydate', 'core_langconfig'));
        $approvaltime = isset($completion->approvalstatus) ? $date : 0;
        $params['date'] = ($approvaltime) ? $approvaltime : '-';
        $approvedby = isset($completion->approveduser) ? \core_user::get_user($completion->approveduser) : '';
        $params['user'] = ($approvedby) ? fullname($approvedby) : '-';

        $approvalstr = get_string('approvedon', 'pulse', $params);
        return html_writer::tag('div', $approvalstr, ['class' => 'badge badge-info']);
    }
    return false;
}

/**
 * Check the logged in users is student for pulse.
 *
 * @param  mixed $cmid
 * @return bool
 */
function pulse_user_isstudent($cmid) {
    global $USER;
    $modulecontext = context_module::instance($cmid);
    $roles = get_user_roles($modulecontext, $USER->id);
    $hasrole = false;
    $studentroles = array_keys(get_archetype_roles('student'));
    foreach ($roles as $key => $role) {
        if (in_array($role->roleid, $studentroles)) {
            $hasrole = true;
            break;
        }
    }
    return $hasrole;
}

/**
 * Find the user already completed the module by self compeltion.
 *
 * @param  mixed $pulseid
 * @param  mixed $userid
 * @return bool true|false
 */
function pulse_already_selfcomplete($pulseid, $userid) {
    global $DB;
    $completion = $DB->get_record('pulse_completion', ['userid' => $userid, 'pulseid' => $pulseid]);
    if (!empty($completion) && $completion->selfcompletion) {
        if (isset($completion->selfcompletion) && $completion->selfcompletion != '') {
            $result = userdate($completion->selfcompletiontime, get_string('strftimedaydate', 'core_langconfig'));
        }
    }
    return isset($result) ? $result : false;
}

/**
 * Render the pulse content with selected box container with box icon.
 *
 * @param string $content Pulse content.
 * @param string $boxicon Icon.
 * @param string $boxtype Box type name (primary, secondory, danger, warning and others).
 * @return string Pulse content with box container.
 */
function mod_pulse_render_content(string $content, string $boxicon, string $boxtype = 'primary'): string {
    global $OUTPUT;
    $html = html_writer::start_tag('div', ['class' => 'pulse-box']);
    $html .= html_writer::start_tag('div', ['class' => 'alert alert-'.$boxtype]);
    if (!empty($boxicon)) {
        $icon = explode(':', $boxicon);
        $icon1 = isset($icon[1]) ? $icon[1] : 'core';
        $icon0 = isset($icon[0]) ? $icon[0] : '';
        $boxicon = $OUTPUT->pix_icon($icon1, $icon0);
        $html .= html_writer::tag('div', $boxicon, ['class' => 'alert alert-icon pulse-box-icon']);
    }
    $html .= html_writer::tag('div', $content, ['class' => 'pulse-box-content']);
    $html .= html_writer::end_tag('div');
    $html .= html_writer::end_tag('div');
    return $html;
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
    $preset = new mod_pulse\preset($presetid, $courseid, $context, $sectionid);
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
    JOIN {modules} AS md ON md.id = cm.module
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
                if (pulse_has_approvalrole($roles, $moduleid)) {
                    $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid]);
                    $html[$moduleid] .= html_writer::tag('div',
                        html_writer::link($approvelink, get_string('approveuserbtn', 'pulse'),
                        ['class' => 'btn btn-primary pulse-approve-users']),
                        ['class' => 'approve-user-wrapper']
                    );
                } else if (pulse_user_isstudent($moduleid)) {
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $message = pulse_user_approved($records[$moduleid]->instance, $USER->id) ) {
                        $html[$moduleid] .= $message.'<br>';
                    }
                }
            }
            // Generate self mark completion buttons for students.
            if (mod_pulse_is_uservisible($moduleid, $USER->id, $data->course)) {
                if ($data->completionself == 1 && pulse_user_isstudent($moduleid)
                    && !pulse_isusercontext($data->completionapprovalroles, $moduleid)) {
                    // Add self mark completed informations.
                    if (!class_exists('core_completion\activity_custom_completion')
                        && $date = pulse_already_selfcomplete($records[$moduleid]->instance, $USER->id)) {
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
                $html[$moduleid] .= pulse_extend_reaction($instance, 'content');
            }
        }
    }

    return json_encode($html);
}

/**
 * Add the completion and reaction buttons with pulse content on view page.
 *
 * @param cm_info $cm Current Course module.
 * @param stdclass $pulse Pulse record object.
 * @return string $html Completion and reaction buttons html content.
 */
function mod_pulse_cm_completionbuttons(cm_info $cm, stdclass $pulse): string {
    global $USER, $DB;
    $html = '';
    $moduleid = $cm->id;
    $extend = true;
    // Approval button generation for selected roles.
    if ($pulse->completionapproval == 1) {
        $roles = $pulse->completionapprovalroles;
        if (pulse_has_approvalrole($roles, $cm->id)) {
            $approvelink = new moodle_url('/mod/pulse/approve.php', ['cmid' => $cm->id]);
            $html .= html_writer::tag('div',
                html_writer::link($approvelink, get_string('approveuserbtn', 'pulse'),
                ['class' => 'btn btn-primary pulse-approve-users']),
                ['class' => 'approve-user-wrapper']
            );
        } else if (pulse_user_isstudent($cm->id)) {
            if (!class_exists('core_completion\activity_custom_completion')
                && $message = pulse_user_approved($cm->instance, $USER->id)) {
                $html .= $message.'<br>';
            }
        }
    }

    // Generate self mark completion buttons for students.
    if (mod_pulse_is_uservisible($moduleid, $USER->id, $cm->course)) {
        if ($pulse->completionself == 1 && pulse_user_isstudent($moduleid)
            && !pulse_isusercontext($pulse->completionapprovalroles, $moduleid)) {
            // Add self mark completed informations.
            if (!class_exists('core_completion\activity_custom_completion')
                && $date = pulse_already_selfcomplete($cm->instance, $USER->id)) {
                $selfmarked = get_string('selfmarked', 'pulse', ['date' => $date]).'<br>';
                $html .= html_writer::tag('div', $selfmarked,
                ['class' => 'pulse-self-marked badge badge-success']);
            } else if (!pulse_already_selfcomplete($cm->instance, $USER->id)) {
                $selfcomplete = new moodle_url('/mod/pulse/approve.php', ['cmid' => $moduleid, 'action' => 'selfcomplete']);
                $selfmarklink = html_writer::link($selfcomplete, get_string('markcomplete', 'pulse'),
                    ['class' => 'btn btn-primary pulse-approve-users']
                );
                $html .= html_writer::tag('div', $selfmarklink, ['class' => 'pulse-approve-users']);
            }
        }
    } else {
        $extend = false;
    }
    // Extend the pro features if the logged in users has able to view the module.
    if ($extend) {
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);
        $instance = new stdclass();
        $instance->pulse = $pulse;
        $instance->pulse->id = $cm->instance;
        $instance->user = $USER;
        $html .= pulse_extend_reaction($instance, 'content');
    }
    return $html;
}
