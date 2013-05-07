<?php
namespace Icecave\Sked\Schedule;

interface ScheduleInterface
{
    /**
     * @return string A unique name for the schedule.
     */
    public function name();

    /**
     * @return string The name of the task to be executed.
     */
    public function taskName();

    /**
     * @return mixed The job payload.
     */
    public function payload();

    /**
     * Indicates whether or not missed executions may be skipped.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable();

    /**
     * Controls whether or not missed executions may be skipped.
     *
     * @param boolean $skippable True if executions may be skipped.
     */
    public function setIsSkippable($skippable);
}
