<?php

namespace pulseaction_notification\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user;

class notification extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('formtab', 'pulseaction_notification');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        global $PAGE;

        // require_once($CFG->dirroot.'/mod/attendance/locallib.php');
        $notificationentity = new \pulseaction_notification\local\entities\notification();
        $notificationalias = $notificationentity->get_table_alias('pulse_autoinstances');

        $notificationschalias = $notificationentity->get_table_alias('pulseaction_notification_sch');
        $this->set_main_table('pulseaction_notification_sch', $notificationschalias);
        $this->add_entity($notificationentity);

        // Force the join to be added so that course fields can be added first.
        $this->add_join($notificationentity->schedulejoin());

        // Add core user join.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $userjoin = "JOIN {user} {$useralias} ON {$useralias}.id = {$notificationschalias}.userid";
        $this->add_entity($userentity->add_join($userjoin));

        $coursentity = new course();
        $coursealias = $coursentity->get_table_alias('course');
        $coursejoin = "JOIN {course} {$coursealias} ON {$coursealias}.id = {$notificationalias}.courseid";
        $this->add_entity($coursentity->add_join($coursejoin));

        if ($instance = optional_param('instanceid', null, PARAM_INT)) {
            $this->add_base_condition_simple("{$notificationschalias}.instanceid", $instance);
        }

        if (method_exists($this, 'add_all_from_entities')) {
            $this->add_all_from_entities();
        } else {
            $this->add_columns_from_entity($notificationentity->get_entity_name());
            $this->add_filters_from_entity($notificationentity->get_entity_name());
            $this->add_conditions_from_entity($notificationentity->get_entity_name());

            $this->add_columns_from_entity($userentity->get_entity_name());
            $this->add_filters_from_entity($userentity->get_entity_name());
            $this->add_conditions_from_entity($userentity->get_entity_name());

            $this->add_columns_from_entity($coursentity->get_entity_name());
            $this->add_filters_from_entity($coursentity->get_entity_name());
            $this->add_conditions_from_entity($coursentity->get_entity_name());
        }

        $PAGE->requires->js_call_amd('pulseaction_notification/chaptersource', 'reportModal', ['contextid' => \context_system::instance()->id]);
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {

        return [
            'course:fullname',
            'notification:messagetype',
            'notification:subject',
            'user:fullname',
            'notification:timecreated',
            'notification:scheduletime',
            'notification:status'
        ];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'notification:instanceid'
        ];
    }


}
