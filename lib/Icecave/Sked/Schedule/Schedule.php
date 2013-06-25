<?php
namespace Icecave\Sked\Schedule;

use Icecave\Sked\TypeCheck\TypeCheck;

class Schedule implements ScheduleInterface
{
    /**
     * @param string  $name      A unique name for the schedule.
     * @param boolean $skippable Indicates whether or not missed executions may be skipped.
     */
    public function __construct($name, $skippable = true)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->name = $name;
        $this->skippable = $skippable;
    }

    /**
     * @return string A unique name for the schedule.
     */
    public function name()
    {
        TypeCheck::get(__CLASS__)->name(func_get_args());

        return $this->name;
    }

    /**
     * Indicates whether or not missed executions may be skipped.
     *
     * @return boolean True if executions may be skipped.
     */
    public function isSkippable()
    {
        TypeCheck::get(__CLASS__)->isSkippable(func_get_args());

        return $this->skippable;
    }

    /**
     * Controls whether or not missed executions may be skipped.
     *
     * @param boolean $skippable True if executions may be skipped.
     */
    public function setIsSkippable($skippable)
    {
        TypeCheck::get(__CLASS__)->setIsSkippable(func_get_args());

        $this->skippable = $skippable;
    }

    private $typeCheck;
    private $name;
    private $skippable;
}
