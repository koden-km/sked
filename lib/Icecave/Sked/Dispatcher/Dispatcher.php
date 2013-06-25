<?php
namespace Icecave\Sked\Dispatcher;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\TypeCheck\TypeCheck;

class Dispatcher implements DispatcherInterface
{
    public function __construct()
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());
    }

    /**
     * @param DateTime $now
     * @param Event    $event
     *
     * @return string
     */
    public function dispatch(DateTime $now, Event $event)
    {
        TypeCheck::get(__CLASS__)->dispatch(func_get_args());

        static $jobId = 0;

        return strval(++$jobId);
    }

    private $typeCheck;
}
