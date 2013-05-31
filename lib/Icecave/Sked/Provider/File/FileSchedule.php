<?php
namespace Icecave\Sked\Provider\File;

use Cron\CronExpression;
use Icecave\Sked\Schedule\Schedule;
use Icecave\Sked\Schedule\ScheduleInterface;
use Icecave\Sked\TypeCheck\TypeCheck;
use Icecave\Skew\Entities\TaskDetails;
use Icecave\Skew\Entities\TaskDetailsInterface;

class FileSchedule extends Schedule
{
    /**
     * @param string $name
     * @param TaskDetailsInterface $taskDetails
     * @param CronExpression $cronExpression
     * @param boolean $skippable
     */
    public function __construct($name, TaskDetailsInterface $taskDetails, CronExpression $cronExpression, $skippable)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct($name, $skippable);

        $this->taskDetails = $taskDetails;
        $this->cronExpression = $cronExpression;
    }

    /**
     * @return TaskDetailsInterface The details of the task to be executed.
     */
    public function taskDetails()
    {
        TypeCheck::get(__CLASS__)->taskDetails(func_get_args());

        return $this->taskDetails;
    }

    /**
     * @return CronExpression
     */
    public function cronExpression()
    {
        TypeCheck::get(__CLASS__)->cronExpression(func_get_args());

        return $this->cronExpression;
    }

    private $typeCheck;
    private $taskDetails;
    private $cronExpression;
}
