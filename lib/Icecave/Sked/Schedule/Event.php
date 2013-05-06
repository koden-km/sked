<?php
namespace Icecave\Sked\Schedule;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\ScheduleInterface;

/**
 * Represents a specific execution of a schedule.
 */
class Event
{
    public function __construct(ScheduleInterface $schedule, DateTime $dateTime)
    {
        $this->schedule = $schedule;
        $this->dateTime = $dateTime;
    }

    public function schedule()
    {
        return $this->schedule;
    }

    /**
     * @return DateTime The time at which the schedule event is expected to execute.
     */
    public function dateTime()
    {
        return $this->dateTime;
    }

    private $schedule;
    private $dateTime;
}
