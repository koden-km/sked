<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\Duration\Duration;
use Icecave\Sked\Schedule\ScheduleInterface;
use Icecave\Sked\Schedule\Event;

class BasicProvider implements ProviderInterface
{
    public function  __construct(ScheduleInterface $schedule, Duration $interval)
    {
        $this->schedule = $schedule;
        $this->interval = $interval;
        $this->next = null;
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
        if (null === $this->next) {
            $this->next = $now;
        }

        if ($this->event) {
            return null;
        } elseif ($threshold->compare($this->next) < 0) {
            return null;
        }

        $this->event = new Event($this->schedule, $this->next);

        return $this->event;
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
        if (null === $this->event) {
            throw new Exception\NotAcquiredException;
        } elseif ($event !== $this->event) {
            throw new Exception\UnknownEventException;
        } elseif (null !== $dispatchedAt) {
            $this->next = $this->next->add($this->interval);

            // If the schedule is skippable and the job was dispatched after the NEXT intended execution
            // then schedule the next event at the next multiple of the interval after the real dispatch time.
            if ($this->schedule->isSkippable() && $dispatchedAt->compare($this->next) > 0) {
                $delay = $dispatchedAt->differenceAsDuration($next);
                $interval = $this->interval->totalSeconds();
                $iterations = ceil($delay->totalSeconds() / $interval);
                $this->next = $this->next->add(new Duration($iterations * $interval));
            }
        }

        $this->event = null;
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
        return 1;
    }

    private $schedule;
    private $interval;
    private $next;
    private $event;
}
