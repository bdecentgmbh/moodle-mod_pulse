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
 * Pulse notification entities for report builder
 *
 * @package   pulseaction_notification
 * @copyright 2023, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace pulseaction_notification\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\{date, number, select, text};
use core_reportbuilder\local\helpers\format;
use html_writer;
use pulseaction_notification\notification as pulsenotification;
use lang_string;

/**
 * Pulse notification entity base for report source.
 */
class notification extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {

        return [
            'user' => 'plnu',
            'context' => 'plnctx',
            'course' => 'plnc',
            'pulseaction_notification_sch' => 'plnsch',
            'pulse_autoinstances' => 'plni',
            'pulse_autotemplates' => 'plnt',
            'pulse_autotemplates_ins' => 'plnti',
            'pulseaction_notification_ins' => 'plani',
            'pulseaction_notification' => 'plan',
            'cohort_members' => 'chtm',
            'cohort' => 'cht'
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('notificationreport', 'pulseaction_notification');
    }

    public function initialise(): base {

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        list($filters, $conditions) = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        foreach ($conditions as $condition) {
            $this->add_condition($condition);
        }

        return $this;
    }

    /**
     * List of columns available for this notfication datasource.
     *
     * @return array
     */
    protected function get_all_columns(): array {

        $notificationschalias = $this->get_table_alias('pulseaction_notification_sch');
        $templatesalias = $this->get_table_alias('pulse_autotemplates');
        $templatesinsalias = $this->get_table_alias('pulse_autotemplates_ins');

        $notificationalias = $this->get_table_alias('pulseaction_notification');
        $notificationinsalias = $this->get_table_alias('pulseaction_notification_ins');

        // Time the schedule is created.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'pulseaction_notification'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$notificationschalias}.timecreated")
        ->add_callback(static function ($value, $row): string {
            return userdate($value);
        });

        // Schedule time to send notification.
        $columns[] = (new column(
            'scheduletime',
            new lang_string('scheduledtime', 'pulseaction_notification'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$notificationschalias}.scheduletime")
        ->add_callback(static function ($value, $row): string {
            return userdate($value);
        });

        // Message type field.
        $columns[] = (new column(
            'messagetype',
            new lang_string('messagetype', 'pulseaction_notification'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("IF ({$templatesalias}.title <> '', {$templatesalias}.title, {$templatesinsalias}.title)", 'title')
        ->add_callback(fn($val, $row) => format_string($val));

        // Subject field.
        $columns[] = (new column(
            'subject',
            new lang_string('subject', 'pulseaction_notification'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("IF ({$notificationinsalias}.subject <> '',
            {$notificationinsalias}.subject, {$notificationalias}.subject)", "subject")
        ->add_field("{$templatesinsalias}.instanceid")
        ->add_field("{$notificationschalias}.userid")
        ->add_callback(static function($value, $row): string {

            return $value . html_writer::link('javascript:void(0);', '<i class="fa fa-info"></i>', [
                'class' => 'pulse-automation-info-block',
                'data-target' => 'view-content',
                'data-instanceid' => $row->instanceid,
                'data-userid' => $row->userid
            ]);
        });

        // Status of the schedule.
        $columns[] = (new column(
            'status',
            new lang_string('status', 'pulseaction_notification'),
            $this->get_entity_name()
        ))
        ->set_is_sortable(true)
        ->add_field("{$notificationschalias}.status")
        ->add_callback([pulsenotification::class, 'get_schedule_status']);

        return $columns;
    }

    /**
     * Defined filters for the notification entities.
     *
     * @return array
     */
    protected function get_all_filters(): array {
        global $DB;

        $notificationschalias = $this->get_table_alias('pulseaction_notification_sch');
        $templatesalias = $this->get_table_alias('pulse_autotemplates');
        $templatesinsalias = $this->get_table_alias('pulse_autotemplates_ins');

        $instancealias = $this->get_table_alias('pulse_autoinstances');
        $notificationinsalias = $this->get_table_alias('pulseaction_notification_ins');

        $cohortmembersalias = $this->get_table_alias('cohort_members');
        $cohortalias = $this->get_table_alias('cohort');

        $useralias = $this->get_table_alias('user');

        // Automation instance id.
        $conditions[] = (new filter(
            number::class,
            'instanceid',
            new lang_string('instanceid', 'pulseaction_notification'),
            $this->get_entity_name(),
            "{$instancealias}.id"
        ));

        // Automation instance.
        $filters[] = (new filter(
            text::class,
            'automationinstance',
            new lang_string('messagetype', 'pulseaction_notification'),
            $this->get_entity_name(),
            "IF ({$templatesinsalias}.title <> '', {$templatesinsalias}.title, {$templatesalias}.title)"
        ));

        // Automation template.
        $filters[] = (new filter(
            text::class,
            'automationtemplate',
            new lang_string('messagetype', 'pulseaction_notification'),
            $this->get_entity_name(),
            "{$templatesalias}.title"
        ));

        // Status of the schedule.
        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('status', 'pulseaction_notification'),
            $this->get_entity_name(),
            "{$notificationschalias}.status",
        ))->set_options([
            1 => get_string('onhold', 'pulseaction_notification'),
            2 => get_string('queued', 'pulseaction_notification'),
            3 => get_string('sent', 'pulseaction_notification'),
            0 => get_string('failed', 'pulseaction_notification')
        ]);

        // Scheduled time date filter.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('schedulecreatedtime', 'pulseaction_notification'),
            $this->get_entity_name(),
            "{$notificationschalias}.timecreated"
        ));

        // Filter by the schedule nextrun time.
        $filters[] = (new filter(
            date::class,
            'scheduletime',
            new lang_string('scheduledtime', 'pulseaction_notification'),
            $this->get_entity_name(),
            "{$notificationschalias}.scheduletime"
        ));

        // Cohort based filters.
        $options = $DB->get_records_menu('cohort', [], '', 'id, name');
        $filters[] = (new filter(
            select::class,
            'cohort',
            new lang_string('cohort', 'cohort'),
            $this->get_entity_name(),
            "{$cohortalias}.id"
        ))->add_join("
            JOIN {user} {$useralias} ON {$useralias}.id = {$notificationschalias}.userid
            JOIN {cohort_members} {$cohortmembersalias} on {$cohortmembersalias}.userid = {$useralias}.id
            JOIN {cohort} {$cohortalias} on {$cohortalias}.id = {$cohortmembersalias}.cohortid
        ")->set_options($options);

        // Conditions.
        $options = $DB->get_records_menu('cohort', [], '', 'id, name');
        $conditions[] = (new filter(
            select::class,
            'cohort',
            new lang_string('cohort', 'cohort'),
            $this->get_entity_name(),
            "{$cohortalias}.id"
        ))->add_join("
            JOIN {user} {$useralias} ON {$useralias}.id = {$notificationschalias}.userid
            JOIN {cohort_members} {$cohortmembersalias} on {$cohortmembersalias}.userid = {$useralias}.id
            JOIN {cohort} {$cohortalias} on {$cohortalias}.id = {$cohortmembersalias}.cohortid
        ")->set_options($options);

        return [$filters, $conditions];
    }

    /**
     * Schedule join sql.
     *
     * @return string
     */
    public function schedulejoin() {

        $notificationschalias = $this->get_table_alias('pulseaction_notification_sch');

        $autoinstancesalias = $this->get_table_alias('pulse_autoinstances');
        $autotemplatesalias = $this->get_table_alias('pulse_autotemplates');
        $autotemplatesinsalias = $this->get_table_alias('pulse_autotemplates_ins');
        $notificationinsalias = $this->get_table_alias('pulseaction_notification_ins');
        $notificationalias = $this->get_table_alias('pulseaction_notification');

        return "
            JOIN {pulse_autoinstances} AS {$autoinstancesalias} ON {$autoinstancesalias}.id = {$notificationschalias}.instanceid
            JOIN {pulse_autotemplates} AS {$autotemplatesalias} ON {$autotemplatesalias}.id = {$autoinstancesalias}.templateid
            JOIN {pulse_autotemplates_ins} AS {$autotemplatesinsalias}
                ON {$autotemplatesinsalias}.instanceid = {$autoinstancesalias}.id
            JOIN {pulseaction_notification_ins} AS {$notificationinsalias}
                ON {$notificationinsalias}.instanceid = {$notificationschalias}.instanceid
            JOIN {pulseaction_notification} AS  {$notificationalias}
                ON {$notificationalias}.templateid = {$autoinstancesalias}.templateid";
    }
}
