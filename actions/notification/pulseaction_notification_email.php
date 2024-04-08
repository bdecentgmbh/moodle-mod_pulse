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
 * Contains the definiton of the email message processors (sends messages to users via email)
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/email/message_output_email.php');

/**
 * The email message processor for pulse notification actions. Extends the core message_output_email processor.
 */
class pulseaction_notification_email extends message_output_email {

    /**
     * Processes the message (sends by email).
     * - Modified the core message_output_email plugin method to send CC and BCC mails.
     *
     * @copyright 2008 Luis Rodrigues and Martin Dougiamas
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     */
    public function send_message($eventdata) {
        global $CFG, $DB;

        // Skip any messaging suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' || $eventdata->userto->suspended || $eventdata->userto->deleted) {
            return true;
        }

        // The user the email is going to.
        $recipient = null;

        // Check if the recipient has a different email address specified in their messaging preferences Vs their user profile.
        $emailmessagingpreference = get_user_preferences('message_processor_email_email', null, $eventdata->userto);
        $emailmessagingpreference = clean_param($emailmessagingpreference, PARAM_EMAIL);

        // If the recipient has set an email address in their preferences use that instead of the one in their profile,
        // But only if overriding the notification email address is allowed.
        if (!empty($emailmessagingpreference) && !empty($CFG->messagingallowemailoverride)) {
            // Clone to avoid altering the actual user object.
            $recipient = clone($eventdata->userto);
            $recipient->email = $emailmessagingpreference;
        } else {
            $recipient = $eventdata->userto;
        }

        // Check if we have attachments to send.
        $attachment = '';
        $attachname = '';
        if (!empty($CFG->allowattachments) && !empty($eventdata->attachment)) {
            if (empty($eventdata->attachname)) {
                // Attachment needs a file name.
                debugging('Attachments should have a file name. No attachments have been sent.', DEBUG_DEVELOPER);
            } else if (!($eventdata->attachment instanceof stored_file)) {
                // Attachment should be of a type stored_file.
                debugging('Attachments should be of type stored_file. No attachments have been sent.', DEBUG_DEVELOPER);
            } else {
                // Copy attachment file to a temporary directory and get the file path.
                $attachment = $eventdata->attachment->copy_content_to_temp();

                // Get attachment file name.
                $attachname = clean_filename($eventdata->attachname);
            }
        }

        // Configure mail replies - this is used for incoming mail replies.
        $replyto = '';
        $replytoname = '';
        if (isset($eventdata->replyto)) {
            $replyto = $eventdata->replyto;
            if (isset($eventdata->replytoname)) {
                $replytoname = $eventdata->replytoname;
            }
        }

        // Pulse - section to add bcc and cc users.
        $ccaddr = [];
        $bccaddr = [];
        $customdata = json_decode($eventdata->customdata);
        if (isset($eventdata->customdata) && isset($customdata->cc)) {
            $ccaddr = $customdata->cc;
            $bccaddr = $customdata->bcc;
        }

        // We email messages from private conversations straight away, but for group we add them to a table to be sent later.
        $emailuser = true;
        if (!$eventdata->notification) {
            if ($eventdata->conversationtype == \core_message\api::MESSAGE_CONVERSATION_TYPE_GROUP) {
                $emailuser = false;
            }
        }

        if ($emailuser) {
            $result = $this->email_to_user($recipient, $eventdata->userfrom, $eventdata->subject, $eventdata->fullmessage,
                $eventdata->fullmessagehtml, $attachment, $attachname, true, $replyto, $replytoname, 79, $ccaddr, $bccaddr);
        } else {
            $messagetosend = new stdClass();
            $messagetosend->useridfrom = $eventdata->userfrom->id;
            $messagetosend->useridto = $recipient->id;
            $messagetosend->conversationid = $eventdata->convid;
            $messagetosend->messageid = $eventdata->savedmessageid;
            $result = $DB->insert_record('message_email_messages', $messagetosend, false);
        }

        // Remove an attachment file if any.
        if (!empty($attachment) && file_exists($attachment)) {
            unlink($attachment);
        }

        return $result;
    }

    /**
     * Send an email to a specified user - Modified the core moodlelib function to send CC and BCC mails.
     *
     * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
     *
     * @param stdClass $user  A {@see $USER} object
     * @param stdClass $from A {@see $USER} object
     * @param string $subject plain text subject line of the email
     * @param string $messagetext plain text version of the message
     * @param string $messagehtml complete html version of the message (optional)
     * @param string $attachment a file on the filesystem, either relative to $CFG->dataroot or a full path to a file in one of
     *          the following directories: $CFG->cachedir, $CFG->dataroot, $CFG->dirroot, $CFG->localcachedir, $CFG->tempdir
     * @param string $attachname the name of the file (extension indicates MIME)
     * @param bool $usetrueaddress determines whether $from email address should
     *          be sent out. Will be overruled by user profile setting for maildisplay
     * @param string $replyto Email address to reply to
     * @param string $replytoname Name of reply to recipient
     * @param int $wordwrapwidth custom word wrap width, default 79
     * @param array $cc Cc mails list.
     * @param array $bcc Bcc mails list.
     *
     * @return bool Returns true if mail was sent OK and false if there was an error.
     */
    public function email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '',
                        $usetrueaddress = true, $replyto = '', $replytoname = '', $wordwrapwidth = 79, $cc=[], $bcc=[]) {

        global $CFG, $PAGE, $SITE;

        if (empty($user) || empty($user->id)) {
            debugging('Can not send email to null user', DEBUG_DEVELOPER);
            return false;
        }

        if (empty($user->email)) {
            debugging('Can not send email to user without email: '.$user->id, DEBUG_DEVELOPER);
            return false;
        }

        if (!empty($user->deleted)) {
            debugging('Can not send email to deleted user: '.$user->id, DEBUG_DEVELOPER);
            return false;
        }

        if (defined('BEHAT_SITE_RUNNING')) {
            // Fake email sending in behat.
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('Not sending email due to $CFG->noemailever config setting', DEBUG_NORMAL);
            return true;
        }

        if (email_should_be_diverted($user->email)) {
            $subject = "[DIVERTED {$user->email}] $subject";
            $user = clone($user);
            $user->email = $CFG->divertallemailsto;
        }

        // Skip mail to suspended users.
        if ((isset($user->auth) && $user->auth == 'nologin') || (isset($user->suspended) && $user->suspended)) {
            return true;
        }

        if (!validate_email($user->email)) {
            // We can not send emails to invalid addresses - it might create security issue or confuse the mailer.
            debugging("email_to_user: User $user->id (".fullname($user).") email ($user->email) is invalid! Not sending.");
            return false;
        }

        if (over_bounce_threshold($user)) {
            debugging("email_to_user: User $user->id (".fullname($user).") is over bounce threshold! Not sending.");
            return false;
        }

        // TLD .invalid  is specifically reserved for invalid domain names.
        // For More information, see {@link http://tools.ietf.org/html/rfc2606#section-2}.
        if (substr($user->email, -8) == '.invalid') {
            debugging("email_to_user: User $user->id (".fullname($user).") email domain ($user->email) is invalid! Not sending.");
            return true; // This is not an error.
        }

        // If the user is a remote mnet user, parse the email text for URL to the
        // wwwroot and modify the url to direct the user's browser to login at their
        // home site (identity provider - idp) before hitting the link itself.
        if (is_mnet_remote_user($user)) {
            require_once($CFG->dirroot.'/mnet/lib.php');

            $jumpurl = mnet_get_idp_jump_url($user);
            $callback = partial('mnet_sso_apply_indirection', $jumpurl);

            $messagetext = preg_replace_callback("%($CFG->wwwroot[^[:space:]]*)%",
                    $callback,
                    $messagetext);
            $messagehtml = preg_replace_callback("%href=[\"'`]($CFG->wwwroot[\w_:\?=#&@/;.~-]*)[\"'`]%",
                    $callback,
                    $messagehtml);
        }
        $mail = get_mailer();

        if (!empty($mail->SMTPDebug)) {
            echo '<pre>' . "\n";
        }

        $temprecipients = [];
        $tempreplyto = [];

        // Make sure that we fall back onto some reasonable no-reply address.
        $noreplyaddressdefault = 'noreply@' . get_host_from_url($CFG->wwwroot);
        $noreplyaddress = empty($CFG->noreplyaddress) ? $noreplyaddressdefault : $CFG->noreplyaddress;

        if (!validate_email($noreplyaddress)) {
            debugging('email_to_user: Invalid noreply-email '.s($noreplyaddress));
            $noreplyaddress = $noreplyaddressdefault;
        }

        // Make up an email address for handling bounces.
        if (!empty($CFG->handlebounces)) {
            $modargs = 'B'.base64_encode(pack('V', $user->id)).substr(md5($user->email), 0, 16);
            $mail->Sender = generate_email_processing_address(0, $modargs);
        } else {
            $mail->Sender = $noreplyaddress;
        }

        // Make sure that the explicit replyto is valid, fall back to the implicit one.
        if (!empty($replyto) && !validate_email($replyto)) {
            debugging('email_to_user: Invalid replyto-email '.s($replyto));
            $replyto = $noreplyaddress;
        }

        if (is_string($from)) { // So we can pass whatever we want if there is need.
            $mail->From     = $noreplyaddress;
            $mail->FromName = $from;
            // Check if using the true address is true, and the email is in the list of allowed domains for sending email,
            // and that the senders email setting is either displayed to everyone, or display to only other users that are enrolled
            // in a course with the sender.
        } else if ($usetrueaddress && can_send_from_real_email_address($from, $user)) {
            if (!validate_email($from->email)) {
                debugging('email_to_user: Invalid from-email '.s($from->email).' - not sending');
                // Better not to use $noreplyaddress in this case.
                return false;
            }
            $mail->From = $from->email;
            $fromdetails = new stdClass();
            $fromdetails->name = fullname($from);
            $fromdetails->url = preg_replace('#^https?://#', '', $CFG->wwwroot);
            $fromdetails->siteshortname = format_string($SITE->shortname);
            $fromstring = $fromdetails->name;
            if ($CFG->emailfromvia == EMAIL_VIA_ALWAYS) {
                $fromstring = get_string('emailvia', 'core', $fromdetails);
            }
            $mail->FromName = $fromstring;
            if (empty($replyto)) {
                $tempreplyto[] = [$from->email, fullname($from)];
            }
        } else {
            $mail->From = $noreplyaddress;
            $fromdetails = new stdClass();
            $fromdetails->name = fullname($from);
            $fromdetails->url = preg_replace('#^https?://#', '', $CFG->wwwroot);
            $fromdetails->siteshortname = format_string($SITE->shortname);
            $fromstring = $fromdetails->name;
            if ($CFG->emailfromvia != EMAIL_VIA_NEVER) {
                $fromstring = get_string('emailvia', 'core', $fromdetails);
            }
            $mail->FromName = $fromstring;
            if (empty($replyto)) {
                $tempreplyto[] = [$noreplyaddress, get_string('noreplyname')];
            }
        }

        if (!empty($replyto)) {
            $tempreplyto[] = [$replyto, $replytoname];
        }

        $temprecipients[] = [$user->email, fullname($user)];

        // Set word wrap.
        $mail->WordWrap = $wordwrapwidth;

        if (!empty($from->customheaders)) {
            // Add custom headers.
            if (is_array($from->customheaders)) {
                foreach ($from->customheaders as $customheader) {
                    $mail->addCustomHeader($customheader);
                }
            } else {
                $mail->addCustomHeader($from->customheaders);
            }
        }

        // If the X-PHP-Originating-Script email header is on then also add an additional
        // header with details of where exactly in moodle the email was triggered from,
        // either a call to message_send() or to email_to_user().
        if (ini_get('mail.add_x_header')) {
            // @codingStandardsIgnoreStart
            $stack = debug_backtrace(false);
            // @codingStandardsIgnoreEnd
            $origin = $stack[0];

            foreach ($stack as $depth => $call) {
                if ($call['function'] == 'message_send') {
                    $origin = $call;
                }
            }

            $originheader = $CFG->wwwroot . ' => ' . gethostname() . ':'
                . str_replace($CFG->dirroot . '/', '', $origin['file']) . ':' . $origin['line'];
            $mail->addCustomHeader('X-Moodle-Originating-Script: ' . $originheader);
        }

        if (!empty($CFG->emailheaders)) {
            $headers = array_map('trim', explode("\n", $CFG->emailheaders));
            foreach ($headers as $header) {
                if (!empty($header)) {
                    $mail->addCustomHeader($header);
                }
            }
        }

        if (!empty($from->priority)) {
            $mail->Priority = $from->priority;
        }

        $renderer = $PAGE->get_renderer('core');
        $context = [
            'sitefullname' => $SITE->fullname,
            'siteshortname' => $SITE->shortname,
            'sitewwwroot' => $CFG->wwwroot,
            'subject' => $subject,
            'prefix' => $CFG->emailsubjectprefix,
            'to' => $user->email,
            'toname' => fullname($user),
            'from' => $mail->From,
            'fromname' => $mail->FromName,
        ];
        if (!empty($tempreplyto[0])) {
            $context['replyto'] = $tempreplyto[0][0];
            $context['replytoname'] = $tempreplyto[0][1];
        }
        if ($user->id > 0) {
            $context['touserid'] = $user->id;
            $context['tousername'] = $user->username;
        }

        if (!empty($user->mailformat) && $user->mailformat == 1) {
            // Only process html templates if the user preferences allow html email.

            if (!$messagehtml) {
                // If no html has been given, BUT there is an html wrapping template then
                // auto convert the text to html and then wrap it.
                $messagehtml = trim(text_to_html($messagetext));
            }
            $context['body'] = $messagehtml;
            $messagehtml = $renderer->render_from_template('core/email_html', $context);
        }

        $context['body'] = html_to_text(nl2br($messagetext));
        $mail->Subject = $renderer->render_from_template('core/email_subject', $context);
        $mail->FromName = $renderer->render_from_template('core/email_fromname', $context);
        $messagetext = $renderer->render_from_template('core/email_text', $context);

        // Autogenerate a MessageID if it's missing.
        if (empty($mail->MessageID)) {
            $mail->MessageID = generate_email_messageid();
        }

        if ($messagehtml && !empty($user->mailformat) && $user->mailformat == 1) {
            // Don't ever send HTML to users who don't want it.
            $mail->isHTML(true);
            $mail->Encoding = 'quoted-printable';
            $mail->Body    = $messagehtml;
            $mail->AltBody = "\n$messagetext\n";
        } else {
            $mail->IsHTML(false);
            $mail->Body = "\n$messagetext\n";
        }

        if ($attachment && $attachname) {
            if (preg_match( "~\\.\\.~" , $attachment )) {
                // Security check for ".." in dir path.
                $supportuser = core_user::get_support_user();
                $temprecipients[] = [$supportuser->email, fullname($supportuser, true)];
                $mail->addStringAttachment(
                    'Error in attachment.  User attempted to attach a filename with a unsafe name.',
                    'error.txt', '8bit', 'text/plain');
            } else {
                require_once($CFG->libdir.'/filelib.php');
                $mimetype = mimeinfo('type', $attachname);

                // Before doing the comparison, make sure that the paths are correct (Windows uses slashes in the other direction).
                // The absolute (real) path is also fetched to ensure that comparisons to allowed paths are compared equally.
                $attachpath = str_replace('\\', '/', realpath($attachment));

                // Build an array of all filepaths from which attachments can be added (normalised slashes, absolute/real path).
                $allowedpaths = array_map(function(string $path): string {
                    return str_replace('\\', '/', realpath($path));
                }, [
                    $CFG->cachedir,
                    $CFG->dataroot,
                    $CFG->dirroot,
                    $CFG->localcachedir,
                    $CFG->tempdir,
                    $CFG->localrequestdir,
                ]);

                // Set addpath to true.
                $addpath = true;

                // Check if attachment includes one of the allowed paths.
                foreach (array_filter($allowedpaths) as $allowedpath) {
                    // Set addpath to false if the attachment includes one of the allowed paths.
                    if (strpos($attachpath, $allowedpath) === 0) {
                        $addpath = false;
                        break;
                    }
                }

                // If the attachment is a full path to a file in the multiple allowed paths, use it as is,
                // otherwise assume it is a relative path from the dataroot (for backwards compatibility reasons).
                if ($addpath == true) {
                    $attachment = $CFG->dataroot . '/' . $attachment;
                }

                $mail->addAttachment($attachment, $attachname, 'base64', $mimetype);
            }
        }

        // Check if the email should be sent in an other charset then the default UTF-8.
        if ((!empty($CFG->sitemailcharset) || !empty($CFG->allowusermailcharset))) {

            // Use the defined site mail charset or eventually the one preferred by the recipient.
            $charset = $CFG->sitemailcharset;
            if (!empty($CFG->allowusermailcharset)) {
                if ($useremailcharset = get_user_preferences('mailcharset', '0', $user->id)) {
                    $charset = $useremailcharset;
                }
            }

            // Convert all the necessary strings if the charset is supported.
            $charsets = get_list_of_charsets();
            unset($charsets['UTF-8']);
            if (in_array($charset, $charsets)) {
                $mail->CharSet  = $charset;
                $mail->FromName = core_text::convert($mail->FromName, 'utf-8', strtolower($charset));
                $mail->Subject  = core_text::convert($mail->Subject, 'utf-8', strtolower($charset));
                $mail->Body     = core_text::convert($mail->Body, 'utf-8', strtolower($charset));
                $mail->AltBody  = core_text::convert($mail->AltBody, 'utf-8', strtolower($charset));

                foreach ($temprecipients as $key => $values) {
                    $temprecipients[$key][1] = core_text::convert($values[1], 'utf-8', strtolower($charset));
                }
                foreach ($tempreplyto as $key => $values) {
                    $tempreplyto[$key][1] = core_text::convert($values[1], 'utf-8', strtolower($charset));
                }
            }
        }

        foreach ($temprecipients as $values) {
            $mail->addAddress($values[0], $values[1]);
        }
        foreach ($tempreplyto as $values) {
            $mail->addReplyTo($values[0], $values[1]);
        }

        // Custom method to add cc and bcc.
        foreach ($cc as $values) {
            $mail->addCC($values[0], $values[1]);
        }
        foreach ($bcc as $values) {
            $mail->addBCC($values[0], $values[1]);
        }

        if (!empty($CFG->emaildkimselector)) {
            $domain = substr(strrchr($mail->From, "@"), 1);
            $pempath = "{$CFG->dataroot}/dkim/{$domain}/{$CFG->emaildkimselector}.private";
            if (file_exists($pempath)) {
                $mail->DKIM_domain      = $domain;
                $mail->DKIM_private     = $pempath;
                $mail->DKIM_selector    = $CFG->emaildkimselector;
                $mail->DKIM_identity    = $mail->From;
            } else {
                debugging("Email DKIM selector chosen due to {$mail->From} but no certificate found at $pempath", DEBUG_DEVELOPER);
            }
        }

        if ($mail->send()) {
            set_send_count($user);
            if (!empty($mail->SMTPDebug)) {
                echo '</pre>';
            }
            return true;
        } else {
            // Trigger event for failing to send email.
            $event = \core\event\email_failed::create([
                'context' => context_system::instance(),
                'userid' => $from->id,
                'relateduserid' => $user->id,
                'other' => [
                    'subject' => $subject,
                    'message' => $messagetext,
                    'errorinfo' => $mail->ErrorInfo,
                ],
            ]);
            $event->trigger();
            if (CLI_SCRIPT) {
                mtrace('Error: lib/moodlelib.php email_to_user(): '.$mail->ErrorInfo);
            }
            if (!empty($mail->SMTPDebug)) {
                echo '</pre>';
            }
            return false;
        }
    }

}
