<?php
namespace Icecave\Sked\Provider\File;

use Cron\CronExpression;
use Icecave\Skew\Entities\TaskDetails;
use PHPUnit_Framework_TestCase;

class FileScheduleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->name = 'email-reports';
        $this->taskDetails = new TaskDetails('foo.send-email-reports');
        $this->cronExpression = CronExpression::factory('@daily');
        $this->skippable = true;

        $this->fileSchedule = new FileSchedule(
            $this->name,
            $this->taskDetails,
            $this->cronExpression,
            $this->skippable
        );
    }

    public function testConstruct()
    {
        $this->assertSame($this->name, $this->fileSchedule->name());
        $this->assertSame($this->taskDetails, $this->fileSchedule->taskDetails());
        $this->assertSame($this->cronExpression, $this->fileSchedule->cronExpression());
        $this->assertTrue($this->fileSchedule->isSkippable());
    }
}
