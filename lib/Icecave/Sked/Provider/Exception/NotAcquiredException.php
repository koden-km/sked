<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use LogicException;

class NotAcquiredException extends LogicException
{
    /**
     * @param string         $scheduleName
     * @param Exception|null $exception
     */
    public function __construct($scheduleName, Exception $exception = null)
    {
        parent::__construct('The schedule "' . $scheduleName . '" has not previously been acquired.', 0, $exception);
    }
}
