<?php
namespace Icecave\Sked\Provider\File;

use Cron\CronExpression;
use Eloquent\Liberator\Liberator;
use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Constraint\Reader\SchemaReaderInterface;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\SwitchingScopeResolvingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Validation\DefaultingConstraintValidator;
use Icecave\Collections\Map;
use Icecave\Isolator\Isolator;
use Icecave\Skew\Entities\TaskDetails;
use Phake;
use PHPUnit_Framework_TestCase;

class FileReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->isolator = Phake::partialMock('Icecave\Isolator\Isolator');

        $this->reader = new SwitchingScopeResolvingReader;

        $this->schemaReader = new SchemaReader;

        $this->constraintValidator = new BoundConstraintValidator(
            new DefaultingConstraintValidator,
            $this->schemaReader->readPath(__DIR__ . '/../../../../../../res/schedule-config.schema.json')
        );

        $this->fileReader = Phake::partialMock(
            __NAMESPACE__ . '\FileReader',
            $this->reader,
            $this->constraintValidator,
            $this->schemaReader,
            $this->isolator
        );

        $this->liberatedFileReader = Liberator::liberate($this->fileReader);
    }

    public function testConstruct()
    {
        $this->assertSame($this->reader, $this->liberatedFileReader->reader);
        $this->assertSame($this->constraintValidator, $this->liberatedFileReader->constraintValidator);
        $this->assertSame($this->schemaReader, $this->liberatedFileReader->schemaReader);
        $this->assertSame($this->isolator, $this->liberatedFileReader->isolator);
    }

    public function testConstructorDefaults()
    {
        $fileReader = new FileReader;
        $liberatedFileReader = Liberator::liberate($fileReader);

        $this->assertInstanceOf('Eloquent\Schemer\Reader\SwitchingScopeResolvingReader', $liberatedFileReader->reader);
        $this->assertInstanceOf('Eloquent\Schemer\Validation\BoundConstraintValidator', $liberatedFileReader->constraintValidator);
        $this->assertInstanceOf('Eloquent\Schemer\Constraint\Reader\SchemaReader', $liberatedFileReader->schemaReader);
        $this->assertInstanceOf('Icecave\Isolator\Isolator', $liberatedFileReader->isolator);
    }

    public function testReadDirectories()
    {
        $taskDetails1 = new TaskDetails('site.send-email-reports');
// TODO: no longer need to have the "@sked.time" variable for payload?
        $taskDetails1->setPayload('@sked.time');
        $taskDetails1->setTags(array());

        $schedule1 = new FileSchedule(
            'email-reports',
            $taskDetails1,
            CronExpression::factory('@hourly'),
            false            
        );

        $taskDetails2 = new TaskDetails('site.update-leaderboards');
        $taskDetails2->setPayload(null);
        $taskDetails2->setTags(array('foo', 'bar'));

        $schedule2 = new FileSchedule(
            'leaderboards',
            $taskDetails2,
            CronExpression::factory('*/5 * * * *'),
            true
        );

        $taskDetails3 = new TaskDetails('site.do-something');
        $taskDetails3->setPayload(null);
        $taskDetails3->setTags(array());

        $schedule3 = new FileSchedule(
            'sub-dir-test',
            $taskDetails3,
            CronExpression::factory('0 0 1 * *'),
            false
        );

        $schedules = new Map;
        $schedules->add($schedule1->name(), $schedule1);
        $schedules->add($schedule2->name(), $schedule2);
        $schedules->add($schedule3->name(), $schedule3);

// do i need this or not?
        // Phake::when($this->fileReader)
        //     ->readFile(Phake::anyParameters())
        //     ->thenReturn($schedules);       // should it return "new Map;" or "$schedules" ?

        $schedulesDir = __DIR__ . '/../../../../../fixture/schedules';
        $directories = array($schedulesDir);

        $this->assertEquals($schedules, $this->fileReader->readDirectories($directories));

// $actual = $this->fileReader->readDirectories($directories);
// echo PHP_EOL;
// print_r($actual);
// echo PHP_EOL;
// $this->assertEquals($schedules, $actual);

    }



    // public function REFACTOR_testReadDirectories()
    // {
    //     // $schedule1 = new FileSchedule(
    //     //     'email-reports',
    //     //     'site.send-email-reports',
    //     //     CronExpression::factory('@hourly'),
    //     //     '@sked.time',
    //     //     false,
    //     //     array()
    //     // );

    //     // $schedule2 = new FileSchedule(
    //     //     'leaderboards',
    //     //     'site.update-leaderboards',
    //     //     CronExpression::factory('*/5 * * * *'),
    //     //     null,
    //     //     true,
    //     //     array('foo', 'bar')
    //     // );

    //     // $schedule3 = new FileSchedule(
    //     //     'sub-dir-test',
    //     //     'site.do-something',
    //     //     CronExpression::factory('0 0 1 * *'),
    //     //     null,
    //     //     false,
    //     //     array()
    //     // );

    //     // $schedules = new Map;
    //     // $schedules->add($schedule1->id(), $schedule1);
    //     // $schedules->add($schedule2->id(), $schedule2);
    //     // $schedules->add($schedule3->id(), $schedule3);

    //     $schedules = new Map;

    //     Phake::when($this->fileReader)
    //         ->readFile(Phake::anyParameters())
    //         ->thenReturn(new Map);

    //     $schedulesDir = __DIR__ . '/../../../../../fixture/schedules';
    //     $directories = array($schedulesDir);

    //     $this->assertEquals($schedules, $this->fileReader->readDirectories($directories));

    //     Phake::inOrder(
    //         Phake::verify($this->isolator)->scandir($schedulesDir),
    //         Phake::verify($this->isolator)->is_dir($schedulesDir . '/subdir'),
    //         Phake::verify($this->fileReader)->readDirectories(array($schedulesDir . '/subdir')),
    //         Phake::verify($this->fileReader)->readFile(array($schedulesDir . '/subdir/bar.sked.yaml')),
    //         Phake::verify($this->isolator)->scandir($schedulesDir . '/subdir'),
    //         Phake::verify($this->fileReader)->readFile(array($schedulesDir . '/foo.sked.yaml'))
    //     );
    // }







    // public function OLD_AND_WAS_COMMENTED__testReadDirectories()
    // {
    //     $schedule1 = new FileSchedule(
    //         'email-reports',
    //         'site.send-email-reports',
    //         CronExpression::factory('@hourly'),
    //         '@sked.time',
    //         false,
    //         array()
    //     );

    //     $schedule2 = new FileSchedule(
    //         'leaderboards',
    //         'site.update-leaderboards',
    //         CronExpression::factory('*/5 * * * *'),
    //         null,
    //         true,
    //         array('foo', 'bar')
    //     );

    //     $schedule3 = new FileSchedule(
    //         'sub-dir-test',
    //         'site.do-something',
    //         CronExpression::factory('0 0 1 * *'),
    //         null,
    //         false,
    //         array()
    //     );

    //     $schedules = new Map;
    //     $schedules->add($schedule1->id(), $schedule1);
    //     $schedules->add($schedule2->id(), $schedule2);
    //     $schedules->add($schedule3->id(), $schedule3);

    //     $schedulesDir = __DIR__ . '/../../../../../fixture/schedules';
    //     $directories = array($schedulesDir);

    //     $this->assertEquals($schedules, $this->fileReader->readDirectories($directories));

    //     Phake::verify($this->isolator)->scandir($schedulesDir);
    //     Phake::verify($this->isolator)->scandir($schedulesDir . '/subdir');
    // }
}
