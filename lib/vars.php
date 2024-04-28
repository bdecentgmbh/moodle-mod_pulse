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

use mod_pulse\helper as pulsehelper;
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
     * Course context.
     *
     * @var \context
     */
    protected $coursecontext = null;

    /**
     * Course element without updateing its vars.
     *
     * @var stdclass
     */
    protected $orgcourse = null;

    /**
     * Sets up and retrieves the API objects.
     *
     * @param mixed $user User data record
     * @param mixed $course Course data object
     * @param mixed $sender Sender user record data.
     * @param mixed $pulse Pulse instance record data.
     * @return void
     */
    public function __construct($user, $course, $sender, $pulse) {
        global $CFG, $USER;

        $newuser = !empty($user->id) ? $user : $USER;
        self::convert_varstime_format($newuser);
        $this->user =& $newuser;

        $this->sender =& $sender;
        $wwwroot = $CFG->wwwroot;
        $this->pulse = $pulse;

        self::convert_varstime_format($pulse);
        $this->mod = $pulse; // Auomation templates pulse is used as module.

        $this->orgcourse = clone $course; // Store the course record without before update its format.
        self::convert_varstime_format($course);
        $this->course = $course;
        // Course context.
        $this->coursecontext = \context_course::instance($this->course->id);

        if (!empty($course->id)) {
            $this->course->url = new moodle_url($wwwroot .'/course/view.php', ['id' => $this->course->id]);
        }
        if (!empty($user->id)) {
            $this->url = new moodle_url($wwwroot .'/user/profile.php', ['id' => $this->user->id]);
        }
        $this->site = $this->get_sitedata();

        $this->enrolment = $this->get_user_enrolment();

    }

    /**
     * Check whether it is ok to call certain methods of this class as a substitution var
     *
     * @param string $methodname = text;
     * @return string
     **/
    private static function ok2call($methodname) {
        return ($methodname != "vars" && $methodname != "__construct" && $methodname != "__get"
            && $methodname != "ok2call" && $methodname != "convert_varstime_format");
    }

    /**
     * Converts specific timestamp values in an array to user-readable time format.
     *
     * @param stdclass $var The array containing timestamp values to be converted.
     */
    private static function convert_varstime_format(&$var) {
        if (empty($var)) {
            return;
        }
        // Update the timestamp to user readable time.
        array_walk($var, function(&$value, $key) {
            if (in_array(strtolower($key), ['timecreated', 'timemodified', 'startdate', 'enddate', 'firstaccess',
                'lastaccess', 'lastlogin', 'currentlogin', 'timecreated', 'starttime', 'endtime',
                ])) {
                $value = $value ? userdate($value) : '';
            }
            // Update the status to user readable strings.
            if (in_array(strtolower($key), ['visible', 'groupmode', 'groupmodeforce', 'defaultgroupingid'])) {
                $value = $value == 1 ? get_string('enabled', 'pulse') : get_string('disabled', 'pulse');
            }

            if (strtolower($key) == 'lang') {
                // Get the list of translations.
                $translations = get_string_manager()->get_list_of_translations();
                $value = $translations[$value] ?? '';
            }
        });

        $var = (object) $var;
    }

    /**
     * Set up all the methods that can be called and used for substitution var in email templates.
     *
     * @return array
     *
     **/
    public static function vars() {
        global $DB, $SITE;

        $reflection = new ReflectionClass("pulse_email_vars");
        $amethods = $reflection->getMethods();

        // These fields refer to the objects declared at the top of this class. User_ -> $this->user, etc.
        $result = [
            // User data fields.
            'User' => self::user_profile_fields(),
            // Course data fields.
            'Course' => self::course_fields(),
            // Sender data fields.
            'Sender' => self::sender_data_fields(),
            // Enrolment data fields.
            'Enrolment' => self::enrolement_data_fields(),
            // Site data fields.
            'Site' => self::site_data_fields(),
            // Course activities data fields.
            'Mod' => self::course_activities_data_fields(),
            // Meta fields.
            'Mod_Metadata' => self::module_meta_fields(),
        ];

        $result += \mod_pulse\extendpro::pulse_extend_reaction_placholder();

        $result += ['others' => ['siteurl', 'courseurl', 'linkurl', 'completionstatus']];

        // Remove empty vars.
        $result = array_filter($result);

        return $result ?? [];
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

                if (method_exists($this, $object)) {
                    return $this->$object($property); // Call the method.
                } else if ($this->$object == null) {
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
     * Reaction placeholders dynamic data.
     * Pro featuer extended from locla_pulsepro.
     *
     * @return void
     */
    public function reaction() {
        return \mod_pulse\extendpro::pulse_extend_reaction($this);
    }

    /**
     * Completion status.
     *
     * @return string
     */
    public function completionstatus() {
        global $DB;

        $completion = new \completion_info($this->course);
        $coursecontext = $this->coursecontext ?? \context_course::instance($this->course->id);

        if ($completion->is_course_complete($this->user->id)) {
            return get_string('completed');
        } else if ($DB->record_exists('course_completions', ['course' => $this->course->id, 'userid' => $this->user->id])) {
            return get_string('inprogress');
        } else if (is_enrolled($coursecontext, $this->user->id)) {
            return get_string('completionenrolled', 'pulse');
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

        $enrolmanager = new course_enrolment_manager($PAGE, $this->orgcourse);
        $enrolments = $enrolmanager->get_user_enrolments($this->user->id);

        if (!empty($enrolments)) {
            $firstinstance = current($enrolments);
            $percentage = \core_completion\progress::get_course_progress_percentage($this->orgcourse, $this->user->id);
            $progress = !empty($percentage) ? $percentage : 0;

            return (object) [
                'progress' => round($progress) .'%',
                'status' => ($firstinstance->status == 1) ? get_string('suspended', 'mod_pulse') : get_string('active'),
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

            $userfields = [
                'firstname', 'lastname', 'fullname', 'email', 'username',
                'description', 'department', 'phone', 'address', 'city', 'country',
                'institution',
            ];

            $userdbfields = $DB->get_columns('user');

            $profilefields = array_map(function($value) {
                return str_replace('profile_field', 'profilefield', $value);
            }, (new auth_plugin_base)->get_custom_user_profile_fields());

            $fields = array_merge($userfields, array_keys($userdbfields), array_values($profilefields));
            $fields = array_unique($fields);

            array_walk($fields, function(&$value) {
                $value = 'User_'.ucwords($value);
            });

            $fields = array_values($fields);

            $removefields = [
                'User_confirmed', 'User_policyagreed', 'User_deleted', 'User_suspended', 'User_mnethostid', 'User_password',
                'User_emailstop', 'User_descriptionformat', 'User_mailformat', 'User_maildigest', 'User_maildisplay',
                'User_autosubscribe', 'User_trackforums', 'User_timemodified', 'User_trustbitmask', 'User_imagealt',
                'User_moodlenetprofile',
            ];
            $fields = array_filter($fields, fn($field) => !in_array($field, $removefields));
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

            $coursefields = [
                'fullname', 'shortname', 'summary', 'courseurl', 'startdate',
                'enddate', 'id', 'category', 'idnumber', 'format', 'visible',
                'groupmode', 'groupmodeforce', 'defaultgroupingid', 'lang', 'calendartype', 'theme', 'timecreated',
                'timemodified', 'enablecompletion',
            ];

            $sql = "SELECT cf.shortname FROM {customfield_field} cf
            JOIN {customfield_category} cc ON cc.id = cf.categoryid
            WHERE cc.component = :component";
            $records = $DB->get_records_sql($sql, ['component' => 'core_course']);

            $customfields = array_map(function($value) {
                return 'customfield_'.$value;
            }, array_keys($records));

            $fields = array_merge($coursefields, array_values($customfields));

            array_walk($fields, function(&$value) {
                $value = 'Course_'.ucwords($value);
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
                    $value = "Mod_metadata".$value;
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
        // Verify the face to face is installed. if not, prevent session placeholder inclusion.
        if (!file_exists($CFG->dirroot.'/mod/facetoface/lib.php')) {
            return [];
        }

        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        $fields = [
            'Starttime', 'Startdate', 'Enddate', 'Endtime', 'Link', 'Details',
            'Discountcode', 'Capacity', 'Normalcost', 'Discountcost',
            'Type',
        ];
        $customfields = facetoface_get_session_customfields();
        foreach ($customfields as $field) {
            $fields[] = 'customfield_' . $field->shortname;
        }
        return array_map(fn($field) => 'Mod_session_'.$field, $fields);
    }

    /**
     * Sender information fields.
     *
     * @return array
     */
    public static function sender_data_fields() {
        return [
            // Sender information fields .
            'Sender_Firstname', 'Sender_Lastname', 'Sender_Email',
        ];
    }

    /**
     * Enrolment information fields.
     *
     * @return array
     */
    public static function enrolement_data_fields() {
        return [
            // Sender information fields .
            'Enrolment_Status', 'Enrolment_Progress', 'Enrolment_Startdate', 'Enrolment_Enddate',
        ];
    }

    /**
     * Site information fields.
     *
     * @return array
     */
    public static function site_data_fields() {
        return [
            // Site fields.
            'Site_Fullname', 'Site_Shortname', 'Site_Summary', 'Site_Siteurl',
        ];
    }

    /**
     * Course activity information fields.
     *
     * @return array
     */
    public static function course_activities_data_fields() {
        return [
            // Activities Fields.
            'Mod_Type', 'Mod_Name', 'Mod_Intro',
        ];
    }

    /**
     * Get the site data.
     *
     * @return array
     */
    public function get_sitedata() {
        global $SITE;

        return (object) [
            'fullname' => $SITE->fullname,
            'shortname' => $SITE->shortname,
            'summary' => $SITE->summary,
            'siteurl' => self::siteurl(),
        ];
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
