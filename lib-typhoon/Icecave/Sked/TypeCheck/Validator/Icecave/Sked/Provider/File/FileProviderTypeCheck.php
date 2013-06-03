<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider\File;

class FileProviderTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('directories', 0, 'mixed<string>');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('fileReader', 1, 'Icecave\\Sked\\Provider\\File\\FileReader');
        } elseif ($argumentCount > 4) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
        }
        $value = $arguments[0];
        $check = function ($value) {
            if (!\is_array($value) && !$value instanceof \Traversable) {
                return false;
            }
            foreach ($value as $key => $subValue) {
                if (!\is_string($subValue)) {
                    return false;
                }
            }
            return true;
        };
        if (!$check($arguments[0])) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'directories',
                0,
                $arguments[0],
                'mixed<string>'
            );
        }
        if ($argumentCount > 2) {
            $value = $arguments[2];
            if (!(\is_string($value) || $value === null)) {
                throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                    'schedulePersistanceFilename',
                    2,
                    $arguments[2],
                    'string|null'
                );
            }
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

    public function loadScheduleLowerBounds(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function saveScheduleLowerBounds(array $arguments)
    {
        if (\count($arguments) > 0) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(0, $arguments[0]);
        }
    }

    public function scheduleLowerBound(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('schedule', 0, 'Icecave\\Sked\\Provider\\File\\FileSchedule');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
    }

    public function setScheduleLowerBound(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('schedule', 0, 'Icecave\\Sked\\Provider\\File\\FileSchedule');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('lowerBound', 1, 'Icecave\\Chrono\\DateTime');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

    public function nextRunDateTime(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 2) {
            if ($argumentCount < 1) {
                throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('now', 0, 'Icecave\\Chrono\\DateTime');
            }
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('schedule', 1, 'Icecave\\Sked\\Provider\\File\\FileSchedule');
        } elseif ($argumentCount > 2) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(2, $arguments[2]);
        }
    }

}
