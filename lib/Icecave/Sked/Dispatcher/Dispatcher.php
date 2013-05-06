<?php
namespace Icecave\Sked\Dispatcher;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\Event;

class Dispatcher implements DispatcherInterface
{
    public function dispatch(DateTime $now, Event $event)
    {
        static $jobId = 0;

        return strval(++$jobId);
    }
}
