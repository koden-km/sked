<?php
namespace Icecave\Sked\Schedule;

interface ScheduleInterface
{
    /**
     * @return string A unique name for the schedule.
     */
    public function name();

    /**
     * Indicates whether or not missed executions may be skipped.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable();
}
