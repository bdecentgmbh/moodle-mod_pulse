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
 * Privacy implementation for pulse module
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_pulse\privacy;

use stdClass;
use context;

use core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * The pulse module stores user completion and invitation notified details.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * List of used data fields summary meta key.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // Completion table fields meta summary.
        $completionmetadata = [
            'userid' => 'privacy:metadata:completion:userid',
            'approvalstatus' => 'privacy:metadata:completion:approvalstatus',
            'approveduser' => 'privacy:metadata:completion:approveduser',
            'approvaltime' => 'privacy:metadata:completion:approvaltime',
            'selfcompletion' => 'privacy:metadata:completion:selfcompletion',
            'selfcompletiontime' => 'privacy:metadata:completion:selfcompletiontime',
            'timemodified' => 'privacy:metadata:completion:timemodified'
        ];
        $collection->add_database_table('pulse_completion', $completionmetadata, 'privacy:metadata:pulsecompletion');

        // Users invitation notified data.
        $usersmetadata = [
            'userid' => 'privacy:metadata:users:userid',
            'status' => 'privacy:metadata:users:status',
            'timecreated' => 'privacy:metadata:users:timecreated'
        ];
        $collection->add_database_table('pulse_users', $usersmetadata, 'privacy:metadata:pulseusers');

        // Added moodle subsystems used in pulse.
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:pulsemessageexplanation');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int         $userid    The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        // User completions.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pulse} p ON p.id = cm.instance
                LEFT JOIN {pulse_completion} pc ON pc.pulseid = p.id
                WHERE (pc.userid = :userid or pc.approveduser = :approvedby)";
        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
            'approvedby' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        // Invitation notified users.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pulse} p ON p.id = cm.instance
                LEFT JOIN {pulse_users} pu ON pu.pulseid = p.id
                WHERE pu.userid = :userid";
        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
            'modulename' => 'pulse',
        ];

         // Discussion authors.
        $sql = "SELECT d.userid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {pulse_completion} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Approved users.
        $sql = "SELECT d.approveduser
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {pulse_completion} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('approveduser', $sql, $params);

        $sql = "SELECT d.userid
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
        JOIN {pulse} f ON f.id = cm.instance
        JOIN {pulse_users} d ON d.pulseid = f.id
        WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        $pulse = $DB->get_record('pulse', ['id' => $cm->instance]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge(['pulseid' => $pulse->id], $userinparams);
        $sql = "pulseid = :pulseid AND userid {$userinsql}";
        $DB->delete_records_select('pulse_completion', $sql, $params);
        $DB->delete_records_select('pulse_users', $sql, $params);

        $sql = "pulseid = :pulseid AND approveduser {$userinsql}";
        $DB->set_field_select('pulse_completion', 'approvalstatus', 0, $sql, $params);
        $DB->set_field_select('pulse_completion', 'approvaltime', '', $sql, $params);
        $DB->set_field_select('pulse_completion', 'approveduser', '', $sql, $params);
    }

    /**
     * Delete user completion data for multiple context.
     *
     * @param approved_contextlist $contextlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('pulse_completion', ['pulseid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('pulse_users', ['pulseid' => $instanceid, 'userid' => $userid]);

            $DB->set_field('pulse_completion', 'approvalstatus', 0, ['pulseid' => $instanceid, 'approveduser' => $userid]);
            $DB->set_field('pulse_completion', 'approvaltime', '', ['pulseid' => $instanceid, 'approveduser' => $userid]);
            $DB->set_field('pulse_completion', 'approveduser', '', ['pulseid' => $instanceid, 'approveduser' => $userid]);
        }
    }

    /**
     * Delete all completion data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('pulse', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('pulse_completion', ['pulseid' => $cm->instance]);
        $DB->delete_records('pulse_users', ['pulseid' => $cm->instance]);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        // Context user.
        $user = $contextlist->get_user();
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT pc.id AS completionid, cm.id AS cmid, c.id AS contextid,
                p.id AS pid, p.course AS pcourse,
                pc.userid AS userid, pc.selfcompletion AS selfcompletion, pc.selfcompletiontime AS selfcompletiontime,
                pc.approvalstatus AS approved, pc.approvaltime AS approvedtime, pc.approveduser AS approvedby
              FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {pulse} p ON p.id = cm.instance
        INNER JOIN {pulse_completion} pc ON pc.pulseid = p.id AND (pc.userid = :userid or pc.approveduser = :approvedby)
            WHERE c.id {$contextsql}
            ORDER BY cm.id, pc.id ASC";

        $params = [
            'modname' => 'pulse',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $contextlist->get_user()->id,
            'approvedby' => $contextlist->get_user()->id,
        ];
        $completions = $DB->get_records_sql($sql, $params + $contextparams);

        self::export_pulse_completions(
            get_string('completionfor', 'mod_pulse'),
            array_filter(
                $completions,
                function(stdClass $completion) use ($contextlist) : bool {
                    return $completion->userid == $contextlist->get_user()->id;
                }
            ),
            $user
        );

        self::export_pulse_completions(
            get_string('approvedby', 'mod_pulse'),
            array_filter(
                $completions,
                function(stdClass $completion) use ($contextlist) : bool {
                    return $completion->approvedby == $contextlist->get_user()->id;
                }
            ),
            $user
        );

    }

    /**
     * Helper function to export completions.
     *
     * The array of "completions" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of sessions. Which is why it needs to be grouped by context id.
     *
     * @param string $path The path in the export (relative to the current context).
     * @param array $completions Array of completions to export the logs for.
     * @param stdclass $user User record object.
     */
    private static function export_pulse_completions(string $path, array $completions, $user) {

        $completionsbycontextid = self::group_by_property($completions, 'contextid');

        foreach ($completionsbycontextid as $contextid => $completion) {
            $context = context::instance_by_id($contextid);

            $completionsbyid = self::group_by_property($completion, 'completionid');

            foreach ($completionsbyid as $completionid => $completions) {

                $completiondata = array_map(function($completion) use ($user) {
                    if ($user->id == $completion->approvedby) {
                        return [
                            'approvedfor' => fullname(\core_user::get_user($completion->userid)),
                            'approvedtime' => $completion->approvedtime ? transform::datetime($completion->approvedtime) : '-',
                        ];

                    } else {
                        return [
                            'selfcomplete' => (($completion->selfcompletion == 1) ? get_string('yes') : get_string('no')),
                            'selfcompletiontime' => $completion->selfcompletiontime
                                ? transform::datetime($completion->selfcompletiontime) : '-',
                            'approved' => (($completion->approved == 1) ? get_string('yes') : get_string('no')),
                            'approvaltime' => $completion->approvedtime ? transform::datetime($completion->approvedtime) : '-',
                            'invitaion' => self::generate_invitationdata($completion->pid, $user->id)

                        ];
                    }
                }, $completions);

                if (!empty($completiondata)) {
                    $context = context::instance_by_id($contextid);
                    // Fetch the generic module data for the questionnaire.
                    $contextdata = helper::get_context_data($context, $user);
                    $contextdata = (object)array_merge((array)$contextdata, $completiondata);
                    writer::with_context($context)->export_data(
                        [get_string('privacy:completion', 'pulse').' '.$completionid, $path],
                        $contextdata
                    );
                }
            };
        }
    }

    /**
     * Generate the invitation notified data for the current user from pulse instance.
     *
     * @param int $pulseid Id of the pulse instance id.
     * @param int $userid Id of the export user.
     * @return array|null Current and previous invitations send to user.
     */
    public static function generate_invitationdata(int $pulseid, int $userid): ?array {
        global $DB;
        if ($records = $DB->get_records('pulse_users', ['pulseid' => $pulseid, 'userid' => $userid])) {
            $previousinvitedata = $current = [];
            $invitations = array_reverse(self::group_by_property($records, 'status'));
            if (empty($invitations)) {
                return [];
            }
            $invitedata = $invitations[0] ?? [];
            if (!empty($invitedata)) {
                $current = [
                    'invited' => get_string('yes'),
                    'invitedtime' => $invitedata[0]->timecreated ? transform::datetime($invitedata[0]->timecreated) : '-'
                ];
            }
            $previousinvitations = $invitations[1] ?? [];

            if (!empty($previousinvitations)) {
                $previousinvitedata['previousinvitations'] = array_map(function($prev) {
                    return [
                        'invitedtime' => $prev->timecreated ? transform::datetime($prev->timecreated) : '-',
                    ];
                }, $previousinvitations);
            }

            return $current + $previousinvitedata;
        }
        return [];
    }


    /**
     * Helper function to group an array of stdClasses by a common property.
     *
     * @param array $classes An array of classes to group.
     * @param string $property A common property to group the classes by.
     * @return array list of element seperated by given property.
     */
    private static function group_by_property(array $classes, string $property): array {
        return array_reduce(
            $classes,
            function (array $classes, stdClass $class) use ($property) : array {
                $classes[$class->{$property}][] = $class;
                return $classes;
            },
            []
        );
    }

}
