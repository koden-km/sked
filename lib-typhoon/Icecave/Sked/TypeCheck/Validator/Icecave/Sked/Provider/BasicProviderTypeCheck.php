<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider;

class BasicProviderTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
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
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('interval', 2, 'Icecave\\Chrono\\TimeSpan\\Duration');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
    }

    public function acquire(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('now', 0, 'Icecave\\Chrono\\DateTime');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('upperBound', 1, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function rollback(array $arguments)
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

    public function commit(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('now', 0, 'Icecave\\Chrono\\DateTime');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('event', 1, 'Icecave\\Sked\\Schedule\\Event');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('lowerBound', 2, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 3) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(3, $arguments[3]);
        }
    }

    public function reload(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('now', 0, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
