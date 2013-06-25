<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Dispatcher;

class DispatcherTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function dispatch(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('now', 0, 'Icecave\\Chrono\\DateTime');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('event', 1, 'Icecave\\Sked\\Schedule\\Event');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

}
