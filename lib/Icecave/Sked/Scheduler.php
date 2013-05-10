<?php
namespace Icecave\Sked;

use Exception;
use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\DateTime;
use Icecave\Chrono\Duration\Duration;
use Icecave\Isolator\Isolator;
use Icecave\Sked\Dispatcher\DispatcherInterface;
use Icecave\Sked\Provider\ProviderInterface;
use Icecave\Sked\Schedule\Event;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Scheduler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param ProviderInterface   $provider              The schedule provider from which schedules are acquired.
     * @param DispatcherInterface $dispatcher            The dispatcher used to dispatch jobs.
     * @param LoggerInterface     $logger                Target for scheduling log information.
     * @param Duration|null       $reloadInterval        The time span to wait between schedule reloads.
     * @param Duration|null       $delayWarningThreshold The maximum time span a job can be delayed before raising a warning.
     * @param ClockInterface|null $clock                 The clock to use, or null to use the system clock.
     * @param Isolator|null       $isolator
     */
    public function __construct(
        ProviderInterface $provider,
        DispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Duration $reloadInterval = null,
        Duration $delayWarningThreshold = null,
        ClockInterface $clock = null,
        Isolator $isolator = null
    ) {
        if (null === $reloadInterval) {
            $reloadInterval = new Duration(600);
        }

        if (null === $delayWarningThreshold) {
            $delayWarningThreshold = new Duration(30);
        }

        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->provider = $provider;
        $this->dispatcher = $dispatcher;
        $this->isRunning = false;
        $this->lastReload = DateTime::fromUnixTime(0);
        $this->doReload = false;
        $this->reloadInterval = $reloadInterval;
        $this->delayWarningThreshold = $delayWarningThreshold;
        $this->clock = $clock;
        $this->isolator = Isolator::get($isolator);

        $this->setLogger($logger);
    }

    public function run()
    {
        if ($this->isRunning) {
            return false;
        }

        try {
            // Prepare for execution ...
            $this->setUp();

            // Execute the main loop, clean-up on failure ...
            try {
                $this->mainLoop();
            } catch (Exception $e) {
                $this->tearDown();
                throw $e;
            }

            // Clean-up on success ...
            $this->tearDown();

            $this->logger->notice('Exited cleanly.');

            return true;

        // Log unexpected exceptions ...
        } catch (Exception $e) {
            $this->logger->critical(
                'Terminating due to unexpected exception: ' . $e->getMessage(),
                ['exception' => $e]
            );

            return false;
        }
    }

    private function setUp()
    {
        $self = $this;

        $handler = function ($signal) use ($self) {
            switch ($signal) {
                case SIGINT:
                case SIGTERM:
                    $self->isRunning = false;
                    break;
                case SIGHUP:
                    $self->doReload = true;
                    break;
            }
        };

        $this->isRunning = true;
        $this->doReload = true;
        $this->nextEvent = null;
        $this->isolator->pcntl_signal(SIGTERM, $handler);
        $this->isolator->pcntl_signal(SIGINT, $handler);
        $this->isolator->pcntl_signal(SIGHUP, $handler);
    }

    private function tearDown()
    {
        $this->isRunning = false;
        $this->doReload = false;
        $this->isolator->pcntl_signal(SIGTERM, SIG_DFL);
        $this->isolator->pcntl_signal(SIGINT, SIG_DFL);
        $this->isolator->pcntl_signal(SIGHUP, SIG_DFL);
        $this->rollbackEvent();
    }

    private function mainLoop()
    {
        while ($this->isRunning) {

            $this->isolator->pcntl_signal_dispatch();

            if ($this->doReload) {
                $this->rollbackEvent();
                $this->reload();
                $this->doReload = false;
            }

            if ($event = $this->acquireEvent()) {

                if ($this->waitUntil($event->dateTime())) {
                    $this->dispatchEvent($event);
                }

            } else {

                if ($this->waitUntil($this->nextReloadDateTime())) {
                    $this->doReload = true;
                }

            }
        }
    }

    private function reload()
    {
        $now = $this->clock->localDateTime();

        try {
            $count = $this->provider->reload($now);
            $this->logger->notice(
                sprintf(
                    'Loaded %d schedule(s), next reload in %ds.',
                    $count,
                    $this->reloadInterval->totalSeconds()
                )
            );
        } catch (Exception $e) {
            $this->logger->critical(
                'Unable to load schedules: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        $this->lastReload = $now;
    }

    private function nextReloadDateTime()
    {
        if (null === $this->lastReload) {
            return $this->clock->localDateTime();
        }

        return $this->lastReload->add($this->reloadInterval);
    }

    private function acquireEvent()
    {
        if (null === $this->nextEvent) {
            $this->nextEvent = $this->provider->acquire(
                $this->clock->localDateTime(),
                $this->nextReloadDateTime()
            );

            if (null !== $this->nextEvent) {
                $this->logger->debug(
                    sprintf(
                        'Acquire event <%s from %s @ %s>',
                        $this->nextEvent->taskDetails()->task(),
                        $this->nextEvent->schedule()->name(),
                        $this->nextEvent->dateTime()
                    )
                );
            }
        }

        return $this->nextEvent;
    }

    private function dispatchEvent()
    {
        $now = $this->clock->localDateTime();

        $jobId = $this->dispatcher->dispatch($now, $this->nextEvent);

        $this->logger->info(
            sprintf(
                'Dispatched "%s" job (id: %s, task: %s)',
                $this->nextEvent->schedule()->name(),
                $jobId,
                $this->nextEvent->taskDetails()->task()
            )
        );

        if ($this->nextEvent->schedule()->isSkippable()) {
            $lowerBounds = $now;
        } else {
            $delay = $now->differenceAsDuration($this->nextEvent->dateTime());

            if ($delay->compare($this->delayWarningThreshold) >= 0) {
                $this->logger->warning(
                    sprintf(
                        'Dispatch of job "%s" scheduled at %s was delayed by %ds.',
                        $jobId,
                        $this->nextEvent->dateTime(),
                        $delay->totalSeconds()
                    )
                );
            }

            $lowerBounds = $this->nextEvent->dateTime();
        }

        $this->commitEvent($lowerBounds);
    }

    private function rollbackEvent()
    {
        if ($this->nextEvent) {

            $this->provider->rollback(
                $this->clock->localDateTime(),
                $this->nextEvent
            );

            $this->logger->debug(
                sprintf(
                    'Rollback event <%s from %s @ %s>',
                    $this->nextEvent->taskDetails()->task(),
                    $this->nextEvent->schedule()->name(),
                    $this->nextEvent->dateTime()
                )
            );

            $this->nextEvent = null;
        }
    }

    private function commitEvent(DateTime $lowerBounds)
    {
        if ($this->nextEvent) {

            $this->provider->commit(
                $this->clock->localDateTime(),
                $this->nextEvent,
                $lowerBounds
            );

            $this->logger->debug(
                sprintf(
                    'Commit event <%s from %s @ %s> with lower bounds %s',
                    $this->nextEvent->taskDetails()->task(),
                    $this->nextEvent->schedule()->name(),
                    $this->nextEvent->dateTime(),
                    $lowerBounds
                )
            );

            $this->nextEvent = null;
        }
    }

    private function waitUntil(DateTime $dateTime)
    {
        $seconds = $dateTime->differenceAsDuration(
            $this->clock->localDateTime()
        )->totalSeconds();

        if ($seconds <= 0) {
            return true;
        }

        $this->logger->debug(sprintf('Sleeping %ds.', $seconds));

        if (0 !== $this->isolator->sleep($seconds)) {
            $this->isolator->pcntl_signal_dispatch();

            return false;
        } else {
            return $this->clock->localDateTime()->compare($dateTime) >= 0;
        }
    }

    private $provider;
    private $dispatcher;
    private $isRunning;
    private $lastReload;
    private $doReload;
    private $reloadInterval;
    private $delayWarningThreshold;
    private $clock;
    private $nextEvent;
    private $isolator;
}
