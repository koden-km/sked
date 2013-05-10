<?php
namespace Icecave\Sked\Schedule;

use Icecave\Chrono\DateTime;
use Phake;
use PHPUnit_Framework_TestCase;

class EventTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->schedule = Phake::mock(__NAMESPACE__ . '\ScheduleInterface');
        $this->taskDetails = Phake::mock('Icecave\Skew\Entities\TaskDetailsInterface');
        $this->dateTime = new DateTime(2012, 1, 1);
        $this->event = new Event($this->schedule, $this->taskDetails, $this->dateTime);
    }

    public function testSchedule()
    {
        $this->assertSame($this->schedule, $this->event->schedule());
    }

    public function testTaskDetails()
    {
        $this->assertSame($this->taskDetails, $this->event->taskDetails());
    }

    public function testDateTime()
    {
        $this->assertSame($this->dateTime, $this->event->dateTime());
    }
}
