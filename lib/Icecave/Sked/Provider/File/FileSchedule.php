<?php
namespace Icecave\Sked\Provider\File;

use Icecave\Agenda\ScheduleInterface as AgendaScheduleInterface;
use Icecave\Sked\Schedule\Schedule;
use Icecave\Sked\TypeCheck\TypeCheck;
use Icecave\Skew\Entities\TaskDetails;
use Icecave\Skew\Entities\TaskDetailsInterface;

class FileSchedule extends Schedule
{
    /**
     * @param string                  $name
     * @param TaskDetailsInterface    $taskDetails
     * @param AgendaScheduleInterface $agendaSchedule
     * @param boolean                 $skippable
     */
    public function __construct($name, TaskDetailsInterface $taskDetails, AgendaScheduleInterface $agendaSchedule, $skippable)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct($name, $skippable);

        $this->taskDetails = $taskDetails;
        $this->agendaSchedule = $agendaSchedule;
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
     * @return AgendaScheduleInterface
     */
    public function agendaSchedule()
    {
        TypeCheck::get(__CLASS__)->agendaSchedule(func_get_args());

        return $this->agendaSchedule;
    }

    private $typeCheck;
    private $taskDetails;
    private $agendaSchedule;
}
