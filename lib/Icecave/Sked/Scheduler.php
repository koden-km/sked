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
            $reloadInterval = new Duration(300);
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
            return;
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

        // Log unexpected exceptions ...
        } catch (Exception $e) {
            $this->logger->critical(
                'Terminating due to unexpected exception: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    private function setUp()
    {
        $self = $this;

        $handler = function ($signal) use ($self) {
            switch ($signal) {
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
        $this->isolator->pcntl_signal(SIGHUP, $handler);
    }

    private function tearDown()
    {
        $this->isRunning = false;
        $this->doReload = false;
        $this->isolator->pcntl_signal(SIGTERM, SIG_DFL);
        $this->isolator->pcntl_signal(SIGHUP, SIG_DFL);

        if ($this->nextEvent) {
            $this->provider->release($this->nextEvent);
            $this->logger->debug(
                sprintf(
                    'Released undispatched "%s" task from schedule "%s" due at %s.',
                    $this->nextEvent->schedule()->taskName(),
                    $this->nextEvent->schedule()->id(),
                    $this->nextEvent->dateTime()
                ),
                ['payload' => $this->nextEvent->schedule()->payload()]
            );
            $this->nextEvent = null;
        }
    }

    private function mainLoop()
    {
        while ($this->isRunning) {

            $this->isolator->pcntl_signal_dispatch();

            // Handle reload due to signal ...
            if ($this->doReload) {
                $this->reload();
                $this->doReload = false;
            }

            // Calculate time of next reload ...
            $nextReload = $this->lastReload->add($this->reloadInterval);

            // Look for an event before the next reload, if we don't already have one ...
            if (null === $this->nextEvent) {
                $this->nextEvent = $this->provider->acquire(
                    $this->clock->localDateTime(),
                    $nextReload
                );

                if (null !== $this->nextEvent) {
                    $this->logger->debug(
                        sprintf(
                            'Acquired "%s" task from schedule "%s" due at %s.',
                            $this->nextEvent->schedule()->taskName(),
                            $this->nextEvent->schedule()->id(),
                            $this->nextEvent->dateTime()
                        ),
                        ['payload' => $this->nextEvent->schedule()->payload()]
                    );
                }
            }

            // If we *still* don't have an event, wait until the next reload ...
            if (null === $this->nextEvent) {
                if ($this->waitUntil($nextReload)) {
                    $this->doReload = true;
                }

            // Otherwise wait for the event time before dispatching ...
            } elseif ($this->waitUntil($this->nextEvent->dateTime())) {
                $this->dispatch($this->nextEvent);
                $this->nextEvent = null;
            }
        }
    }

    private function reload()
    {
        try {
            $count = $this->provider->reload();
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

        $this->lastReload = $this->clock->localDateTime();
    }

    private function dispatch(Event $event)
    {
        $now = $this->clock->localDateTime();

        $jobId = $this->dispatcher->dispatch($now, $event);

        $this->logger->info(
            sprintf(
                'Job "%s" dispatched: "%s" task from schedule "%s".',
                $jobId,
                $event->schedule()->taskName(),
                $event->schedule()->id()
            )
        );

        $this->provider->release($event, $now);

        $this->logger->debug(
            sprintf(
                'Released dispatched "%s" task from schedule "%s" due at %s',
                $this->nextEvent->schedule()->taskName(),
                $this->nextEvent->schedule()->id(),
                $this->nextEvent->dateTime()
            ),
            ['payload' => $this->nextEvent->schedule()->payload()]
        );

        $delay = $now->differenceAsDuration($event->dateTime());

        if ($delay->compare($this->delayWarningThreshold) >= 0) {
            $this->logger->warning(
                sprintf(
                    'Dispatch of job "%s" scheduled at %s was delayed by %ds.',
                    $jobId,
                    $event->dateTime(),
                    $delay->totalSeconds()
                )
            );
        }
    }

    private function waitUntil(DateTime $dateTime)
    {
        $seconds = $dateTime->differenceAsDuration($this->clock->localDateTime())->totalSeconds();

        if ($seconds <= 0) {
            return true;
        }

        $this->logger->debug(
            sprintf(
                'Sleeping %ds.',
                $seconds
            )
        );

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
