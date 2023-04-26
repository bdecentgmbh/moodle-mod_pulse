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
        $this->course =& $course;
        if (!empty($course->id)) {
            $this->course->url = new moodle_url($wwwroot .'/course/view.php', array('id' => $this->course->id));
        }
        if (!empty($user->id)) {
            $this->url = new moodle_url($wwwroot .'/user/profile.php', array('id' => $this->user->id));
        }
        $this->site = get_site();
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
        $reflection = new ReflectionClass("pulse_email_vars");
        $amethods = $reflection->getMethods();

        // These fields refer to the objects declared at the top of this class. User_ -> $this->user, etc.
        $result = array(
            // User fields.
            'User_FirstName', 'User_LastName', 'User_Email', 'User_Username',
            'User_Institution', 'User_Department',
            'User_Address', 'User_City', 'User_Country',
            // Course fields .
            'Course_FullName', 'Course_ShortName', 'courseurl',
            // Site fields.
            'Site_FullName', 'Site_ShortName', 'Site_Summary',
            // Sender information fields .
            'Sender_FirstName', 'Sender_LastName', 'Sender_Email',
            // Miscellaneouss fields.
            'linkurl', 'siteurl', 'reaction'
        );

        // Add all methods of this class that are ok2call to the $result array as well.
        // This means you can add extra methods to this class to cope with values that don't fit in objects mentioned above.
        // Or to create methods with specific formatting of the values (just don't give those methods names starting with
        // 'User_', 'Course_', etc).
        foreach ($amethods as $method) {
            if (self::ok2call($method->name) && !in_array($method->name, $result) ) {
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
        return pulse_extend_reaction($this);
    }
}


// If the version is not iomad, set empty emailvars class for provide previous pro versions compatibility.
if (!file_exists($CFG->dirroot.'/local/iomad/version.php')) {

    class EmailVars extends pulse_email_vars{

        /**
         * Set up all the methods that can be called and used for substitution var in email templates.
         *
         * @return array
         *
         **/
        public static function vars() {
            return parent::vars();
        }
    }
}
