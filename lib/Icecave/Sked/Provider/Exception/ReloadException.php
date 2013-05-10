<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use RuntimeException;

class ReloadException extends RuntimeException
{
    /**
     * @param string         $reason
     * @param Exception|null $exception
     */
    public function __construct($reason, Exception $exception = null)
    {
        parent::__construct($reason, 0, $exception);
    }
}
