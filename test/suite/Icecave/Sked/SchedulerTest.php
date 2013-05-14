<?php
namespace Icecave\Sked;

use Eloquent\Liberator\Liberator;
use Icecave\Chrono\DateTime;
use Phake;
use PHPUnit_Framework_TestCase;
use RuntimeException;

class SchedulerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->provider = Phake::mock('Icecave\Sked\Provider\ProviderInterface');
        $this->dispatcher = Phake::mock('Icecave\Sked\Dispatcher\DispatcherInterface');
        $this->logger = Phake::mock('Psr\Log\LoggerInterface');
        $this->clock = Phake::mock('Icecave\Chrono\Clock\ClockInterface');
        $this->isolator = Phake::mock('Icecave\Isolator\Isolator');
        $this->now = new DateTime(2012, 01, 01, 0, 0, 0);

        Phake::when($this->clock)
            ->localDateTime()
            ->thenReturn($this->now);

        $this->scheduler = Phake::partialMock(
            __NAMESPACE__ . '\Scheduler',
            $this->provider,
            $this->dispatcher,
            $this->logger,
            $this->clock,
            $this->isolator
        );

        $this->liberatedScheduler = Liberator::liberate($this->scheduler);
    }

    public function testSetReloadInterval()
    {
        $this->assertSame(600, $this->scheduler->reloadInterval());
        $this->scheduler->setReloadInterval(300);
        $this->assertSame(300, $this->scheduler->reloadInterval());
    }

    public function testSetDelayWarningThreshold()
    {
        $this->assertSame(30, $this->scheduler->delayWarningThreshold());
        $this->scheduler->setDelayWarningThreshold(60);
        $this->assertSame(60, $this->scheduler->delayWarningThreshold());
    }

    public function testRun()
    {
        Phake::when($this->scheduler)
            ->mainLoop()
            ->thenReturn(null);

        $result = $this->scheduler->run();

        Phake::inOrder(
            Phake::verify($this->scheduler)->setUp(),
            Phake::verify($this->scheduler)->mainLoop(),
            Phake::verify($this->scheduler)->tearDown(),
            Phake::verify($this->logger)->notice('Exited cleanly.')
        );

        $this->assertTrue($result);
    }

    public function testRunWithSetupFailure()
    {
        $exception = new RuntimeException('Flagrant system error!');

        Phake::when($this->scheduler)
            ->setUp()
            ->thenThrow($exception);

        $result = $this->scheduler->run();

        Phake::inOrder(
            Phake::verify($this->scheduler)->setUp(),
            Phake::verify($this->logger)->critical('Terminating due to unexpected exception: Flagrant system error!', ['exception' => $exception])
        );

        Phake::verify($this->scheduler, Phake::never())->mainLoop();
        Phake::verify($this->scheduler, Phake::never())->tearDown();

        $this->assertFalse($result);
    }

    public function testRunWithMainLoopFailure()
    {
        $exception = new RuntimeException('Flagrant system error!');

        Phake::when($this->scheduler)
            ->mainLoop()
            ->thenThrow($exception);

        $result = $this->scheduler->run();

        Phake::inOrder(
            Phake::verify($this->scheduler)->setUp(),
            Phake::verify($this->scheduler)->mainLoop(),
            Phake::verify($this->scheduler)->tearDown(),
            Phake::verify($this->logger)->critical('Terminating due to unexpected exception: Flagrant system error!', ['exception' => $exception])
        );

        $this->assertFalse($result);
    }

    public function testRunWithTearDownFailure()
    {
        $exception = new RuntimeException('Flagrant system error!');

        Phake::when($this->scheduler)
            ->mainLoop()
            ->thenReturn(null);

        Phake::when($this->scheduler)
            ->tearDown()
            ->thenThrow($exception);

        $result = $this->scheduler->run();

        Phake::inOrder(
            Phake::verify($this->scheduler)->setUp(),
            Phake::verify($this->scheduler)->mainLoop(),
            Phake::verify($this->scheduler)->tearDown(),
            Phake::verify($this->logger)->critical('Terminating due to unexpected exception: Flagrant system error!', ['exception' => $exception])
        );

        $this->assertFalse($result);
    }

    public function testSetUp()
    {
        $this->liberatedScheduler->setUp();

        $this->assertTrue($this->liberatedScheduler->isRunning);
        $this->assertTrue($this->liberatedScheduler->doReload);
        $this->assertNull($this->liberatedScheduler->nextEvent);

        Phake::verify($this->isolator)->pcntl_signal(SIGTERM, $this->isInstanceOf('Closure'));
        Phake::verify($this->isolator)->pcntl_signal(SIGINT, $this->isInstanceOf('Closure'));
        Phake::verify($this->isolator)->pcntl_signal(SIGHUP, $this->isInstanceOf('Closure'));
    }

    public function testSigTermHandler()
    {
        $this->liberatedScheduler->setUp();

        $handler = null;
        Phake::verify($this->isolator)->pcntl_signal(SIGTERM, Phake::capture($handler));
        $this->assertInstanceOf('Closure', $handler);

        $this->liberatedScheduler->isRunning = true;

        $handler(SIGTERM);

        $this->assertFalse($this->liberatedScheduler->isRunning);
    }

    public function testSigIntHandler()
    {
        $this->liberatedScheduler->setUp();

        $handler = null;
        Phake::verify($this->isolator)->pcntl_signal(SIGINT, Phake::capture($handler));
        $this->assertInstanceOf('Closure', $handler);

        $this->liberatedScheduler->isRunning = true;

        $handler(SIGINT);

        $this->assertFalse($this->liberatedScheduler->isRunning);
    }

    public function testSigHupHandler()
    {
        $this->liberatedScheduler->setUp();

        $handler = null;
        Phake::verify($this->isolator)->pcntl_signal(SIGHUP, Phake::capture($handler));
        $this->assertInstanceOf('Closure', $handler);

        $this->liberatedScheduler->doReload = false;

        $handler(SIGHUP);

        $this->assertTrue($this->liberatedScheduler->doReload);
    }

    public function testTearDown()
    {
        $this->liberatedScheduler->tearDown();

        $this->assertFalse($this->liberatedScheduler->isRunning);
        $this->assertFalse($this->liberatedScheduler->doReload);

        Phake::verify($this->isolator)->pcntl_signal(SIGTERM, SIG_DFL);
        Phake::verify($this->isolator)->pcntl_signal(SIGINT, SIG_DFL);
        Phake::verify($this->isolator)->pcntl_signal(SIGHUP, SIG_DFL);

        Phake::verify($this->scheduler)->rollbackEvent();
    }

    public function testMainLoop()
    {

    }

    public function testReload()
    {
        Phake::when($this->provider)
            ->reload(Phake::anyParameters())
            ->thenReturn(5);

        $this->liberatedScheduler->reload();

        Phake::inOrder(
            Phake::verify($this->provider)->reload($this->now),
            Phake::verify($this->logger)->notice('Loaded 5 schedule(s), next reload in 600s.')
        );

        $this->assertSame($this->now, $this->liberatedScheduler->lastReload);
    }

    public function testReloadFailure()
    {
        $exception = new RuntimeException('Flagrant system error!');

        Phake::when($this->provider)
            ->reload(Phake::anyParameters())
            ->thenThrow($exception);

        $this->liberatedScheduler->reload();

        Phake::inOrder(
            Phake::verify($this->provider)->reload($this->now),
            Phake::verify($this->logger)->critical('Unable to load schedules: Flagrant system error!', ['exception' => $exception])
        );

        $this->assertSame($this->now, $this->liberatedScheduler->lastReload);
    }

    public function testNextReloadDateTime()
    {
        $result = $this->liberatedScheduler->nextReloadDateTime();

        $this->assertInstanceOf('Icecave\Chrono\DateTime', $result);
        $this->assertSame('1970-01-01T00:10:00+00:00', $result->isoString());

        $this->liberatedScheduler->lastReload = $this->now;

        $result = $this->liberatedScheduler->nextReloadDateTime();

        $this->assertInstanceOf('Icecave\Chrono\DateTime', $result);
        $this->assertSame('2012-01-01T00:10:00+00:00', $result->isoString());
    }

    public function testAcquireEvent()
    {

    }

    public function testDispatchEvent()
    {

    }

    public function testRollbackEvent()
    {

    }

    public function testCommitEvent()
    {

    }

    public function testWaitUntil()
    {
        $future = $this->now->add(15);

        Phake::when($this->clock)
            ->localDateTime()
            ->thenReturn($this->now)
            ->thenReturn($future);

        Phake::when($this->isolator)
            ->sleep(Phake::anyParameters())
            ->thenReturn(0);

        $dateTime = $this->now->add(10);

        $result = $this->liberatedScheduler->waitUntil($dateTime);

        $localDateTimeVerifier = Phake::verify($this->clock, Phake::times(2))->localDateTime();

        Phake::inOrder(
            $localDateTimeVerifier,
            Phake::verify($this->logger)->debug('Sleeping 10s until 2012-01-01T00:00:10+00:00'),
            Phake::verify($this->isolator)->sleep(10),
            $localDateTimeVerifier
        );

        $this->assertTrue($result);
    }

    public function testWaitUntilSafeGuard()
    {
        $future = $this->now->add(9);

        Phake::when($this->clock)
            ->localDateTime()
            ->thenReturn($this->now)
            ->thenReturn($future);

        Phake::when($this->isolator)
            ->sleep(Phake::anyParameters())
            ->thenReturn(0);

        $dateTime = $this->now->add(10);

        $result = $this->liberatedScheduler->waitUntil($dateTime);

        $localDateTimeVerifier = Phake::verify($this->clock, Phake::times(2))->localDateTime();

        Phake::inOrder(
            $localDateTimeVerifier,
            Phake::verify($this->logger)->debug('Sleeping 10s until 2012-01-01T00:00:10+00:00'),
            Phake::verify($this->isolator)->sleep(10),
            $localDateTimeVerifier
        );

        $this->assertFalse($result);
    }

    public function testWaitUntilSignalled()
    {
        Phake::when($this->clock)
            ->localDateTime()
            ->thenReturn($this->now);

        Phake::when($this->isolator)
            ->sleep(Phake::anyParameters())
            ->thenReturn(100); // non-zero = signalled

        $dateTime = $this->now->add(10);

        $result = $this->liberatedScheduler->waitUntil($dateTime);

        Phake::inOrder(
            Phake::verify($this->clock)->localDateTime(),
            Phake::verify($this->logger)->debug('Sleeping 10s until 2012-01-01T00:00:10+00:00'),
            Phake::verify($this->isolator)->sleep(10),
            Phake::verify($this->isolator)->pcntl_signal_dispatch()
        );

        $this->assertFalse($result);
    }

    public function testWaitUntilDateTimeInPast()
    {
        Phake::when($this->clock)
            ->localDateTime()
            ->thenReturn($this->now);

        Phake::when($this->isolator)
            ->sleep(Phake::anyParameters())
            ->thenReturn(100); // non-zero = signalled

        $dateTime = $this->now->subtract(10);

        $result = $this->liberatedScheduler->waitUntil($dateTime);

        Phake::verify($this->clock)->localDateTime();
        Phake::verifyNoInteraction($this->isolator);
        Phake::verifyNoInteraction($this->logger);

        $this->assertTrue($result);
    }
}
