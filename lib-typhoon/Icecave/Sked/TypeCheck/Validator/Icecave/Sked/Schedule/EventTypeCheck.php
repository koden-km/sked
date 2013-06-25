<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Schedule;

class EventTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('schedule', 0, 'Icecave\\Sked\\Schedule\\ScheduleInterface');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('taskDetails', 1, 'Icecave\\Skew\\Entities\\TaskDetailsInterface');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('dateTime', 2, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
    }

    public function schedule(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function taskDetails(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function dateTime(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

}
