<?php
namespace Icecave\Sked;

use Icecave\Chrono\Clock\ClockInterface;
use Icecave\Isolator\Isolator;
use Icecave\Skew\Client\Client;
use Psr\Log\LoggerInterface;

class Scheduler
{
    /**
     * @param Client                           $skewClient            The Skew client used to enqueue jobs.
     * @param array<ScheduleProviderInterface> $scheduleProviders     An array of schedule providers from which schedules are chosen.
     * @param LoggerInterface                  $logger                Target for scheduling log information.
     * @param integer                          $reloadInterval        The number of seconds between schedule reloads.
     * @param integer                          $delayWarningThreshold The maximum number of seconds a job can be delayed before triggering a warning.
     * @param ClockInterface|null              $clock                 The clock to use, or null to use the system clock.
     * @param Isolator|null                    $isolator
     */
    public function __construct(
        Client $skewClient,
        array $scheduleProviders,
        LoggerInterface $logger,
        $reloadInterval = 300,
        $delayWarningThreshold = 30,
        ClockInterface $clock = null,
        Isolator $isolator = null
    ) {
        if (null === $clock) {
            $clock = new SystemClock;
        }

        $this->skewClient = $skewClient;
        $this->scheduleProviders = $scheduleProviders;
        $this->logger = $logger;
        $this->isRunning = false;
        $this->lastReload = 0;
        $this->reloadInterval = $reloadInterval;
        $this->delayWarningThreshold = $delayWarningThreshold;
        $this->clock = $clock;
        $this->isolator = Isolator::get($isolator);
    }

    public function run()
    {
        if ($this->isRunning) {
            return;
        }

        $this->isRunning = true;
        $this->reloadSchedules();

        try {
            while ($this->isRunning) {
                list($unixTime, $callback) = $this->nextEvent();
                if ($this->waitUntil($unixTime)) {
                    $callback();
                }
            }
        } catch (Exception $e) {
            $this->isRunning = false;

            $this->logger->emergency(
                'Unexpected exception: ' . $e->getMessage(),
                ['exception' => $e]
            );

            throw $e;
        }
    }

    private function nextEvent()
    {
        $currentDateTime = $this->clock->localDateTime();

        $nextReload =


        $nextUnixTime = $this->lastReload + $this->reloadInterval;
        $nextEvent = null;

        // Iterate over the providers to find the next event ...
        foreach ($this->scheduleProviders as $scheduleProvider) {

            // Acquire an event from this provider ...
            if ($event = $scheduleProvider->acquire($currentDateTime)) {

                $unixTime = $event->dateTime()->unixTime();

                // Check if it is new than
                if ($unixTime < $nextUnixTime) {

                    if ($nextEvent) {
                        $nextScheduleProvider->release($nextEvent);
                    }

                    $nextUnixTime = $unixTime;
                    $nextEvent = $event;
                    $nextScheduleProvider = $scheduleProvider;
                }
            }
        }

        if ($nextEvent) {
            return [
                $nextUnixTime,
                function () use ($this, $nextEvent, $nextScheduleProvider) {
                    $this->triggerJob($nextEvent, $nextScheduleProvider);
                }
            ];
        }

        return [
            $nextUnixTime,
            [$this, 'reloadSchedules']
        ];
    }

    private function reloadSchedules()
    {
        $scheduleCount = 0;

        foreach ($this->scheduleProviders as $scheduleProvider) {
            try {
                $scheduleCount += $scheduleProvider->reload();
            } catch (Exception $e) {
                $this->logger->alert(
                    'Unable to load schedules: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }

        $this->lastReload = $this->clock->unixTime();

        $this->logger->info(
            'Schedules reloaded, there are {count} active schedule(s).',
            ['count' => $scheduleCount]
        );
    }

    private function triggerJob(ScheduleEvent $event, ScheduleProviderInterface $scheduleProvider)
    {
        $this->skewClient->submit(
            $event->schedule()->taskName(),
            $this->substitutePayloadVariables(
                $event->schedule()->payload()
            )
        );

        $currentDateTime = $this->clock->localDateTime();

        $scheduleProvider->notify($event, $currentDateTime);

        $this->logger->info(
            'Triggered job {job} ({task}).',
            [
                'job' => $job->id(),
                'task' => $schedule->taskName(),
                'payload' => $payload,
            ]
        );

        $delay = $currentDateTime->unixTime() - $event->dateTime()->unixTime();

        if ($delay > $this->delayWarningThreshold) {
            $this->logger->warning(
                'Job {job} ({task}) scheduled for {expected} was delayed by {delay}s.',
                [
                    'job' => $job->id(),
                    'task' => $schedule->taskName(),
                    'expected' => $scheduledDateTime->isoString(),
                    'delay' => $delay,
                ]
            );
        }
    }

    private function waitUntil($unixTime)
    {
        $seconds = $unixTime - $this->clock->unixTime();

        if ($seconds <= 0) {
            return true;
        }

        return 0 === $this->isolator->sleep($seconds);
    }

    private $skewClient;
    private $scheduleProviders;
    private $logger;
    private $isRunning;
    private $lastReload;
    private $reloadInterval;
    private $delayWarningThreshold;
    private $clock;
    private $isolator;
}
