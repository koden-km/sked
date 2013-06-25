<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Schedule;

class ScheduleTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('name', 0, 'string');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
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
        if ($argumentCount > 1) {
            $value = $arguments[1];
            if (!\is_bool($value)) {
                throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'skippable',
                    1,
                    $arguments[1],
                    'boolean'
                );
            }
        }
    }

    public function name(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function isSkippable(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setIsSkippable(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('skippable', 0, 'boolean');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_bool($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'skippable',
                0,
                $arguments[0],
                'boolean'
            );
        }
    }

}
