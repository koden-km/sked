<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use Icecave\Sked\TypeCheck\TypeCheck;
use RuntimeException;

class ReloadException extends RuntimeException
{
    /**
     * @param string         $reason
     * @param Exception|null $exception
     */
    public function __construct($reason, Exception $exception = null)
    {
        TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct($reason, 0, $exception);
    }
}
