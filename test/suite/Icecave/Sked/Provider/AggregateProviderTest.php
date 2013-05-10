<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\Schedule\Schedule;
use Icecave\Skew\Entities\TaskDetails;
use Phake;
use PHPUnit_Framework_TestCase;

class AggregateProviderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->now = new DateTime(2012, 1, 1);

        $this->schedule1 = new Schedule('schedule-1');
        $this->schedule2 = new Schedule('schedule-2');

        $this->taskDetails = new TaskDetails('skew.test');

        $this->provider1 = Phake::mock(__NAMESPACE__ . '\ProviderInterface');
        $this->provider2 = Phake::mock(__NAMESPACE__ . '\ProviderInterface');

        $this->provider = new AggregateProvider;

        $this->provider->add($this->provider1);
        $this->provider->add($this->provider2);
    }

    public function testAdd()
    {
        $result = $this->provider->providers();

        $this->assertInstanceOf('Icecave\Collections\Set', $result);
        $this->assertSame(2, $result->size());
        $this->assertTrue($result->contains($this->provider1));
        $this->assertTrue($result->contains($this->provider2));
    }

    public function testRemove()
    {
        $this->provider->remove($this->provider1);
        $this->assertFalse($this->provider->providers()->contains($this->provider1));
    }

    public function testAcquire()
    {
        $upperBound = new DateTime(2012, 1, 1);

        $result = $this->provider->acquire($this->now, $upperBound);

        Phake::verify($this->provider1)->acquire($this->now, $upperBound);
        Phake::verify($this->provider2)->acquire($this->now, $upperBound);

        $this->assertNull($result);
    }

    public function testAcquireOneEvent()
    {
        $upperBound = new DateTime(2012, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event);

        $result = $this->provider->acquire($this->now, $upperBound);

        Phake::verify($this->provider1)->acquire($this->now, $upperBound);
        Phake::verify($this->provider2)->acquire($this->now, $upperBound);

        $this->assertSame($event, $result);
    }

    public function testAcquireTwoEvents()
    {
        $upperBound = new DateTime(2013, 1, 1);

        $event1 = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));
        $event2 = new Event($this->schedule2, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event1);

        Phake::when($this->provider2)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event2);

        $result = $this->provider->acquire($this->now, $upperBound);

        Phake::inOrder(
            Phake::verify($this->provider1)->acquire($this->now, $upperBound),
            Phake::verify($this->provider2)->acquire($this->now, $event1->dateTime()),
            Phake::verify($this->provider1)->rollback($this->now, $event1)
        );

        $this->assertSame($event2, $result);
    }

    public function testRollback()
    {
        $upperBound = new DateTime(2013, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event);

        $result = $this->provider->acquire($this->now, $upperBound);
        $this->provider->rollback($this->now, $result);

        Phake::verify($this->provider1)->rollback($this->now, $event);
        Phake::verify($this->provider2, Phake::never())->rollback(Phake::anyParameters());
    }

    public function testRollbackFailure()
    {
        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->rollback($this->now, $event);
    }

    public function testRollbackFailureAfterSuccess()
    {
        $upperBound = new DateTime(2013, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event);

        $result = $this->provider->acquire($this->now, $upperBound);
        $this->provider->rollback($this->now, $result);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->rollback($this->now, $result);
    }

    public function testCommit()
    {
        $upperBound = new DateTime(2013, 1, 1);
        $lowerBound = new DateTime(2011, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event);

        $result = $this->provider->acquire($this->now, $upperBound);
        $this->provider->commit($this->now, $result, $lowerBound);

        Phake::verify($this->provider1)->commit($this->now, $event, $lowerBound);
        Phake::verify($this->provider2, Phake::never())->commit(Phake::anyParameters());
    }

    public function testCommitFailure()
    {
        $lowerBound = new DateTime(2011, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->commit($this->now, $event, $lowerBound);
    }

    public function testCommitFailureAfterSuccess()
    {
        $upperBound = new DateTime(2013, 1, 1);
        $lowerBound = new DateTime(2011, 1, 1);

        $event = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));

        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($event);

        $result = $this->provider->acquire($this->now, $upperBound);
        $this->provider->commit($this->now, $result, $lowerBound);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->commit($this->now, $result, $lowerBound);
    }

    public function testReload()
    {
        Phake::when($this->provider1)
            ->reload(Phake::anyParameters())
            ->thenReturn(1);

        Phake::when($this->provider2)
            ->reload(Phake::anyParameters())
            ->thenReturn(2);

        $count = $this->provider->reload($this->now);

        Phake::verify($this->provider1)->reload($this->now);
        Phake::verify($this->provider2)->reload($this->now);

        $this->assertSame(3, $count);
    }
}
