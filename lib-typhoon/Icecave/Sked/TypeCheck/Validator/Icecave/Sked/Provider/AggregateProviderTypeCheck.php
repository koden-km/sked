<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider;

class AggregateProviderTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function add(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('provider', 0, 'Icecave\\Sked\\Provider\\ProviderInterface');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function remove(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('provider', 0, 'Icecave\\Sked\\Provider\\ProviderInterface');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function providers(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
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
