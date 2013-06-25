<?php
namespace Icecave\Sked;

use Exception;
use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Chrono\Clock\SystemClock;
use Icecave\Chrono\DateTime;
use Icecave\Isolator\Isolator;
use Icecave\Sked\Dispatcher\DispatcherInterface;
use Icecave\Sked\Provider\ProviderInterface;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\TypeCheck\TypeCheck;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Scheduler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param ProviderInterface   $provider   The schedule provider from which schedules are acquired.
     * @param DispatcherInterface $dispatcher The dispatcher used to dispatch jobs.
     * @param LoggerInterface     $logger     Target for scheduling log information.
     * @param ClockInterface|null $clock      The clock to use, or null to use the system clock.
     * @param Isolator|null       $isolator
     */
    public function __construct(
        ProviderInterface $provider,
        DispatcherInterface $dispatcher,
        LoggerInterface $logger,
        ClockInterface $clock = null,
        Isolator $isolator = null
    ) {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->provider = $provider;
        $this->dispatcher = $dispatcher;
        $this->isRunning = false;
        $this->lastReload = DateTime::fromUnixTime(0);
        $this->doReload = false;
        $this->reloadInterval = 600;
        $this->delayWarningThreshold = 30;
        $this->clock = $clock;
        $this->isolator = Isolator::get($isolator);

        $this->setLogger($logger);
    }

    /**
     * @return integer
     */
    public function reloadInterval()
    {
        TypeCheck::get(__CLASS__)->reloadInterval(func_get_args());

        return $this->reloadInterval;
    }

    /**
     * @param integer $seconds
     */
    public function setReloadInterval($seconds)
    {
        TypeCheck::get(__CLASS__)->setReloadInterval(func_get_args());

        $this->reloadInterval = $seconds;
    }

    /**
     * @return integer
     */
    public function delayWarningThreshold()
    {
        TypeCheck::get(__CLASS__)->delayWarningThreshold(func_get_args());

        return $this->delayWarningThreshold;
    }

    /**
     * @param integer $seconds
     */
    public function setDelayWarningThreshold($seconds)
    {
        TypeCheck::get(__CLASS__)->setDelayWarningThreshold(func_get_args());

        $this->delayWarningThreshold = $seconds;
    }

    /**
     * @return boolean
     */
    public function run()
    {
        TypeCheck::get(__CLASS__)->run(func_get_args());

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

    protected function setUp()
    {
        TypeCheck::get(__CLASS__)->setUp(func_get_args());

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

    protected function tearDown()
    {
        TypeCheck::get(__CLASS__)->tearDown(func_get_args());

        $this->isRunning = false;
        $this->doReload = false;
        $this->isolator->pcntl_signal(SIGTERM, SIG_DFL);
        $this->isolator->pcntl_signal(SIGINT, SIG_DFL);
        $this->isolator->pcntl_signal(SIGHUP, SIG_DFL);
        $this->rollbackEvent();
    }

    protected function mainLoop()
    {
        TypeCheck::get(__CLASS__)->mainLoop(func_get_args());

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

    protected function reload()
    {
        TypeCheck::get(__CLASS__)->reload(func_get_args());

        $now = $this->clock->localDateTime();

        try {
            $count = $this->provider->reload($now);
            $this->logger->notice(
                sprintf(
                    'Loaded %d schedule(s), next reload in %ds.',
                    $count,
                    $this->reloadInterval
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

    /**
     * @return DateTime
     */
    protected function nextReloadDateTime()
    {
        TypeCheck::get(__CLASS__)->nextReloadDateTime(func_get_args());

        return $this->lastReload->add($this->reloadInterval);
    }

    /**
     * @return Event|null
     */
    protected function acquireEvent()
    {
        TypeCheck::get(__CLASS__)->acquireEvent(func_get_args());

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

    protected function dispatchEvent()
    {
        TypeCheck::get(__CLASS__)->dispatchEvent(func_get_args());

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
            $lowerBounds = $this->nextEvent->dateTime();

            $delay = $now->differenceAsSeconds($this->nextEvent->dateTime());

            if ($delay >= $this->delayWarningThreshold) {
                $this->logger->warning(
                    sprintf(
                        'Dispatch of job "%s" scheduled at %s was delayed by %ds.',
                        $jobId,
                        $this->nextEvent->dateTime(),
                        $delay
                    )
                );
            }
        }

        $this->commitEvent($lowerBounds);
    }

    protected function rollbackEvent()
    {
        TypeCheck::get(__CLASS__)->rollbackEvent(func_get_args());

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

    /**
     * @param DateTime $lowerBounds
     */
    protected function commitEvent(DateTime $lowerBounds)
    {
        TypeCheck::get(__CLASS__)->commitEvent(func_get_args());

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

    /**
     * @param DateTime $dateTime
     *
     * @return boolean
     */
    protected function waitUntil(DateTime $dateTime)
    {
        TypeCheck::get(__CLASS__)->waitUntil(func_get_args());

        $seconds = $dateTime->differenceAsSeconds(
            $this->clock->localDateTime()
        );

        if ($seconds <= 0) {
            return true;
        }

        $this->logger->debug(
            sprintf('Sleeping %ds until %s', $seconds, $dateTime)
        );

        if (0 === $this->isolator->sleep($seconds)) {
            return $this->clock->localDateTime()->isGreaterThanOrEqualTo($dateTime);
        }

        $this->isolator->pcntl_signal_dispatch();

        return false;
    }

    private $typeCheck;
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
