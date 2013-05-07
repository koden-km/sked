<?php
namespace Icecave\Sked\Schedule;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\ScheduleInterface;

/**
 * An event represents a specific time at which a job is to be dispatched based on a schedule.
 */
class Event
{
    /**
     * @param ScheduleInterface $schedule The schedule describing the job.
     * @param DateTime          $dateTime The time at which the event is expected to execute.
     */
    public function __construct(ScheduleInterface $schedule, DateTime $dateTime)
    {
        $this->schedule = $schedule;
        $this->dateTime = $dateTime;
    }

    /**
     * @return ScheduleInterface The schedule describing the job.
     */
    public function schedule()
    {
        return $this->schedule;
    }

    /**
     * @return DateTime The time at which the event is expected to execute.
     */
    public function dateTime()
    {
        return $this->dateTime;
    }

    private $schedule;
    private $dateTime;
}
