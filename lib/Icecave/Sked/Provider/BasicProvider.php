<?php
namespace Icecave\Sked\Provider;

use Icecave\Chrono\DateTime;
use Icecave\Chrono\Duration\Duration;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\Schedule\ScheduleInterface;
use Icecave\Skew\Entities\TaskDetailsInterface;

class BasicProvider implements ProviderInterface
{
    public function  __construct(ScheduleInterface $schedule, TaskDetailsInterface $taskDetails, Duration $interval)
    {
        $this->schedule = $schedule;
        $this->taskDetails = $taskDetails;
        $this->interval = $interval;
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
        if (null === $this->next) {
            $this->next = $now;
        }

        if ($this->acquiredEvent) {
            return null;
        } elseif ($this->next->compare($upperBound) >= 0) {
            return null;
        }

        return $this->acquiredEvent = new Event(
            $this->schedule,
            $this->taskDetails,
            $this->next
        );
    }

    /**
     * Release a previously acquired event, without dispatching a job or making any changes.
     *
     * @param DateTime $now   The current time.
     * @param Event    $event The previously acquired event.
     */
    public function rollback(DateTime $now, Event $event)
    {
        if ($event !== $this->acquiredEvent) {
            throw new Exception\NotAcquiredException($event->schedule()->name());
        }

        $this->acquiredEvent = null;
    }

    /**
     * Release a previously acquired event and mark the job as dispatched.
     *
     * Future events for the same schedule are guaranteed to have an execution time greater than the specified lower bound.
     *
     * @param DateTime $now        The current time.
     * @param Event    $event      The previously acquired event.
     * @param DateTime $lowerBound Threshold of future event eligibility (event-date > upper-bound).
     */
    public function commit(DateTime $now, Event $event, DateTime $lowerBound)
    {
        if ($event !== $this->acquiredEvent) {
            throw new Exception\NotAcquiredException($event->schedule()->name());
        }

        // Advance the next timestamp if it's out of bounds ...
        if ($this->next->compare($lowerBound) <= 0) {
            $difference = $lowerBound->differenceAsDuration($this->next)->totalSeconds() + 1;
            $iterations = intval(ceil($difference / $this->interval->totalSeconds()));
            $this->next = $this->next->add(new Duration($iterations * $this->interval->totalSeconds()));
        }

        $this->acquiredEvent = null;
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
    private $jobRequest;
    private $interval;
    private $next;
    private $acquiredEvent;
}
