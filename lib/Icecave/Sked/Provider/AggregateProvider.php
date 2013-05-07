<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Sked\Schedule\Event;

class AggregateProvider implements ProviderInterface
{
    public function  __construct()
    {
        $this->providers = new Set;
        $this->eventMap = new Map;
    }

    public function add(ProviderInterface $provider)
    {
        $this->providers->add($provider);
    }

    public function remove(ProviderInterface $provider)
    {
        $this->providers->remove($provider);
    }

    /**
     * Acquire the next schedule due to be executed.
     *
     * Once a schedule has been acquired, subsequent calls to acquire() will not yield the same schedule.
     *
     * @param DateTime $now       The current time.
     * @param DateTime $threshold The threshold after which schedules will not be considered for execution.
     *
     * @return Event|null The schedule event describing the next execution, or null if there is none.
     */
    public function acquire(DateTime $now, DateTime $threshold)
    {
        $nextEvent = null;
        $nextProvider = null;

        foreach ($this->providers as $provider) {
            if ($event = $provider->acquire($now, $threshold)) {
                if ($nextEvent) {
                    $nextProvider->release($nextEvent);
                }
                $nextEvent = $event;
                $nextProvider = $provider;
                $threshold = $event->nextExecutionTime();
            }
        }

        $this->eventMap[$nextEvent] = $provider;

        return $nextEvent;
    }

    /**
     * Release a previously acquired schedule event.
     *
     * If $dispatchedAt is non-null the schedule is marked as executed and will not be returned from
     * acquire() until the NEXT scheduled execution.
     *
     * @param DateTime      $now          The current time.
     * @param Event         $event        The schedule event that was processed.
     * @param DateTime|null $dispatchedAt The time at which the job was dispatched for execution, or null if it was not dispatched.
     */
    public function release(DateTime $now, Event $event, DateTime $dispatchedAt = null)
    {
        $this->eventMap[$event]->release($now, $event, $dispatchedAt);
        $this->eventMap->remove($event);
    }

    /**
     * Reload the schedules.
     *
     * @param DateTime $now The current time.
     *
     * @return integer The number of active schedules.
     */
    public function reload(DateTime $now)
    {
        $count = 0;

        foreach ($this->providers as $provider) {
            $count += $provider->reload($now);
        }

        return $count;
    }

    private $providers;
    private $eventMap;
}
