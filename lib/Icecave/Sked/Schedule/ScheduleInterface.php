<?php
namespace Icecave\Sked\Schedule;

interface ScheduleInterface
{
    /**
     * @return string A unique identifier for the schedule.
     */
    public function id();

    /**
     * @return string The name of the task to be executed.
     */
    public function taskName();

    /**
     * @return mixed The job payload.
     */
    public function payload();

    /**
     * Indicates whether or not the schedule is enabled.
     *
     * @return boolean True if the schedule is enabled.
     */
    public function isEnabled();

    /**
     * Controls whether or not the schedule is enabled.
     *
     * @param boolean $enabled True if the schedule is enabled.
     */
    public function setIsEnabled($enabled);

    /**
     * Indicates whether or not missed executions may be skipped when recovering from a down-time.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable();

    /**
     * Controls whether or not missed executions may be skipped when recovering from a down-time.
     *
     * @param boolean $skippable True if executions may be skipped.
     */
    public function setIsSkippable($skippable);
}
