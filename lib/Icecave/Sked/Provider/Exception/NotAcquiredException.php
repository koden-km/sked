<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use Icecave\Sked\TypeCheck\TypeCheck;
use LogicException;

class NotAcquiredException extends LogicException
{
    /**
     * @param string         $scheduleName
     * @param Exception|null $exception
     */
    public function __construct($scheduleName, Exception $exception = null)
    {
        TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct('The schedule "' . $scheduleName . '" has not previously been acquired.', 0, $exception);
    }
}
