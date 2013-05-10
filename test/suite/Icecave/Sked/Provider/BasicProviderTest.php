<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\Duration\Duration;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\Schedule\Schedule;
use Icecave\Skew\Entities\TaskDetails;
use Phake;
use PHPUnit_Framework_TestCase;

class BasicProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new DateTime(2012, 1, 1);
        $this->upperBound = new DateTime(2013, 1, 1);
        $this->lowerBound = new DateTime(2011, 1, 1);

        $this->schedule = new Schedule('test-schedule');
        $this->taskDetails = new TaskDetails('skew.test');
        $this->interval = new Duration(60);
        $this->provider = new BasicProvider($this->schedule, $this->taskDetails, $this->interval);
    }

    public function testAcquire()
    {
        $event = $this->provider->acquire($this->now, $this->upperBound);

        $this->assertInstanceOf('Icecave\Sked\Schedule\Event', $event);

        $this->assertSame($this->schedule, $event->schedule());
        $this->assertSame($this->now, $event->dateTime());
        $this->assertSame($this->taskDetails, $event->taskDetails());
    }

    public function testAcquireAlreadyAcquired()
    {
        $this->provider->acquire($this->now, $this->upperBound);

        $event = $this->provider->acquire($this->now, $this->upperBound);

        $this->assertNull($event);
    }

    public function testAcquireUpperBound()
    {
        $event = $this->provider->acquire($this->now, $this->now);

        $this->assertNull($event);
    }

    public function testRollback()
    {
        $event1 = $this->provider->acquire($this->now, $this->upperBound);

        $this->provider->rollback($this->now, $event1);

        $event2 = $this->provider->acquire($this->now, $this->upperBound);

        $this->assertEquals($event1, $event2);
    }

    public function testRollbackFailure()
    {
        $event = new Event($this->schedule, $this->taskDetails, $this->now);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "test-schedule" has not previously been acquired.');
        $this->provider->rollback($this->now, $event);
    }

    public function testRollbackFailureAfterSuccess()
    {
        $event = $this->provider->acquire($this->now, $this->upperBound);

        $this->provider->rollback($this->now, $event);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "test-schedule" has not previously been acquired.');
        $this->provider->rollback($this->now, $event);
    }

    public function testReload()
    {
        $this->assertSame(1, $this->provider->reload($this->now));
    }
}
