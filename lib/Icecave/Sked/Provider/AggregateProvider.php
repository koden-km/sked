<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Collections\Map;
use Icecave\Collections\Set;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\TypeCheck\TypeCheck;

class AggregateProvider implements ProviderInterface
{
    public function __construct()
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->providers = new Set;
        $this->eventMap = new Map;
    }

    /**
     * @param ProviderInterface $provider
     */
    public function add(ProviderInterface $provider)
    {
        TypeCheck::get(__CLASS__)->add(func_get_args());

        $this->providers->add($provider);
    }

    /**
     * @param ProviderInterface $provider
     */
    public function remove(ProviderInterface $provider)
    {
        TypeCheck::get(__CLASS__)->remove(func_get_args());

        $this->providers->remove($provider);
    }

    /**
     * @return Set<ProviderInterface>
     */
    public function providers()
    {
        TypeCheck::get(__CLASS__)->providers(func_get_args());

        return $this->providers;
    }

    /**
     * Acquire the first event due before the given upper bound.
     *
     * Once an event has been acquired, subsequent calls to acquire() will not yield any event for the same schedule.
     * Until the event has been released using either {@see ProviderInterface::rollback()} or {@see ProviderInterface::commit()}.
     *
     * @param DateTime $now        The current time.
     * @param DateTime $upperBound Threshold of event eligibility (event-date < upper-bound).
     *
     * @return Event|null The event describing the next execution, or null if there is none.
     */
    public function acquire(DateTime $now, DateTime $upperBound)
    {
        TypeCheck::get(__CLASS__)->acquire(func_get_args());

        $nextEvent = null;
        $nextProvider = null;

        foreach ($this->providers as $provider) {
            if ($event = $provider->acquire($now, $upperBound)) {
                if ($nextEvent) {
                    $nextProvider->rollback($now, $nextEvent);
                }
                $nextEvent = $event;
                $nextProvider = $provider;
                $upperBound = $event->dateTime();
            }
        }

        $this->eventMap[$nextEvent] = $nextProvider;

        return $nextEvent;
    }

    /**
     * Release a previously acquired event, without dispatching a job or making any changes.
     *
     * @param DateTime $now   The current time.
     * @param Event    $event The previously acquired event.
     *
     * @throws Exception\NotAcquiredException
     */
    public function rollback(DateTime $now, Event $event)
    {
        TypeCheck::get(__CLASS__)->rollback(func_get_args());

        if ($this->eventMap->tryGet($event, $provider)) {
            $provider->rollback($now, $event);
            $this->eventMap->remove($event);
        } else {
            throw new Exception\NotAcquiredException($event->schedule()->name());
        }
    }

    /**
     * Release a previously acquired event and mark the job as dispatched.
     *
     * Future events for the same schedule are guaranteed to have an execution time greater than the specified lower bound.
     *
     * @param DateTime $now        The current time.
     * @param Event    $event      The previously acquired event.
     * @param DateTime $lowerBound Threshold of future event eligibility (event-date > upper-bound).
     *
     * @throws Exception\NotAcquiredException
     */
    public function commit(DateTime $now, Event $event, DateTime $lowerBound)
    {
        TypeCheck::get(__CLASS__)->commit(func_get_args());

        if ($this->eventMap->tryGet($event, $provider)) {
            $provider->commit($now, $event, $lowerBound);
            $this->eventMap->remove($event);
        } else {
            throw new Exception\NotAcquiredException($event->schedule()->name());
        }
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
        TypeCheck::get(__CLASS__)->reload(func_get_args());

        $count = 0;

        foreach ($this->providers as $provider) {
            $count += $provider->reload($now);
        }

        return $count;
    }

    private $typeCheck;
    private $providers;
    private $eventMap;
}
