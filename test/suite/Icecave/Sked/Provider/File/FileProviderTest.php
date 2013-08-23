<?php
namespace Icecave\Sked\Provider\File;

use Eloquent\Liberator\Liberator;
use Icecave\Agenda\Parser\CronParser;
use Icecave\Agenda\Schedule\DailySchedule;
use Icecave\Chrono\DateTime;
use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Isolator\Isolator;
use Icecave\Skew\Entities\TaskDetails;
use Phake;
use PHPUnit_Framework_TestCase;

class FileProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new DateTime(2012, 1, 2);
        $this->upperBound = new DateTime(2012, 1, 3);
        $this->lowerBound = new DateTime(2012, 1, 1);

        $this->fileSchedule = new FileSchedule(
            'foo-name',
            new TaskDetails('foo.some-task'),
            new DailySchedule,
            true
        );

        $this->directories = array(__DIR__ . '/../../../../../fixture/schedules/subdir');
        $this->fileReader = new FileReader;
        $this->scheduleLowerBoundsFilename = null;
        $this->isolator = Phake::partialMock('Icecave\Isolator\Isolator');

        $this->provider = Phake::partialMock(
            'Icecave\Sked\Provider\File\FileProvider',
            $this->directories,
            $this->fileReader,
            $this->scheduleLowerBoundsFilename,
            $this->isolator
        );

        $this->liberatedProvider = Liberator::liberate($this->provider);
    }

    public function testConstruct()
    {
        $this->assertSame($this->directories, $this->liberatedProvider->directories);
        $this->assertSame($this->fileReader, $this->liberatedProvider->fileReader);
        $this->assertSame($this->scheduleLowerBoundsFilename, $this->liberatedProvider->scheduleLowerBoundsFilename);
        $this->assertSame($this->isolator, $this->liberatedProvider->isolator);

        $this->assertTrue($this->liberatedProvider->scheduleLowerBounds->isEmpty());
        $this->assertTrue($this->liberatedProvider->acquiredSchedules->isEmpty());
        $this->assertTrue($this->liberatedProvider->unacquiredSchedules->isEmpty());
    }

    public function testConstructorDefaults()
    {
        $provider = new FileProvider($this->directories);
        $liberatedProvider = Liberator::liberate($provider);

        $this->assertSame($this->directories, $liberatedProvider->directories);
        $this->assertInstanceOf('Icecave\Sked\Provider\File\FileReader', $liberatedProvider->fileReader);
        $this->assertNull($liberatedProvider->scheduleLowerBoundsFilename);
        $this->assertInstanceOf('Icecave\Isolator\Isolator', $liberatedProvider->isolator);

        $this->assertTrue($this->liberatedProvider->scheduleLowerBounds->isEmpty());
        $this->assertTrue($this->liberatedProvider->acquiredSchedules->isEmpty());
        $this->assertTrue($this->liberatedProvider->unacquiredSchedules->isEmpty());
    }

    // public function testAcquire()
    // {
    // }

    // public function testRollback()
    // {
    // }

    // public function testCommit()
    // {
    // }

    // public function testReload()
    // {
    // }

    public function testNextRunDateTime()
    {
        $dateTime = new DateTime(
            $this->now->year(),
            $this->now->month(),
            $this->now->day() + 1
        );

        Phake::when($this->provider)
            ->scheduleLowerBound(Phake::anyParameters())
            ->thenReturn(null);

        $this->assertEquals($dateTime, $this->liberatedProvider->nextRunDateTime($this->now, $this->fileSchedule));

        Phake::verify($this->provider)->scheduleLowerBound($this->fileSchedule);
    }

    public function testNextRunDateTimeWithExistingLowerBound()
    {
        $dateTime = new DateTime(
            $this->now->year(),
            $this->now->month(),
            $this->now->day() + 1
        );

        $this->liberatedProvider->setScheduleLowerBound($this->fileSchedule, $this->lowerBound);

        $this->assertEquals($dateTime, $this->liberatedProvider->nextRunDateTime($this->now, $this->fileSchedule));

        Phake::verify($this->provider)->scheduleLowerBound($this->fileSchedule);
    }

    public function testScheduleLowerBound()
    {
        $this->liberatedProvider->scheduleLowerBounds[$this->fileSchedule->name()] = $this->lowerBound;

        $this->assertSame($this->lowerBound, $this->liberatedProvider->scheduleLowerBound($this->fileSchedule));
    }

    public function testScheduleLowerBoundWithDefaultNull()
    {
        $this->assertNull($this->liberatedProvider->scheduleLowerBound($this->fileSchedule));
    }

    public function testSetScheduleLowerBound()
    {
        $this->assertFalse($this->liberatedProvider->scheduleLowerBounds->hasKey('foo-name'));
        $this->liberatedProvider->setScheduleLowerBound($this->fileSchedule, $this->lowerBound);
        $this->assertTrue($this->liberatedProvider->scheduleLowerBounds->hasKey('foo-name'));
        $this->assertSame($this->lowerBound, $this->liberatedProvider->scheduleLowerBounds->get('foo-name'));

        Phake::verify($this->provider)->saveScheduleLowerBounds();
    }

    public function testLoadScheduleLowerBounds()
    {
        $scheduleLowerBoundsFilename = 'path-to-file/lower-bounds.dat';

        $fakeData = array();
        $fakeData['foo-name'] = $this->lowerBound->isoString();

        $fakeDataMap = new Map;
        foreach ($fakeData as $name => $isoDateTime) {
            $fakeDataMap[$name] = DateTime::fromIsoString($isoDateTime);
        }

        Phake::when($this->isolator)
            ->is_file($scheduleLowerBoundsFilename)
            ->thenReturn(true);

        Phake::when($this->isolator)
            ->file_get_contents($scheduleLowerBoundsFilename)
            ->thenReturn(serialize($fakeData));

        $provider = new FileProvider(
            $this->directories,
            $this->fileReader,
            $scheduleLowerBoundsFilename,
            $this->isolator
        );

        $liberatedProvider = Liberator::liberate($provider);

        $this->assertEquals($fakeDataMap, $liberatedProvider->scheduleLowerBounds);
        $this->assertFalse($liberatedProvider->scheduleLowerBounds->isEmpty());
    }

    public function testLoadScheduleLowerBoundsWithNullPersistanceFilename()
    {
        $isolator = Phake::partialMock('Icecave\Isolator\Isolator');

        $provider = Phake::partialMock(
            'Icecave\Sked\Provider\File\FileProvider',
            $this->directories,
            $this->fileReader,
            null,
            $isolator
        );

        $liberatedProvider = Liberator::liberate($provider);

        $this->assertNull($liberatedProvider->scheduleLowerBoundsFilename);
        $this->assertTrue($liberatedProvider->scheduleLowerBounds->isEmpty());

        Phake::verifyNoInteraction($isolator);
    }

    public function testLoadScheduleLowerBoundsWithNonFile()
    {
        $scheduleLowerBoundsFilename = 'path-to-file-that-does-not-exist/lower-bounds.dat';

        $provider = new FileProvider(
            $this->directories,
            $this->fileReader,
            $scheduleLowerBoundsFilename,
            $this->isolator
        );

        $liberatedProvider = Liberator::liberate($provider);

        Phake::verify($this->isolator)->is_file($scheduleLowerBoundsFilename);
    }

    public function testSaveScheduleLowerBounds()
    {
        $scheduleLowerBoundsFilename = 'path-to-file/lower-bounds.dat';

        $fakeData = array();
        $fakeData['foo-name'] = $this->lowerBound->isoString();

        $fakeDataMap = new Map;
        foreach ($fakeData as $name => $isoDateTime) {
            $fakeDataMap[$name] = DateTime::fromIsoString($isoDateTime);
        }

        $fakeDataSerialized = serialize($fakeData);

        Phake::when($this->isolator)
            ->serialize(Phake::anyParameters())
            ->thenReturn($fakeDataSerialized);

        Phake::when($this->isolator)
            ->file_put_contents(Phake::anyParameters())
            ->thenReturn(strlen($fakeDataSerialized));

        $provider = new FileProvider(
            $this->directories,
            $this->fileReader,
            $scheduleLowerBoundsFilename,
            $this->isolator
        );

        $liberatedProvider = Liberator::liberate($provider);

        $liberatedProvider->scheduleLowerBounds = $fakeDataMap;

        $this->assertNull($liberatedProvider->saveScheduleLowerBounds());

        Phake::verify($this->isolator)->serialize($fakeData);
        Phake::verify($this->isolator)->file_put_contents($scheduleLowerBoundsFilename, $fakeDataSerialized);
    }

    public function testSaveScheduleLowerBoundsWithNullPersistanceFilename()
    {
        $isolator = Phake::partialMock('Icecave\Isolator\Isolator');

        $provider = new FileProvider(
            $this->directories,
            $this->fileReader,
            null,
            $isolator
        );

        $liberatedProvider = Liberator::liberate($provider);

        $this->assertNull($liberatedProvider->scheduleLowerBoundsFilename);
        $this->assertNull($liberatedProvider->saveScheduleLowerBounds());

        Phake::verifyNoInteraction($isolator);
    }
}
