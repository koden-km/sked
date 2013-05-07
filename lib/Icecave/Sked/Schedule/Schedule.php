<?php
namespace Icecave\Sked\Schedule;

/**
 * A schedule defines the execution interval(s) and task information used to produce jobs.
 */
class Schedule implements ScheduleInterface
{
    /**
     * @param string        $name     A unique name for the schedule.
     * @param string        $taskName The name of the Skew task to execute.
     * @param mixed         $payload  The payload data to send with the job.
     * @param array<string> $tags     Tags for the job request.
     */
    public function __construct($name, $taskName, $payload)
    {
        $this->name = $name;
        $this->taskName = $taskName;
        $this->payload = $payload;
        $this->skippable = false;
    }

    /**
     * @return string A unique name for the schedule.
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string The name of the task to be executed.
     */
    public function taskName()
    {
        return $this->taskName;
    }

    /**
     * @return mixed The job payload.
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * Indicates whether or not missed executions may be skipped.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable()
    {
        return $this->skippable;
    }

    /**
     * Controls whether or not missed executions may be skipped.
     *
     * @param boolean $skippable True if executions may be skipped.
     */
    public function setIsSkippable($skippable)
    {
        $this->skippable = $skippable;
    }

    private $name;
    private $taskName;
    private $payload;
    private $skippable;
}
