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
 * Notification pulse action - Message manager.
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace pulseaction_notification;

use core\message\message;

/**
 * Helps to send messages from available message processesors, extends the moodle core\message\manager method.
 */
class manager extends \core\message\manager {

    /**
     * Do the message sending - The method to use the custom call_processor method.
     *
     * NOTE: to be used from message_send() only.
     *
     * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
     * @author    Petr Skoda <petr.skoda@totaralms.com>
     *
     * @param \core\message\message $eventdata fully prepared event data for processors
     * @param \stdClass $savemessage the message saved in 'message' table
     * @param array $processorlist list of processors for target user
     * @return int $messageid the id from 'messages' (false is not returned)
     */
    public static function send_message(message $eventdata, \stdClass $savemessage, array $processorlist) {
        global $CFG;

        require_once($CFG->dirroot.'/message/lib.php'); // This is most probably already included from messagelib.php file.

        if (empty($processorlist)) {
            // Trigger event for sending a message or notification - we need to do this before marking as read!
            self::trigger_message_events($eventdata, $savemessage);

            if ($eventdata->notification) {
                // If they have deselected all processors and it's a notification mark it read. The user doesn't want to be
                // bothered.
                $savemessage->timeread = null;
                \core_message\api::mark_notification_as_read($savemessage);
            } else if (empty($CFG->messaging)) {
                // If it's a message and messaging is disabled mark it read.
                \core_message\api::mark_message_as_read($eventdata->userto->id, $savemessage);
            }

            return $savemessage->id;
        }

        // Let the manager do the sending or buffering when db transaction in progress.
        return self::send_message_to_processors($eventdata, $savemessage, $processorlist);
    }

    /**
     * Send message to message processors - Inherit the method to use the custom call_processor method.
     *
     * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
     * @author    Petr Skoda <petr.skoda@totaralms.com>
     *
     * @param \stdClass|\core\message\message $eventdata
     * @param \stdClass $savemessage
     * @param array $processorlist
     * @throws \moodle_exception
     * @return int $messageid
     */
    protected static function send_message_to_processors($eventdata, \stdClass $savemessage, array
    $processorlist) {
        global $CFG, $DB;

        // We cannot communicate with external systems in DB transactions,
        // buffer the messages if necessary.
        if ($DB->is_transaction_started()) {
            // We need to clone all objects so that devs may not modify it from outside later.
            $eventdata = clone($eventdata);
            $eventdata->userto = clone($eventdata->userto);
            $eventdata->userfrom = clone($eventdata->userfrom);

            // Conserve some memory the same was as $USER setup does.
            unset($eventdata->userto->description);
            unset($eventdata->userfrom->description);

            self::$buffer[] = array($eventdata, $savemessage, $processorlist);
            return $savemessage->id;
        }

        // Send the message to processors.
        if (!self::call_processors($eventdata, $processorlist)) {
            throw new \moodle_exception("Message was not sent.");
        }

        // Trigger event for sending a message or notification - we need to do this before marking as read!
        self::trigger_message_events($eventdata, $savemessage);

        if (!$eventdata->notification && empty($CFG->messaging)) {
            // If it's a message and messaging is disabled mark it read.
            \core_message\api::mark_message_as_read($eventdata->userto->id, $savemessage);
        }

        return $savemessage->id;
    }

    /**
     * For each processor, call it's send_message() method
     *  - This method is modified version of core\message\manager call_processors.
     *  - Modifed to use our custom email message procesessor instead of default message_output_email method.
     *  - By updating the email processor pulse includes the CC and Bcc emails.
     *
     * @copyright 2014 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
     * @author    Petr Skoda <petr.skoda@totaralms.com>
     *
     * @param message $eventdata the message object.
     * @param array $processorlist the list of processors for a single user.
     * @return bool false if error calling message processor
     */
    protected static function call_processors(message $eventdata, array $processorlist) {
        // Allow plugins to change the message/notification data before sending it.
        $pluginsfunction = get_plugins_with_function('pre_processor_message_send');
        $sendmsgsuccessful = true;
        foreach ($processorlist as $procname) {
            // Let new messaging class add custom content based on the processor.
            $proceventdata = ($eventdata instanceof message) ? $eventdata->get_eventobject_for_processor($procname) : $eventdata;

            if ($pluginsfunction) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        $pluginfunction($procname, $proceventdata);
                    }
                }
            }

            $stdproc = new \stdClass();
            $stdproc->name = $procname;

            // Call the pulse email process instead of message_email_output.
            $processor = ($procname == 'email')
                ? self::get_processed_processor_object($stdproc) : \core_message\api::get_processed_processor_object($stdproc);
            if (!$processor->object->send_message($proceventdata)) {
                debugging('Error calling message processor ' . $procname);
                $sendmsgsuccessful = false;
            }
        }
        return $sendmsgsuccessful;
    }

    /**
     * Modified version of \core_message\api::get_processed_processor_object.
     *  - Fetch the pulseaction_notification_email processor. This helps to use pulse custom email processor.
     *
     * Given a processor object, loads information about it's settings and configurations.
     * This is not a public api, instead use {@see \core_message\api::get_message_processor()}
     * or {@see \get_message_processors()}
     *
     * @copyright  2016 Mark Nelson <markn@moodle.com>
     *
     * @param \stdClass $processor processor object
     * @return \stdClass processed processor object
     * @since Moodle 3.2
     */
    public static function get_processed_processor_object(\stdClass $processor) {
        global $CFG;

        $processorfile = $CFG->dirroot. '/mod/pulse/actions/notification/pulseaction_notification_email.php';
        if (is_readable($processorfile)) {
            include_once($processorfile);
            $processclass = 'pulseaction_notification_email';
            if (class_exists($processclass)) {
                $pclass = new $processclass();
                $processor->object = $pclass;
                $processor->configured = 0;
                if ($pclass->is_system_configured()) {
                    $processor->configured = 1;
                }
                $processor->hassettings = 0;
                if (is_readable($CFG->dirroot.'/message/output/'.$processor->name.'/settings.php')) {
                    $processor->hassettings = 1;
                }
                $processor->available = 1;
            } else {
                throw new \moodle_exception('errorcallingprocessor', 'message');
            }
        } else {
            $processor->available = 0;
        }
        return $processor;
    }
}
