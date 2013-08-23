<?php
namespace Icecave\Sked\Provider\File;

use Icecave\Agenda\Schedule\DailySchedule;
use Icecave\Skew\Entities\TaskDetails;
use PHPUnit_Framework_TestCase;

class FileScheduleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->name = 'email-reports';
        $this->taskDetails = new TaskDetails('foo.send-email-reports');
        $this->agendaSchedule = new DailySchedule;
        $this->skippable = true;

        $this->fileSchedule = new FileSchedule(
            $this->name,
            $this->taskDetails,
            $this->agendaSchedule,
            $this->skippable
        );
    }

    public function testConstruct()
    {
        $this->assertSame($this->name, $this->fileSchedule->name());
        $this->assertSame($this->taskDetails, $this->fileSchedule->taskDetails());
        $this->assertSame($this->agendaSchedule, $this->fileSchedule->agendaSchedule());
        $this->assertTrue($this->fileSchedule->isSkippable());
    }
}
