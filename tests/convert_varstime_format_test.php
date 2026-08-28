<?php
// PHPUnit test for convert_varstime_format

namespace mod_pulse;

final class convert_varstime_format_test extends \advanced_testcase {
    public function test_convert_from_timestamp() {
        $this->resetAfterTest();

        $ts = strtotime('2026-08-13 14:30:00');
        $obj = (object)['startdate' => $ts];

        $method = new \ReflectionMethod('pulse_email_vars', 'convert_varstime_format');
        $method->setAccessible(true);
        $method->invokeArgs(null, [&$obj]);

        $expecteddate = userdate($ts, get_string('strftimedate', 'core_langconfig'));
        $expectedwithtime = userdate($ts, get_string('strftimedatetime', 'core_langconfig'));

        $this->assertEquals($expecteddate, $obj->startdate);
        $this->assertEquals($expectedwithtime, $obj->startdate_withtime);
    }

    public function test_convert_from_string_date() {
        $this->resetAfterTest();

        $datestr = '2026-08-13 14:30:00';
        $obj = (object)['startdate' => $datestr];

        $method = new \ReflectionMethod('pulse_email_vars', 'convert_varstime_format');
        $method->setAccessible(true);
        $method->invokeArgs(null, [&$obj]);

        $ts = strtotime($datestr);
        $expecteddate = userdate($ts, get_string('strftimedate', 'core_langconfig'));
        $expectedwithtime = userdate($ts, get_string('strftimedatetime', 'core_langconfig'));

        $this->assertEquals($expecteddate, $obj->startdate);
        $this->assertEquals($expectedwithtime, $obj->startdate_withtime);
    }
}
