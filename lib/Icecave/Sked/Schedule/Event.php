<?php
namespace Icecave\Sked\Schedule;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\ScheduleInterface;
use Icecave\Sked\TypeCheck\TypeCheck;
use Icecave\Skew\Entities\TaskDetailsInterface;

/**
 * An event represents a specific time at which a job is to be dispatched based on a schedule.
 */
class Event
{
    /**
     * @param ScheduleInterface    $schedule    The schedule that produced the event.
     * @param TaskDetailsInterface $taskDetails The details of the task to execute.
     * @param DateTime             $dateTime    The time at which the event is expected to execute.
     */
    public function __construct(ScheduleInterface $schedule, TaskDetailsInterface $taskDetails, DateTime $dateTime)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->schedule = $schedule;
        $this->taskDetails = $taskDetails;
        $this->dateTime = $dateTime;
    }

    /**
     * @return ScheduleInterface The schedule describing the job.
     */
    public function schedule()
    {
        TypeCheck::get(__CLASS__)->schedule(func_get_args());

        return $this->schedule;
    }

    /**
     * @return TaskDetailsInterface
     */
    public function taskDetails()
    {
        TypeCheck::get(__CLASS__)->taskDetails(func_get_args());

        return $this->taskDetails;
    }

    /**
     * @return DateTime The time at which the event is expected to execute.
     */
    public function dateTime()
    {
        TypeCheck::get(__CLASS__)->dateTime(func_get_args());

        return $this->dateTime;
    }

    private $typeCheck;
    private $schedule;
    private $taskDetails;
    private $dateTime;
}
