<?php
namespace Icecave\Sked\TypeCheck\Validator\Icecave\Sked\Provider\File;

class FileReaderTypeCheck extends \Icecave\Sked\TypeCheck\AbstractValidator
{
    public function validateConstruct(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount > 4) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(4, $arguments[4]);
        }
    }

    public function readDirectories(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('directories', 0, 'mixed<string>');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
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
    }

    public function readFile(array $arguments)
    {
        $argumentCount = \count($arguments);
        if ($argumentCount < 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\MissingArgumentException('filename', 0, 'string');
        } elseif ($argumentCount > 1) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentException(1, $arguments[1]);
        }
        $value = $arguments[0];
        if (!\is_string($value)) {
            throw new \Icecave\Sked\TypeCheck\Exception\UnexpectedArgumentValueException(
                'filename',
                0,
                $arguments[0],
                'string'
            );
        }
    }

}
