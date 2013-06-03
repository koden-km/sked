<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider\File;

class FileScheduleTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 4) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('name', 0, 'string');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('taskDetails', 1, 'Icecave\\Skew\\Entities\\TaskDetailsInterface');
            }
            if ($argumentCount < 3) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('cronExpression', 2, 'Cron\\CronExpression');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('skippable', 3, 'boolean');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'name',
                0,
                $arguments[0],
                'string'
            );
        }
        $value = $arguments[3];
        if (!\is_bool($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'skippable',
                3,
                $arguments[3],
                'boolean'
            );
        }
    }

    public function taskDetails(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function cronExpression(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

}
