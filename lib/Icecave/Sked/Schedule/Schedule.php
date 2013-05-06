<?php
namespace Icecave\Sked\Schedule;

class Schedule implements ScheduleInterface
{
    public function __construct($id, $taskName, $payload)
    {
        $this->id = $id;
        $this->taskName = $taskName;
        $this->payload = $payload;
        $this->enabled = true;
        $this->skippable = false;
    }

    /**
     * @return string A unique identifier for the schedule.
     */
    public function id()
    {
        return $this->id;
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
     * Indicates whether or not the schedule is enabled.
     *
     * @return boolean True if the schedule is enabled.
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Controls whether or not the schedule is enabled.
     *
     * @param boolean $enabled True if the schedule is enabled.
     */
    public function setIsEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Indicates whether or not missed executions may be skipped when recovering from a down-time.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable()
    {
        return $this->skippable;
    }

    /**
     * Controls whether or not missed executions may be skipped when recovering from a down-time.
     *
     * @param boolean $skippable True if executions may be skipped.
     */
    public function setIsSkippable($skippable)
    {
        $this->skippable = $skippable;
    }

    private $id;
    private $taskName;
    private $payload;
    private $enabled;
    private $skippable;
}
