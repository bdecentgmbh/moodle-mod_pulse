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
 * Pulse notification action helper - Contains methods to send pulse notifications to users.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaction_notification;

use stdClass;
use core_user;
use cache;

/**
 * Pulse notification action helper to send messages to users.
 */
class helper {

    /**
     * Send pulse notifications to the users.
     *
     * @param  mixed $userto
     * @param  mixed $subject
     * @param  mixed $messageplain
     * @param  mixed $messagehtml
     * @param  mixed $pulse
     * @param  mixed $sender
     * @param array $cc List of cc users.
     * @param array $bcc List of bcc users.
     *
     * @return void
     */
    public static function messagetouser($userto, $subject, $messageplain, $messagehtml, $pulse, $sender=true, $cc=[], $bcc=[]) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/pulse/lib.php');

        $eventdata = new \core\message\message();
        $eventdata->name = 'mod_pulse';
        $eventdata->component = 'mod_pulse';
        $eventdata->courseid = $pulse->course;
        $eventdata->modulename = 'pulse';
        $eventdata->userfrom = $sender ? $sender : core_user::get_support_user();
        $eventdata->userto = $userto;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $messageplain;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $messagehtml;
        $eventdata->smallmessage = $subject;
        $eventdata->customdata = ['cc' => $cc, 'bcc' => $bcc];
        if (self::message_send($eventdata)) {
            pulse_mtrace( "Pulse send to the user.");
            return true;
        } else {
            pulse_mtrace( "Failed - Pulse send to the user. -".fullname($userto), true);
            return false;
        }
    }

    /**
     * MODIFIED the core message_send method to send the message using pulse notification manager instead of core/mesage/manager.
     *
     * Called when a message provider wants to send a message.
     * This functions checks the message recipient's message processor configuration then
     * sends the message to the configured processors
     *
     * Required parameters of the $eventdata object:
     *  component string component name. must exist in message_providers
     *  name string message type name. must exist in message_providers
     *  userfrom object|int the user sending the message
     *  userto object|int the message recipient
     *  subject string the message subject
     *  fullmessage string the full message in a given format
     *  fullmessageformat int the format if the full message (FORMAT_MOODLE, FORMAT_HTML, ..)
     *  fullmessagehtml string the full version (the message processor will choose with one to use)
     *  smallmessage string the small version of the message
     *
     * Optional parameters of the $eventdata object:
     *  notification bool should the message be considered as a notification rather than a personal message
     *  contexturl string if this is a notification then you can specify a url to view the event.
     * For example the forum post the user is being notified of.
     *  contexturlname string the display text for contexturl
     *
     * Note: processor failure will not reported as false return value in all scenarios,
     *       for example when it is called while a database transaction is open,
     *       earlier versions did not do it consistently either.
     *
     * @copyright 2008 Luis Rodrigues and Martin Dougiamas
     * @category message
     * @param \core\message\message $eventdata information about the message (component, userfrom, userto, ...)
     * @return mixed the integer ID of the new message or false if there was a problem
     */
    public static function message_send(\core\message\message $eventdata) {
        global $CFG, $DB, $SITE;

        require_once($CFG->dirroot. '/lib/messagelib.php');

        // New message ID to return.
        $messageid = false;

        // Fetch default (site) preferences.
        $defaultpreferences = get_message_output_default_preferences();
        $preferencebase = $eventdata->component.'_'.$eventdata->name;

        // If the message provider is disabled via preferences, then don't send the message.
        if (!empty($defaultpreferences->{$preferencebase.'_disable'})) {
            return $messageid;
        }

        // By default a message is a notification. Only personal/private messages aren't notifications.
        if (!isset($eventdata->notification)) {
            $eventdata->notification = 1;
        }

        if (!is_object($eventdata->userfrom)) {
            $eventdata->userfrom = core_user::get_user($eventdata->userfrom);
        }
        if (!$eventdata->userfrom) {
            debugging('Attempt to send msg from unknown user', DEBUG_NORMAL);
            return false;
        }

        // Legacy messages (FROM a single user TO a single user) must be converted into conversation messages.
        // Then, these will be passed through the conversation messages code below.
        if (!$eventdata->notification && !$eventdata->convid) {
            // If messaging is disabled at the site level, then the 'instantmessage' provider is always disabled.
            // Given this is the only 'message' type message provider, we can exit now if this is the case.
            // Don't waste processing time trying to work out the other conversation member,
            // If it's an individual conversation, just throw a generic debugging notice and return.
            if (empty($CFG->messaging) || $eventdata->component !== 'moodle' || $eventdata->name !== 'instantmessage') {
                debugging('Attempt to send msg from a provider '.$eventdata->component.'/'.$eventdata->name.
                    ' that is inactive or not allowed for the user id='.$eventdata->userto->id, DEBUG_NORMAL);
                return false;
            }

            if (!is_object($eventdata->userto)) {
                $eventdata->userto = core_user::get_user($eventdata->userto);
            }
            if (!$eventdata->userto) {
                debugging('Attempt to send msg to unknown user', DEBUG_NORMAL);
                return false;
            }

            // Verify all necessary data fields are present.
            if (!isset($eventdata->userto->auth) or !isset($eventdata->userto->suspended)
                or !isset($eventdata->userto->deleted) or !isset($eventdata->userto->emailstop)) {

                debugging('Necessary properties missing in userto object, fetching full record', DEBUG_DEVELOPER);
                $eventdata->userto = core_user::get_user($eventdata->userto->id);
            }

            $usertoisrealuser = (core_user::is_real_user($eventdata->userto->id) != false);
            // If recipient is internal user (noreply user), and emailstop is set then don't send any msg.
            if (!$usertoisrealuser && !empty($eventdata->userto->emailstop)) {
                debugging('Attempt to send msg to internal (noreply) user', DEBUG_NORMAL);
                return false;
            }

            if ($eventdata->userfrom->id == $eventdata->userto->id) {
                // It's a self conversation.
                $conversation = \core_message\api::get_self_conversation($eventdata->userfrom->id);
                if (empty($conversation)) {
                    $conversation = \core_message\api::create_conversation(
                        \core_message\api::MESSAGE_CONVERSATION_TYPE_SELF,
                        [$eventdata->userfrom->id]
                    );
                }
            } else {
                if (!$conversationid = \core_message\api::get_conversation_between_users([$eventdata->userfrom->id,
                                                                                        $eventdata->userto->id])) {
                    // It's a private conversation between users.
                    $conversation = \core_message\api::create_conversation(
                        \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
                        [
                            $eventdata->userfrom->id,
                            $eventdata->userto->id
                        ]
                    );
                }
            }
            // We either have found a conversation, or created one.
            $conversationid = !empty($conversationid) ? $conversationid : $conversation->id;
            $eventdata->convid = $conversationid;
        }

        // This is a message directed to a conversation, not a specific user as was the way in legacy messaging.
        // The above code has adapted the legacy messages into conversation messages.
        // We must call send_message_to_conversation(), which handles per-member processor iteration and triggers
        // a per-conversation event.
        // All eventdata for messages should now have a convid, as we fixed this above.
        if (!$eventdata->notification) {

            // Only one message will be saved to the DB.
            $conversationid = $eventdata->convid;
            $table = 'messages';
            $tabledata = new stdClass();
            $tabledata->courseid = $eventdata->courseid;
            $tabledata->useridfrom = $eventdata->userfrom->id;
            $tabledata->conversationid = $conversationid;
            $tabledata->subject = $eventdata->subject;
            $tabledata->fullmessage = $eventdata->fullmessage;
            $tabledata->fullmessageformat = $eventdata->fullmessageformat;
            $tabledata->fullmessagehtml = $eventdata->fullmessagehtml;
            $tabledata->smallmessage = $eventdata->smallmessage;
            $tabledata->timecreated = time();
            $tabledata->customdata = $eventdata->customdata;

            // The Trusted Content system.
            // Texts created or uploaded by such users will be marked as trusted and will not be cleaned before display.
            if (trusttext_active()) {
                // Individual conversations are always in system context.
                $messagecontext = \context_system::instance();
                // We need to know the type of conversation and the contextid if it is a group conversation.
                if ($conv = $DB->get_record('message_conversations', ['id' => $conversationid], 'id, type, contextid')) {
                    if ($conv->type == \core_message\api::MESSAGE_CONVERSATION_TYPE_GROUP && $conv->contextid) {
                        $messagecontext = \context::instance_by_id($conv->contextid);
                    }
                }
                $tabledata->fullmessagetrust = trusttext_trusted($messagecontext);
            } else {
                $tabledata->fullmessagetrust = false;
            }

            if ($messageid = message_handle_phpunit_redirection($eventdata, $table, $tabledata)) {
                return $messageid;
            }

            // Cache messages.
            if (!empty($eventdata->convid)) {
                // Cache the timecreated value of the last message in this conversation.
                $cache = cache::make('core', 'message_time_last_message_between_users');
                $key = \core_message\helper::get_last_message_time_created_cache_key($eventdata->convid);
                $cache->set($key, $tabledata->timecreated);
            }

            // Store unread message just in case we get a fatal error any time later.
            $tabledata->id = $DB->insert_record($table, $tabledata);
            $eventdata->savedmessageid = $tabledata->id;

            return \core\message\manager::send_message_to_conversation($eventdata, $tabledata);
        }

        // Else the message is a notification.
        if (!is_object($eventdata->userto)) {
            $eventdata->userto = core_user::get_user($eventdata->userto);
        }
        if (!$eventdata->userto) {
            debugging('Attempt to send msg to unknown user', DEBUG_NORMAL);
            return false;
        }

        // If the provider's component is disabled or the user can't receive messages from it, don't send the message.
        $isproviderallowed = false;
        foreach (message_get_providers_for_user($eventdata->userto->id) as $provider) {
            if ($provider->component === $eventdata->component && $provider->name === $eventdata->name) {
                $isproviderallowed = true;
                break;
            }
        }
        if (!$isproviderallowed) {
            debugging('Attempt to send msg from a provider '.$eventdata->component.'/'.$eventdata->name.
                ' that is inactive or not allowed for the user id='.$eventdata->userto->id, DEBUG_NORMAL);
            return false;
        }

        // Verify all necessary data fields are present.
        if (!isset($eventdata->userto->auth) or !isset($eventdata->userto->suspended)
                or !isset($eventdata->userto->deleted) or !isset($eventdata->userto->emailstop)) {

            debugging('Necessary properties missing in userto object, fetching full record', DEBUG_DEVELOPER);
            $eventdata->userto = core_user::get_user($eventdata->userto->id);
        }

        $usertoisrealuser = (core_user::is_real_user($eventdata->userto->id) != false);
        // If recipient is internal user (noreply user), and emailstop is set then don't send any msg.
        if (!$usertoisrealuser && !empty($eventdata->userto->emailstop)) {
            debugging('Attempt to send msg to internal (noreply) user', DEBUG_NORMAL);
            return false;
        }

        // Check if we are creating a notification or message.
        $table = 'notifications';

        $tabledata = new stdClass();
        $tabledata->useridfrom = $eventdata->userfrom->id;
        $tabledata->useridto = $eventdata->userto->id;
        $tabledata->subject = $eventdata->subject;
        $tabledata->fullmessage = $eventdata->fullmessage;
        $tabledata->fullmessageformat = $eventdata->fullmessageformat;
        $tabledata->fullmessagehtml = $eventdata->fullmessagehtml;
        $tabledata->smallmessage = $eventdata->smallmessage;
        $tabledata->eventtype = $eventdata->name;
        $tabledata->component = $eventdata->component;
        $tabledata->timecreated = time();
        $tabledata->customdata = $eventdata->customdata;
        if (!empty($eventdata->contexturl)) {
            $tabledata->contexturl = (string)$eventdata->contexturl;
        } else {
            $tabledata->contexturl = null;
        }

        if (!empty($eventdata->contexturlname)) {
            $tabledata->contexturlname = (string)$eventdata->contexturlname;
        } else {
            $tabledata->contexturlname = null;
        }

        if ($messageid = message_handle_phpunit_redirection($eventdata, $table, $tabledata)) {
            return $messageid;
        }

        // Fetch enabled processors.
        $processors = get_message_processors(true);

        // Preset variables.
        $processorlist = array();
        // Fill in the array of processors to be used based on default and user preferences.
        foreach ($processors as $processor) {
            // Skip adding processors for internal user, if processor doesn't support sending message to internal user.
            if (!$usertoisrealuser && !$processor->object->can_send_to_any_users()) {
                continue;
            }

            // First find out permissions.
            $defaultlockedpreference = $processor->name . '_provider_' . $preferencebase . '_locked';
            $locked = false;
            if (isset($defaultpreferences->{$defaultlockedpreference})) {
                $locked = $defaultpreferences->{$defaultlockedpreference};
            } else {
                // MDL-25114 They supplied an $eventdata->component $eventdata->name combination which doesn't.
                // exist in the message_provider table (thus there is no default settings for them).
                $preferrormsg = "Could not load preference $defaultlockedpreference. Make sure the component and name you supplied
                        to message_send() are valid.";
                throw new \coding_exception($preferrormsg);
            }

            $preferencename = 'message_provider_'.$preferencebase.'_enabled';
            $forced = false;
            if ($locked && isset($defaultpreferences->{$preferencename})) {
                $userpreference = $defaultpreferences->{$preferencename};
                $forced = in_array($processor->name, explode(',', $userpreference));
            }

            // Find out if user has configured this output.
            // Some processors cannot function without settings from the user.
            $userisconfigured = $processor->object->is_user_configured($eventdata->userto);

            // DEBUG: notify if we are forcing unconfigured output.
            if ($forced && !$userisconfigured) {
                debugging(
                    'Attempt to force message delivery to user who has "'.$processor->name.'" output unconfigured', DEBUG_NORMAL);
            }

            // Populate the list of processors we will be using.
            if ($forced && $userisconfigured) {
                // An admin is forcing users to use this message processor. Use this processor unconditionally.
                $processorlist[] = $processor->name;
            } else if (!$forced && !$locked && $userisconfigured && !$eventdata->userto->emailstop) {
                // User has not disabled notifications.
                // See if user set any notification preferences, otherwise use site default ones.
                if ($userpreference = get_user_preferences($preferencename, null, $eventdata->userto)) {
                    if (in_array($processor->name, explode(',', $userpreference))) {
                        $processorlist[] = $processor->name;
                    }
                } else if (isset($defaultpreferences->{$preferencename})) {
                    if (in_array($processor->name, explode(',', $defaultpreferences->{$preferencename}))) {
                        $processorlist[] = $processor->name;
                    }
                }
            }
        }

        // Store unread message just in case we get a fatal error any time later.
        $tabledata->id = $DB->insert_record($table, $tabledata);
        $eventdata->savedmessageid = $tabledata->id;

        // Let the manager do the sending or buffering when db transaction in progress.
        try {
            // PULSE MODIFY - Send the message using pulse notification manager instead of core/mesage/manager.
            return \pulseaction_notification\manager::send_message($eventdata, $tabledata, $processorlist);
        } catch (\moodle_exception $exception) {
            return false;
        }
    }



}
