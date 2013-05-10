<?php
namespace Icecave\Sked\Schedule;

use PHPUnit_Framework_TestCase;

class ScheduleTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->schedule = new Schedule('schedule-name');
    }

    public function testConstructor()
    {
        $schedule = new Schedule('name', false);
        $this->assertSame('name', $schedule->name());
        $this->assertFalse($schedule->isSkippable());
    }

    public function testName()
    {
        $this->assertSame('schedule-name', $this->schedule->name());
    }

    public function testSetIsSkippable()
    {
        $this->assertTrue($this->schedule->isSkippable());
        $this->schedule->setIsSkippable(false);
        $this->assertFalse($this->schedule->isSkippable());
    }
}
