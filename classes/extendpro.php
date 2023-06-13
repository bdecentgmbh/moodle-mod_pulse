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
 * Pulse instance extend features file. contains pro feature extended methods
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

/**
 * Extend the pro feature to pulse instances.
 */
class extendpro {

    /**
     * Trigger the add pulse instance.
     *
     * @param  mixed $pulseid
     * @param  mixed $pulse
     * @return void
     */
    public static function pulse_extend_add_instance($pulseid, $pulse) {
        $callbacks = get_plugins_with_function('extend_pulse_add_instance');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($pulseid, $pulse);
            }
        }
    }

    /**
     * Trigger pulse extended plugins to do their own update steps.
     *
     * @param  mixed $pulse Pulse instance data.
     * @param  mixed $context Context module.
     * @return void
     */
    public static function pulse_extend_update_instance($pulse, $context) {
        $callbacks = get_plugins_with_function('extend_pulse_update_instance');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($pulse, $context);
            }
        }
    }

    /**
     * Trigger pulse extended plugins delete function to do their own delete steps.
     *
     * @param  mixed $cmid Module context id
     * @param  mixed $pulseid Pulse instance id.
     * @return void
     */
    public static function pulse_extend_delete_instance($cmid, $pulseid) {
        $callbacks = get_plugins_with_function('extend_pulse_delete_instance');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($cmid, $pulseid);
            }
        }
    }

    /** Inject form elements into mod instance form.
     *
     * @param  mform $mform the form to inject elements into.
     * @param  mixed $instance Pulse instance.
     * @param  mixed $method Method of form fields (=reaction only returns the reaction form fields)
     * @return void
     */
    public static function mod_pulse_extend_form($mform, $instance, $method='') {
        $callbacks = get_plugins_with_function('extend_pulse_form');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($mform, $instance, $method);
            }
        }
    }

    /** Extende the pro plugins validation error messages.
     *
     * @param  mixed $data module form submitted data.
     * @param  mixed $files Module form submitted files.
     * @return array list of validation errors.
     */
    public static function mod_pulse_extend_formvalidation($data, $files) {
        $callbacks = get_plugins_with_function('extend_pulse_validation');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                return $pluginfunction($data, $files);
            }
        }
    }

    /** Inject form elements into mod instance form.
     * @param mform $mform the form to inject elements into.
     */
    public static function mod_pulse_extend_formdata($mform) {
        $callbacks = get_plugins_with_function('extend_pulse_formdata');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($mform);
            }
        }
    }

    /** Extend form post process method from pro plugin.
     * @param object $data module form submitted data object.
     */
    public static function pulse_extend_postprocessing($data) {
        $callbacks = get_plugins_with_function('extend_pulse_postprocessing');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($data);
            }
        }
    }

    /**
     * Extended the support of data processing before defalut values are set to form.
     *
     * @param  mixed $defaultvalues Current default values.
     * @param  mixed $currentinstance status of instance is current (true/false)
     * @param  mixed $context Module context data record.
     * @return void
     */
    public static function pulse_extend_preprocessing(&$defaultvalues, $currentinstance, $context) {
        $callbacks = get_plugins_with_function('extend_pulse_preprocessing');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $pluginfunction($defaultvalues, $currentinstance, $context);
            }
        }
    }

    /**
     * Call the extended email placeholder filters to replace the content.
     *
     * @param  mixed $instance Pulse instance data object.
     * @param  mixed $displaytype Location to display the reaction.
     * @return string $html
     */
    public static function pulse_extend_reaction($instance, $displaytype='notification') {
        $html = '';
        $callbacks = get_plugins_with_function('extend_pulse_reaction');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $html .= $pluginfunction($instance, $displaytype);
            }
        }
        return $html;
    }


    /**
     * Check the pulsepro extended the invitation method.
     * if extended the invitation then the invitations are send using pulse pro plugin.
     * @return void
     */
    public static function pulse_extend_invitation() {
        $callbacks = get_plugins_with_function('extend_pulse_invitation');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                return $pluginfunction();
            }
        }
    }

    /**
     * List of extended the function used in the backup steps.
     *
     * @param  mixed $pulse
     * @param  mixed $userinfo
     * @return void
     */
    public static function pulse_extend_backup_steps($pulse, $userinfo) {
        $callbacks = get_plugins_with_function('extend_pulse_backup_steps');
        if (!empty($callbacks)) {
            foreach ($callbacks as $type => $plugins) {
                foreach ($plugins as $plugin => $pluginfunction) {
                    return $pluginfunction($pulse, $userinfo);
                }
            }
        }
        return $pulse;
    }

    /**
     * List of extended plugins restore contents.
     *
     * @param  mixed $contents
     * @return void
     */
    public static function pulse_extend_restore_content(&$contents) {
        $callbacks = get_plugins_with_function('extend_pulse_restore_content');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $contents = $pluginfunction($contents);
            }
        }
    }

    /**
     * Extended plugins restore structures used in the acitivty restore.
     *
     * @param  mixed $paths
     * @return void
     */
    public static function pulse_extend_restore_structure(&$paths) {
        $callbacks = get_plugins_with_function('extend_pulse_restore_structure');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $paths = $pluginfunction($paths);
            }
        }
    }

    /**
     * List of extended plugins fileareas list to add into pluginfile function.
     *
     * @return array
     */
    public static function pulse_extend_filearea(): array {
        $callbacks = get_plugins_with_function('extend_pulse_filearea');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $fileareas = $pluginfunction();
                return $fileareas;
            }
        }
        return [];
    }

    /**
     * Extend the pro features of preset. Triggered during the import preset data clean.
     *
     * @param string $method Preset method to extend
     * @param array $backupdata Preset template data.
     * @return void
     */
    public static function pulse_extend_preset($method, &$backupdata) {
        $callbacks = get_plugins_with_function('extend_preset_formatdata');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $backupdata = $pluginfunction($method, $backupdata);
            }
        }
    }


    /**
     * Extend the pro features of preset. Convert the record data format into moodle form editor format.
     *
     * @param string $pulseid Preset method to extend
     * @param array $configdata Custom config data.
     * @return void
     */
    public static function pulse_preset_update($pulseid, $configdata) {
        $callbacks = get_plugins_with_function('extend_preset_update');
        foreach ($callbacks as $type => $plugins) {
            foreach ($plugins as $plugin => $pluginfunction) {
                $backupdata = $pluginfunction($pulseid, $configdata);
            }
        }
    }

}
