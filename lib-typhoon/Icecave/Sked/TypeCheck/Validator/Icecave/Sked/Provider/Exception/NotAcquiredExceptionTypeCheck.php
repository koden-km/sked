<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider\Exception;

class NotAcquiredExceptionTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('scheduleName', 0, 'string');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'scheduleName',
                0,
                $arguments[0],
                'string'
            );
        }
    }

}
