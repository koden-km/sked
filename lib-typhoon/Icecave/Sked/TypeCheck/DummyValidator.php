<?php
namespace Icecave\Sked\TypeCheck;

class DummyValidator extends AbstractValidator
{
    public function __call($name, array $arguments)
    {
    }

}
