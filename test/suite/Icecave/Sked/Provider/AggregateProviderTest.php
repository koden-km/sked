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

        $this->event1 = new Event($this->schedule1, $this->taskDetails, new DateTime(2012, 1, 1));
        $this->event2 = new Event($this->schedule2, $this->taskDetails, new DateTime(2012, 1, 1));

        $this->upperBound = new DateTime(2013, 1, 1);
        $this->lowerBound = new DateTime(2011, 1, 1);

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
        $result = $this->provider->acquire($this->now, $this->upperBound);

        Phake::verify($this->provider1)->acquire($this->now, $this->upperBound);
        Phake::verify($this->provider2)->acquire($this->now, $this->upperBound);

        $this->assertNull($result);
    }

    public function testAcquireOneEvent()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        $result = $this->provider->acquire($this->now, $this->upperBound);

        Phake::verify($this->provider1)->acquire($this->now, $this->upperBound);
        Phake::verify($this->provider2)->acquire($this->now, $this->event1->dateTime());

        $this->assertSame($this->event1, $result);
    }

    public function testAcquireTwoEvents()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        Phake::when($this->provider2)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event2);

        $result = $this->provider->acquire($this->now, $this->upperBound);

        Phake::inOrder(
            Phake::verify($this->provider1)->acquire($this->now, $this->upperBound),
            Phake::verify($this->provider2)->acquire($this->now, $this->event1->dateTime()),
            Phake::verify($this->provider1)->rollback($this->now, $this->event1)
        );

        $this->assertSame($this->event2, $result);
    }

    public function testRollback()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        $result = $this->provider->acquire($this->now, $this->upperBound);
        $this->provider->rollback($this->now, $result);

        Phake::verify($this->provider1)->rollback($this->now, $this->event1);
        Phake::verify($this->provider2, Phake::never())->rollback(Phake::anyParameters());
    }

    public function testRollbackFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->rollback($this->now, $this->event1);
    }

    public function testRollbackFailureAfterSuccess()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        $result = $this->provider->acquire($this->now, $this->upperBound);
        $this->provider->rollback($this->now, $result);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->rollback($this->now, $result);
    }

    public function testCommit()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        $result = $this->provider->acquire($this->now, $this->upperBound);
        $this->provider->commit($this->now, $result, $this->lowerBound);

        Phake::verify($this->provider1)->commit($this->now, $this->event1, $this->lowerBound);
        Phake::verify($this->provider2, Phake::never())->commit(Phake::anyParameters());
    }

    public function testCommitFailure()
    {
        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->commit($this->now, $this->event1, $this->lowerBound);
    }

    public function testCommitFailureAfterSuccess()
    {
        Phake::when($this->provider1)
            ->acquire(Phake::anyParameters())
            ->thenReturn($this->event1);

        $result = $this->provider->acquire($this->now, $this->upperBound);
        $this->provider->commit($this->now, $result, $this->lowerBound);

        $this->setExpectedException(__NAMESPACE__ . '\Exception\NotAcquiredException', 'The schedule "schedule-1" has not previously been acquired.');
        $this->provider->commit($this->now, $result, $this->lowerBound);
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
