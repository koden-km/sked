<?php
namespace Icecave\Sked\Provider\File;

use Icecave\Chrono\DateTime;
use Icecave\Collections\Set;
use Icecave\Collections\Map;
use Icecave\Isolator\Isolator;
use Icecave\Sked\Provider\Exception\NotAcquiredException;
use Icecave\Sked\Provider\ProviderInterface;
use Icecave\Sked\Schedule\Event;
use Icecave\Sked\TypeCheck\TypeCheck;

class FileProvider implements ProviderInterface
{
    /**
     * @param mixed<string>   $directories
     * @param FileReader|null $fileReader
     * @param string|null     $scheduleLowerBoundsFilename The file to store schedule lower bounds for persistance between app restarts, or null to not persist them.
     * @param Isolator|null   $isolator
     */
    public function __construct($directories, FileReader $fileReader = null, $scheduleLowerBoundsFilename = null, Isolator $isolator = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $fileReader) {
            $fileReader = new FileReader;
        }

        $this->directories = $directories;
        $this->fileReader = $fileReader;
        $this->scheduleLowerBoundsFilename = $scheduleLowerBoundsFilename;
        $this->scheduleLowerBounds = new Map;
        $this->acquiredSchedules = new Set;
        $this->unacquiredSchedules = new Set;
        $this->isolator = Isolator::get($isolator);

        $this->loadScheduleLowerBounds();
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

        $nextSchedule = null;
        $nextDateTime = $upperBound;

        foreach ($this->unacquiredSchedules as $schedule) {
            $dateTime = $this->nextRunDateTime($schedule);
            if ($dateTime->isLessThan($nextDateTime)) {
                $nextSchedule = $schedule;
                $nextDateTime = $dateTime;
            }
        }

        if ($nextSchedule) {
            $this->unacquiredSchedules->remove($nextSchedule);
            $this->acquiredSchedules->add($nextSchedule);

            return new Event(
                $nextSchedule,
                $nextSchedule->taskDetails(),
                $nextDateTime
            );
        }

        return null;
    }

    /**
     * Release a previously acquired event, without dispatching a job or making any changes.
     *
     * @param DateTime $now   The current time.
     * @param Event    $event The previously acquired event.
     */
    public function rollback(DateTime $now, Event $event)
    {
        TypeCheck::get(__CLASS__)->rollback(func_get_args());

        if (!$this->acquiredSchedules->contains($event->schedule())) {
            throw new NotAcquiredException($event->schedule()->name());
        }

        $this->unacquiredSchedules->add($event->schedule());
        $this->acquiredSchedules->remove($event->schedule());
    }

    /**
     * Release a previously acquired event and mark the job as dispatched.
     *
     * Future events for the same schedule are guaranteed to have an execution time greater than the specified lower bound.
     *
     * @param DateTime $now        The current time.
     * @param Event    $event      The previously acquired event.
     * @param DateTime $lowerBound Threshold of future event eligibility (event-date > lower-bound).
     */
    public function commit(DateTime $now, Event $event, DateTime $lowerBound)
    {
        TypeCheck::get(__CLASS__)->commit(func_get_args());

        if (!$this->acquiredSchedules->contains($event->schedule())) {
            throw new NotAcquiredException($event->schedule()->name());
        }

        $this->setScheduleLowerBound($event->schedule(), $lowerBound);

        $this->unacquiredSchedules->add($event->schedule());
        $this->acquiredSchedules->remove($event->schedule());
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

        $schedules = $this->fileReader->readDirectories($this->directories);

        $this->acquiredSchedules->clear();
        $this->unacquiredSchedules->clear();

        $this->unacquiredSchedules->unionInPlace($schedules->values());

        return $schedules->count();
    }

    /**
     * @param DateTime     $now      The current time.
     * @param FileSchedule $schedule The schedule to get the next run date time for.
     *
     * @return DateTime The next run date time.
     */
    protected function nextRunDateTime(DateTime $now, FileSchedule $schedule)
    {
        TypeCheck::get(__CLASS__)->nextRunDateTime(func_get_args());

        $scheduleLowerBound = $this->scheduleLowerBound($schedule);
        if ($scheduleLowerBound) {
            return $schedule->agendaSchedule()->firstEventAfter($scheduleLowerBound);
        }

        return $schedule->agendaSchedule()->firstEventFrom($now);
    }

    /**
     * @param FileSchedule $schedule
     *
     * @return DateTime|null
     */
    protected function scheduleLowerBound(FileSchedule $schedule)
    {
        TypeCheck::get(__CLASS__)->scheduleLowerBound(func_get_args());

        return $this->scheduleLowerBounds->getWithDefault($schedule->name());
    }

    /**
     * @param FileSchedule $schedule
     * @param DateTime     $lowerBound
     */
    protected function setScheduleLowerBound(FileSchedule $schedule, DateTime $lowerBound)
    {
        TypeCheck::get(__CLASS__)->setScheduleLowerBound(func_get_args());

        $this->scheduleLowerBounds[$schedule->name()] = $lowerBound;

        $this->saveScheduleLowerBounds();
    }

    protected function loadScheduleLowerBounds()
    {
        TypeCheck::get(__CLASS__)->loadScheduleLowerBounds(func_get_args());

        if (null === $this->scheduleLowerBoundsFilename) {
            return;
        }

        if (!$this->isolator->is_file($this->scheduleLowerBoundsFilename)) {
            return;
        }

        $data = $this->isolator->unserialize(
            $this->isolator->file_get_contents($this->scheduleLowerBoundsFilename)
        );
        foreach ($data as $name => $isoDateTime) {
            $this->scheduleLowerBounds[$name] = DateTime::fromIsoString($isoDateTime);
        }
    }

    protected function saveScheduleLowerBounds()
    {
        TypeCheck::get(__CLASS__)->saveScheduleLowerBounds(func_get_args());

        if (null === $this->scheduleLowerBoundsFilename) {
            return;
        }

        $data = array();
        foreach ($this->scheduleLowerBounds as $name => $dateTime) {
            $data[$name] = $dateTime->isoString();
        }
        $this->isolator->file_put_contents(
            $this->scheduleLowerBoundsFilename,
            $this->isolator->serialize($data)
        );
    }

    private $typeCheck;
    private $directories;
    private $fileReader;
    private $scheduleLowerBoundsFilename;
    private $scheduleLowerBounds;
    private $acquiredSchedules;
    private $unacquiredSchedules;
    private $isolator;
}
