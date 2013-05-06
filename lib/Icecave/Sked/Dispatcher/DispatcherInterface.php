<?php
namespace Icecave\Sked\Dispatcher;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\Event;

interface DispatcherInterface
{
    public function dispatch(DateTime $now, Event $event);
}
