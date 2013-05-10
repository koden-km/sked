<?php
namespace Icecave\Sked\Schedule;

class Schedule implements ScheduleInterface
{
    /**
     * @param string  $name      A unique name for the schedule.
     * @param boolean $skippable Indicates whether or not missed executions may be skipped.
     */
    public function __construct($name, $skippable = true)
    {
        $this->name = $name;
        $this->skippable = $skippable;
    }

    /**
     * @return string A unique name for the schedule.
     */
    public function name()
    {
        return $this->name;
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
    private $skippable;
}
