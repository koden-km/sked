<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Sked\Schedule\Event;

interface ProviderInterface
{
    /**
     * Acquire the next schedule due to be executed.
     *
     * Once a schedule has been acquired, subsequent calls to acquire() will not yield the same schedule.
     *
     * If $threshold is non-null, acquire() will not yield any schedule that is next due after $threshold.
     *
     * @param DateTime $now       The current time.
     * @param DateTime $threshold The threshold after which schedules will not be considered for execution.
     *
     * @return Event|null The schedule event describing the next execution, or null if there is none.
     */
    public function acquire(DateTime $now, DateTime $threshold);

    /**
     * Release a previously acquired schedule event.
     *
     * If $dispatchedAt is non-null the schedule is marked as executed and will not be returned from
     * acquire() until the NEXT scheduled execution.
     *
     * @param Event         $event        The schedule event that was processed.
     * @param DateTime|null $dispatchedAt The time at which the job was dispatched for execution, or null if it was not dispatched.
     */
    public function release(Event $event, DateTime $dispatchedAt = null);

    /**
     * Reload the schedules.
     *
     * @return integer The number of active schedules.
     */
    public function reload();
}
