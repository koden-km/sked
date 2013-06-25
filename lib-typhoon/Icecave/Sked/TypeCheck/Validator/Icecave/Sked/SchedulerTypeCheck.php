<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked;

class SchedulerTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 3) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('provider', 0, 'Icecave\\Sked\\Provider\\ProviderInterface');
            }
            if ($argumentCount < 2) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('dispatcher', 1, 'Icecave\\Sked\\Dispatcher\\DispatcherInterface');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('logger', 2, 'Psr\\Log\\LoggerInterface');
        } elseif ($argumentCount > 5) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(5, $arguments[5]);
        }
    }

    public function reloadInterval(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setReloadInterval(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('seconds', 0, 'integer');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_int($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'seconds',
                0,
                $arguments[0],
                'integer'
            );
        }
    }

    public function delayWarningThreshold(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setDelayWarningThreshold(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('seconds', 0, 'integer');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_int($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'seconds',
                0,
                $arguments[0],
                'integer'
            );
        }
    }

    public function run(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function setUp(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function tearDown(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function mainLoop(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function reload(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function nextReloadDateTime(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function acquireEvent(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function dispatchEvent(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function rollbackEvent(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function commitEvent(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('lowerBounds', 0, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function waitUntil(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('dateTime', 0, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

}
