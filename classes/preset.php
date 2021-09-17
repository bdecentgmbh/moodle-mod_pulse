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
 * Create pulse using presets, Preset template generate and module restore method definied.
 *
 * @package   mod_pulse
 * @copyright 2021, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pulse;

use context_module;
use core_plugin_manager;
use moodle_exception;
use stdclass;

defined( 'MOODLE_INTERNAL') || die(' No direct access ');

require_once($CFG->libdir.'/formslib.php');

/**
 * Create pulse using the backup file of mod pulse. Defined preset form to extract the template file data.
 */
class preset extends \moodleform  {

    /**
     * Preset form to replace the module form during the apply and customize method.
     *
     * @var stdclass
     */
    public $_form;

    /**
     * Pulse module context.
     *
     * @var \context_module
     */
    public $modulecontext;

    /**
     * Pulse module data record.
     *
     * @var stdclass
     */
    public $pulse;

    /**
     * Course id.
     *
     * @var int
     */
    public $courseid;

    /**
     * Restore controller, helps to restore the preset template as pulse module.
     *
     * @var stdclass
     */
    protected $controller;

    /**
     * Module form data.
     *
     * @var array
     */
    public $modformdata = [];

    /**
     * Basic data stored as moodle form element, used to pass the preset and course data to modal.
     *
     * @return void
     */
    public function definition() {
        // Empty form content.
        $this->_form->addElement('hidden', 'importmethod');
        $this->_form->setType('importmethod', PARAM_INT);

        $this->_form->addElement('hidden', 'courseid', $this->courseid);
        $this->_form->setType('courseid', PARAM_INT);

        $this->_form->addElement('hidden', 'presetid', $this->presetid);
        $this->_form->setType('presetid', PARAM_INT);
    }

    /**
     * Prepare and process the preset data like description and instructions.
     *
     * @param int $presetid ID of selected preset.
     * @param int $courseid ID of current course.
     * @param stdclass $coursecontext Course context.
     */
    public function __construct(int $presetid, int $courseid, $coursecontext=null) {
        $this->courseid = $courseid;
        $this->presetid = $presetid;
        $this->course = get_course($courseid);
        $this->presetdata();
        parent::__construct();
    }

    /**
     * Load preview content of selected preset with available configurable form field elements in modal.
     *
     * @return string
     */
    public function output_fragment(): string {
        global $OUTPUT;
        $this->load_forms();
        $presethtml = $OUTPUT->render_from_template('mod_pulse/preset', $this->preset);
        return $presethtml;
    }

    /**
     * Fetch the stored preset data,  and process the text editors content, to filter the multilang and files.
     *
     * @return void
     */
    public function presetdata(): void {
        global $DB;
        $this->preset = $DB->get_record('pulse_presets', ['id' => $this->presetid]);
        if (!empty($this->preset)) {
            $description = file_rewrite_pluginfile_urls(
                $this->preset->description, 'pluginfile.php', \context_system::instance()->id,
                'mod_pulse', 'description', $this->preset->id
            );
            $instruction = file_rewrite_pluginfile_urls(
                $this->preset->instruction, 'pluginfile.php', \context_system::instance()->id,
                'mod_pulse', 'instruction', $this->preset->id
            );

            $this->preset->title = format_text($this->preset->title);
            $this->preset->description = format_text($description, FORMAT_HTML);
            $this->preset->instruction = format_text($instruction, FORMAT_HTML);
        }
    }

    /**
     * Convert all the selected configurable parameters into moodle form element.
     * Then, the modal will display the config params for the user. It helps users to customize before applying.
     *
     * Fetch all the available form elements from module pulse.
     * Then copy the selected configurable params into preset form elements.
     * Finally, it assigns the HTML of preset form elements as config params.
     *
     * @return void
     */
    public function load_forms(): void {
        global $PAGE, $CFG;
        $configparams = (isset($this->preset->configparams)) ? json_decode($this->preset->configparams, true) : [];

        if (!empty($configparams)) {
            // Prevent the javascript collections due to the duplicate of preset and email placeholder js inclusion.
            $PAGE->end_collecting_javascript_requirements();

            require_once($CFG->dirroot . '/course/modlib.php');
            require_once($CFG->dirroot.'/mod/pulse/mod_form.php');

            $course = get_course($this->courseid);
            list($module, $context, $cw, $cm, $data) = \prepare_new_moduleinfo_data($course, 'pulse', 0);
            $section = (isset($configparams['section'])) ? $configparams['section'] : 1;
            $pulseform = new \mod_pulse_mod_form($data, $section, $cm, $course);
            // List of available elements in module pulse form.
            foreach ($pulseform->_form->_elements as $key => $element) {

                $hide = ['hidden', 'html', 'submit', 'static'];
                if (in_array($element->_type, $hide) || $element instanceof \MoodleQuickForm_group) {
                    continue;
                }

                if (in_array($element->_attributes['name'], array_keys($configparams))) {
                    $elementname = $element->_attributes['name'];
                    $elem = $pulseform->_form->getElement($elementname);
                    if ($elem->_type == 'editor' || $elem->_type == 'autocomplete') {
                        // Prevent the confilict with module form editors.
                        // Using same names in editor elements in same page, not load the text editors in second elements.
                        $elem->_attributes['name'] = 'preseteditor_'.$elem->_attributes['name'];
                    }
                    $elem->_label = isset($configparams[$elem->_attributes['name']])
                        ? $configparams[$elem->_attributes['name']] : $elem->_label;
                    $this->_form->addElement($elem);
                }
            }
            $this->add_action_buttons(false, 's');
            // Start to collect the javascripts.
            $PAGE->start_collecting_javascript_requirements();
        }
        // Render all the configurable params form into html.
        $this->preset->configparams = $this->render();
    }

    /**
     * Basic create pulse module dataset.
     * Set the pulse module form data fetched from the current create module form.
     * Afterwards override these datas with configurable params and backup module data.
     *
     * @param array $modformdata Current create module form dataset.
     * @return void
     */
    public function set_modformdata($modformdata) {
        $this->modformdata = $modformdata;
    }

    /**
     * Extract the preset files and trigger the import method.
     *
     * Apply the selected preset to the current adding pulse module. Creates the temp directory for backup.
     * Extract the xml data files from selected preset template backup(mbz) file into the backup temp directory
     *
     * @param [type] $configdata
     * @return void
     */
    public function apply_presets($configdata) {

        if (empty($this->preset->id)) {
            return false;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'mod_pulse', 'preset_template', $this->preset->id, '', false);
        $files = array_values($files);

        if (!isset($files[0])) {
            throw new \moodle_exception('activitybackupnotset', 'pulse');
        }

        $file = $files[0];

        $fp = get_file_packer('application/vnd.moodle.backup');
        $backuptempdir = make_backup_temp_directory('preset' . $this->preset->id);
        $files[0]->extract_to_pathname($fp, $backuptempdir);

        return $this->import_presets('preset'.$this->preset->id, $configdata);
    }

    /**
     * Remove all the empty config params from custom config params. Otherwise it makes the backup data empty.
     *
     * @param array $config custom configurable params data.
     * @return array
     */
    public function clear_empty_data(array $config): array {

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->clear_empty_data($value);
                continue;
            }
            if (trim($value)) {
                $config[$key] = $value;
            } else {
                unset($config[$key]);
            }

        }
        return $config;
    }

    /**
     * Update the format of DB record data into moodle editor form element format.
     *
     * @param array $data Element record data.
     * @param string $name Element form name.
     * @param string $editor Element name with Editor keyword.
     * @return void
     */
    public static function format_editordata(array &$data, string $name, string $editor) {
        if (isset($data[$name])) {
            $data[$editor] = array(
                'text' => $data[$name],
                'format' => $data[$name.'format']
            );
        }
    }

    /**
     * Available pulse field to update to pulse table after the restore of preset.
     *
     * @return array List of pulse module record fields.
     */
    public static function pulsefields(): array {
        return [
            'name',
            'intro',
            'introformat',
            'pulse_subject',
            'pulse_content',
            'pulse_contentformat',
            'pulse',
            'diff_pulse',
            'completionavailable',
            'completionself',
            'completionapproval',
            'completionapprovalroles',
        ];
    }

    /**
     * Check custom config params has contains any field to update.
     *
     * @param array $configdata Custom configurable fields.
     * @param array $fields Record fields.
     * @return bool True if contains, otherwise false.
     */
    public static function hasupdatefields(array $configdata, $fields=null): bool {
        $fields = ($fields) ? $fields : self::pulsefields();
        if (array_intersect(array_keys($configdata), $fields)) {
            return true;
        }
        return false;
    }

    /**
     * Import the preset template with custom config data as a new pulse module.
     * Contains two methods, Save, Customize.
     * When the save method is triggered, it will create the module using moodle basic restore method.
     * Then apply the custom config data directly into tables.
     * On customize method, It fetches all the pulse module-related data from backup XML files.
     * Set as the default value for the pulse module.
     * Then returns the form content to replace with the current mod form.So the user can able to customize it before applying it.
     *
     * @param string $backuptempdir Backup files extracted directory root.
     * @param array $configdata Custom config params data.
     * @return string|json Redirect course url for save method or Module Form html to replace when import method is customize.
     */
    public function import_presets($backuptempdir, $configdata) {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/mod/pulse/locallib.php');

        if (isset($configdata['pulse_content'])) {
            $configdata['pulse_contentformat'] = $configdata['pulse_contenteditor']['format'];
            $configdata['pulse_content'] = $configdata['pulse_contenteditor']['text'];
        }
        $configdata = array_filter($configdata, function($value) {
            return ($value !== null && $value != "") ? true : false;
        });
        // Clear the empty custom element.
        $configdata = $this->clear_empty_data($configdata);
        $method = \backup::TARGET_CURRENT_ADDING;
        // Restore controller.
        $this->controller = new \restore_controller($backuptempdir, $this->courseid, \backup::INTERACTIVE_NO,
        \backup::MODE_IMPORT, $USER->id, $method);

        if ($configdata['importmethod'] == 'save') {
            $this->controller->execute_precheck();
            $this->controller->execute_plan();
            foreach ($this->controller->get_plan()->get_tasks() as $key => $task) {
                if ($task instanceof \restore_activity_task) {
                    $pulseid = $task->get_activityid();
                    $contextid = $task->get_contextid();
                    $configdata['contextid'] = $contextid;
                    if (isset($configdata['introeditor']) && !empty($configdata['introeditor']['text'])) {
                        $introeditor = $configdata['introeditor'];
                        $configdata['introformat'] = $introeditor['format'];
                        $configdata['intro'] = file_save_draft_area_files(
                            $introeditor['itemid'], $contextid, 'mod_pulse', 'intro', 0,
                            array('subdirs' => true), $introeditor['text']
                        );
                    }
                    if (isset($configdata['pulse_contenteditor']) && !empty($configdata['pulse_contenteditor']['text'])) {
                        $pulseeditor = $configdata['pulse_contenteditor'];
                        $configdata['pulse_contentformat'] = $pulseeditor['format'];
                        $configdata['pulse_content'] = file_save_draft_area_files(
                            $pulseeditor['itemid'], $contextid, 'mod_pulse', 'pulse_content', 0,
                            array('subdirs' => true), $pulseeditor['text']
                        );
                    }
                    unset($configdata['pulse_contenteditor']);
                    unset($configdata['introeditor']);
                    // Update the pro reminder contents.
                    pulse_preset_update($pulseid, $configdata);
                    if (!empty($configdata) && self::hasupdatefields($configdata)) {
                        $configdata['id'] = $pulseid;
                        $DB->update_record('pulse', (object) $configdata);
                    }
                    // Update course modules.
                    if (!empty($configdata)) {
                        $configdata['id'] = $task->get_moduleid();
                        $configdata['instance'] = $pulseid;
                        $DB->update_record('course_modules', $configdata);
                    }
                    // Remove the Database cache.
                    purge_other_caches();
                    $DB->reset_caches();
                    break;
                }
            }
            $this->controller->destroy();
            $courseurl = new \moodle_url('/course/view.php', ['id' => $this->courseid ]);
            return json_encode(['url' => $courseurl->out(), 'pulseid' => (isset($pulseid) ? $pulseid : '') ]);

        } else if ($configdata['importmethod'] == 'customize') {
            // Fetch values from backup file.
            $backupdata = $this->fetch_pulse_data_fromxml();

            $this->controller->destroy();
            if (isset($backupdata['intro'])) {
                $backupdata['introeditor'] = array(
                    'text' => $backupdata['intro'],
                    'format' => $backupdata['introformat']
                );
            }
            if (isset($backupdata['pulse_content'])) {
                $backupdata['pulse_content_editor'] = array(
                    'text' => $backupdata['pulse_content'],
                    'format' => $backupdata['pulse_contentformat']
                );
            }

            // Update the config params with backup data.
            $formdata = array_replace_recursive($backupdata, $configdata);

            // Replace the config data.
            $formdata['course'] = $this->courseid;
            $formdata = array_filter($formdata, function($value) {
                if (!is_array($value)) {
                    return (trim($value) != '') ? true : false;
                }
                return true;
            });

            // Create form with values.
            $form = $this->prepare_modform($formdata);
            // Send to replace the form.
            return $form;
        }
    }

    /**
     * Prepare the pulse module form html to replace with current adding module form using fragments.
     * Creates the new pulse form with backup and custom config data to user customization.
     * Triggered only on customize preset method.
     *
     * @param array $data Mod form dataset.
     * @return string Pulse mod form html content.
     */
    public function prepare_modform($data) {
        global $CFG, $PAGE, $COURSE;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot.'/mod/pulse/mod_form.php');
        $this->modformdata = array_replace_recursive($this->modformdata, $data);
        // Replace the config data.
        $this->modformdata = array_map(function($value) {
            return (!is_array($value)) ? trim($value) : $value;
        }, $this->modformdata);

        $COURSE = $this->course;
        $PAGE->set_url(new \moodle_url('/course/modedit.php', [
            'section' => $this->modformdata['section'],
            'add' => $this->modformdata['add'],
            'update' => $this->modformdata['update'],
            'course' => $this->course->id
        ]));

        $pulseform = new \mod_pulse_mod_form((object) $this->modformdata, $this->modformdata['section'], null, $this->course);
        $pulseform->set_data($this->modformdata);
        $this->pulseform = $pulseform; // Test the form elements.
        return $pulseform->render();
    }

    /**
     * Fetch the pulse activity data from the extracted xml file of preset template backup file.
     * Contains three seperate data sets. Course Modules, Pulse, Pulse pro data.
     *
     * Used the custom restore step class to fetch the data. Direct use of xml parsers raise the permission issue.
     *
     * @return array Set of pulse module datas from backup file.
     */
    public function fetch_pulse_data_fromxml(): array {
        $record = [];
        foreach ($this->controller->get_plan()->get_tasks() as $key => $task) {
            if ($task instanceof \restore_activity_task) {
                $te = new \preset_customize_restore('module_info', 'module.xml', $task);
                $te->execute();
                $excluedfields = ['id', 'version', 'modulename', 'sectionid', 'sectionnumber', 'idnumber', 'added'];
                $record += array_filter($te->data, function($key) use ($excluedfields) {
                    return (!in_array($key, $excluedfields));
                }, ARRAY_FILTER_USE_KEY);

                $te = new \preset_customize_restore('module_info', 'pulse.xml', $task);
                $te->execute();
                $excluedfields = ['id', 'course', 'pulseid'];
                $record += array_filter($te->data, function($key) use ($excluedfields) {
                    return (!in_array($key, $excluedfields));
                }, ARRAY_FILTER_USE_KEY);

                $te = new \preset_customize_restore('pulsepro', 'pulse.xml', $task);
                $te->execute();
                $excluedfields = ['id', 'course', 'pulseid'];
                $record += array_filter($te->data, function($key) use ($excluedfields) {
                    return (!in_array($key, $excluedfields));
                }, ARRAY_FILTER_USE_KEY);

                pulse_extend_preset('cleandata', $record);
            }
        }

        return $record;
    }
}
