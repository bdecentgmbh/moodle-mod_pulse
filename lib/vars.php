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
 * Email template placeholder definition. Modified version of IOMAD email templates emailvars.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('No direct access');

/**
 * Filter for notification content placeholders.
 */
class pulse_email_vars {
    // Objects the vars refer to.

    /**
     * User data record.
     *
     * @var object
     */
    protected $user = null;

    /**
     * Course record.
     *
     * @var object
     */
    protected $course = null;

    /**
     * Site record.
     *
     * @var object
     */
    protected $site = null;

    /**
     * Site url.
     *
     * @var string
     */
    protected $url = null;

    /**
     * User who send the notifications to users.
     *
     * @var object
     */
    protected $sender = null;

    /**
     * Pulse instance data record.
     *
     * @var object
     */
    public $pulse = null;

    /**
     * User enrolment start and end date for the current course.
     *
     * @var object
     */
    public $enrolment = null;

    /**
     * Module data.
     *
     * @var object
     */
    protected $mod = null;

    /**
     * Placeholder doesn't have dynamic filter then it will replaced with blank value.
     *
     * @var string
     */
    protected $blank = "[blank]";

    /**
     * Sets up and retrieves the API objects.
     *
     * @param  mixed $user User data record
     * @param  mixed $course Course data object
     * @param  mixed $sender Sender user record data.
     * @param  mixed $pulse Pulse instance record data.
     * @return void
     */
    public function __construct($user, $course, $sender, $pulse) {
        global $CFG;

        $this->user =& $user;
        $this->sender =& $sender;
        $wwwroot = $CFG->wwwroot;
        $this->pulse = $pulse;

        $this->mod = $pulse; // Auomation templates pulse is used as module.

        $this->course =& $course;
        if (!empty($course->id)) {
            $this->course->url = new moodle_url($wwwroot .'/course/view.php', array('id' => $this->course->id));
        }
        if (!empty($user->id)) {
            $this->url = new moodle_url($wwwroot .'/user/profile.php', array('id' => $this->user->id));
        }
        $this->site = get_site();
        $this->enrolment = $this->get_user_enrolment();
    }

    /**
     * Check whether it is ok to call certain methods of this class as a substitution var
     *
     * @param string $methodname = text;
     * @return string
     **/
    private static function ok2call($methodname) {
        return ($methodname != "vars" && $methodname != "__construct" && $methodname != "__get" && $methodname != "ok2call");
    }

    /**
     * Set up all the methods that can be called and used for substitution var in email templates.
     *
     * @return array
     *
     **/
    public static function vars() {
        global $DB;

        $reflection = new ReflectionClass("pulse_email_vars");
        $amethods = $reflection->getMethods();

        // These fields refer to the objects declared at the top of this class. User_ -> $this->user, etc.

        $userfields = self::user_profile_fields();
        $coursefields = self::course_fields();

        $result = array_merge($userfields, $coursefields);


        $otherfields = array(
            // Course fields .
            'courseurl', 'enrolment_startdate', 'enrolment_enddate',
            // Site fields.
            'Site_FullName', 'Site_ShortName', 'Site_Summary',
            // Sender information fields .
            'Sender_FirstName', 'Sender_LastName', 'Sender_Email',
            // Miscellaneouss fields.
            'linkurl', 'siteurl', 'reaction',
            // Activities Fields.
            'Mod_Type', 'Mod_Name', 'Mod_Intro',
        );

        $result = array_merge($result, array_values($otherfields));

        $result = array_merge($result, self::module_meta_fields());

        $result = array_merge($result, self::session_fields());

        // List of methods which doesn't used as placeholders.
        $novars = ['get_user_enrolment', 'user_profile_fields', 'course_fields', 'module_meta_fields', 'session_fields'];

        // Add all methods of this class that are ok2call to the $result array as well.
        // This means you can add extra methods to this class to cope with values that don't fit in objects mentioned above.
        // Or to create methods with specific formatting of the values (just don't give those methods names starting with
        // 'User_', 'Course_', etc).
        foreach ($amethods as $method) {
            if (self::ok2call($method->name) && !in_array($method->name, $result) && !in_array($method->name, $novars) ) {
                $result[] = $method->name;
            }
        }



        return $result;
    }

    /**
     * Trap calls to non-existent methods of this class, that can then be routed to the appropriate objects.
     * @param string $name Placeholder used on the template.
     */
    public function __get($name) {
        if (isset($name)) {
            if (property_exists($this, $name)) {
                return $this->$name;
            }
            preg_match('/^(.*)_(.*)$/', $name, $matches);
            if (isset($matches[1])) {

                $object = strtolower($matches[1]);
                $property = strtolower($matches[2]);

                /* print_object($matches);
                print_object($this->mod);
                print_object($property); */

                if ($this->$object == null) {
                    return $this->blank;
                }

                if (isset($this->$object->$property)) {
                    return $this->$object->$property;
                } else if (method_exists($this->$object, '__get')) {
                    return $this->$object->__get($property);
                } else if (method_exists($this->$object, 'get')) {
                    return $this->$object->get($property);
                } else {
                    return $this->blank;
                }
            } else if (self::ok2call($name)) {
                return $this->$name();
            }
        }
    }

    /**
     * Provide the CourseURL method for templates.
     *
     * returns text;
     *
     **/
    public function courseurl() {
        global $CFG;

        if (empty($CFG->allowthemechangeonurl)) {
            return $this->course->url;
        } else {
            return new moodle_url($this->course->url);
        }
    }

    /**
     * Provide the SiteURL method for templates.
     *
     * returns text;
     *
     **/
    public function siteurl() {
        global $CFG;

        $wwwroot = $CFG->wwwroot;
        return $wwwroot;
    }

    /**
     * Provide the LinkURL method for templates.
     *
     * returns text;
     *
     **/
    public function linkurl() {
        global $CFG;

        if (empty($CFG->allowthemechangeonurl)) {
            return $this->url;
        } else {
            return new moodle_url($this->url);
        }
    }

    /**
     * Reaction placeholders dynamic data.
     * Pro featuer extended from locla_pulsepro.
     *
     * @return void
     */
    public function reaction() {
        return \mod_pulse\extendpro::pulse_extend_reaction($this);
    }

    /**
     * Course progress.
     *
     * @return string
     */
    public function courseprogress() {
        return \core_completion\progress::get_course_progress_percentage($this->course, $this->user->id);
    }

    /**
     * Completion status.
     *
     * @return string
     */
    public function completionstatus() {
        global $DB;

        $completion = new \completion_info($this->course->id);
        $coursecontext = \context_course::instance($this->course->id);

        if ($completion->is_course_complete($this->user->id)) {
            return get_string('completed');
        } else if ($DB->record_exists('course_completions', ['course' => $this->course->id, 'userid' => $this->user->id])) {
            return get_string('inprogress');
        } else if (is_enrolled($coursecontext, $this->user->id)) {
            return get_string('enroled');
        }
        return '';
    }

    /**
     * Find the user enrolment start date and enddate for the current course.
     *
     * @return array
     */
    public function get_user_enrolment() {
        global $PAGE, $CFG;

        $emptystartdate = get_string('enrolmentemptystartdate', 'mod_pulse');
        $emptyenddate = get_string('enrolmentemptyenddate', 'mod_pulse');

        if (empty($this->course) || empty($this->user)) {
            return (object) ['startdate' => $emptystartdate, 'enddate' => $emptyenddate];
        }
        require_once($CFG->dirroot.'/enrol/locallib.php');

        $enrolmanager = new course_enrolment_manager($PAGE, $this->course);
        $enrolments = $enrolmanager->get_user_enrolments($this->user->id);

        if (!empty($enrolments)) {
            $firstinstance = current($enrolments);
            return (object) [
                'startdate' => $firstinstance->timestart
                    ? userdate($firstinstance->timestart, get_string('strftimedatetimeshort', 'langconfig')) : $emptystartdate,
                'enddate' => $firstinstance->timeend
                    ? userdate($firstinstance->timeend, get_string('strftimedatetimeshort', 'langconfig')) : $emptyenddate,
            ];
        }
        return (object) ['startdate' => $emptystartdate, 'enddate' => $emptyenddate];
    }

    /**
     * Include user profile fields with custom profile fields.
     *
     * @return array List of user profile and customfields.
     */
    public static function user_profile_fields() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/lib/authlib.php');

        static $fields;

        if ($fields === null) {
            $fields = [];

            $userfields = $DB->get_columns('user');

            $profilefields = array_map(function($value) {
                return str_replace('profile_field', 'profilefield', $value);
            }, (new auth_plugin_base)->get_custom_user_profile_fields());

            $fields = array_merge(array_keys($userfields), array_values($profilefields));

            array_walk($fields, function(&$value) {
                $value = 'User_'.$value;
            });


            $fields = array_values($fields);
        }

        return $fields;
    }


    /**
     * List of course fields and custom fields.
     *
     * @return array List of course fields.
     */
    public static function course_fields() {
        global $DB;

        static $fields;

        if ($fields === null) {
            $fields = [];

            $coursefields = $DB->get_columns('course');
            $records = $DB->get_records('customfield_field', [], '', 'shortname');

            $customfields = array_map(function($value) {
                return 'customfield_'.$value;
            }, array_keys($records));

            $fields = array_merge(array_keys($coursefields), array_values($customfields));

            array_walk($fields, function(&$value) {
                $value = 'Course_'.$value;
            });

            $fields = array_values($fields);
        }

        return $fields;
    }

    /**
     * Include Module meta fields.
     *
     * @return void
     */
    public static function module_meta_fields() {
        global $DB;

        static $fields;

        if ($fields === null) {
            $fields = [];

            if (!$DB->get_manager()->table_exists('local_metadata_field')) {
                return [];
            }

            $records = $DB->get_records('local_metadata_field', ['contextlevel' => CONTEXT_MODULE], '', 'shortname');

            if (!empty($records)) {
                $fields = array_keys($records);

                array_walk($fields, function(&$value) {
                    $value = 'Mod_Metadata'.$value;
                });
            }

            $fields = array_values($fields);
        }

        return $fields;
    }

    /**
     * Session fields
     *
     * @return array
     */
    public static function session_fields() {
        global $CFG;

        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        $fields = [
            'discountcode', 'details', 'capacity', 'normalcost', 'discountcost', 'starttime', 'startdate', 'enddate', 'endtime'
        ];
        $customfields = facetoface_get_session_customfields();
        foreach ($customfields as $field) {
            $fields[] = 'customfield_' . $field->shortname;
        }
        return array_map(fn($field) => 'Mod_session_'.$field, $fields);
    }

}


// If the version is not iomad, set empty emailvars class for provide previous pro versions compatibility.
if (!file_exists($CFG->dirroot.'/local/iomad/version.php')) {

    /**
     * EMAIL vars for support previous version pulsepro.
     */
    class EmailVars extends pulse_email_vars {

        /**
         * Set up all the methods that can be called and used for substitution var in email templates.
         * There is not use for this function, FIX for CI.
         *
         * @return array
         **/
        public static function vars() {
            $test = ''; // FIX for Moodle CI codechecker.
            return parent::vars();
        }
    }
}
