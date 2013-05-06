<?php
namespace Icecave\Sked;

use Icecave\Chrono\DateTime;

/**
 * Represents a specific execution of a schedule.
 */
class ScheduleEvent
{
    public function __construct(ScheduleProvider $provider, ScheduleInterface $schedule, DateTime $dateTime)
    {
        $this->provider = $provider;
        $this->schedule = $schedule;
        $this->dateTime = $dateTime;
    }

    public function provider()
    {
        return $this->provider;
    }

    public function schedule()
    {
        return $this->schedule;
    }

    public function dateTime()
    {
        return $this->dateTime;
    }

    private $provider;
    private $schedule;
    private $dateTime;
}
