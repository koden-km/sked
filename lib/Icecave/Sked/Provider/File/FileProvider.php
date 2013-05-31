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
     * @param mixed<string> $directories
     * @param FileReader    $fileReader
     * @param string        $schedulePersistanceFilename
     * @param Isolator|null $isolator
     */
    public function __construct($directories, FileReader $fileReader, $schedulePersistanceFilename, Isolator $isolator = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $fileReader) {
            $fileReader = new FileReader;
        }

        $this->directories = $directories;
        $this->fileReader = $fileReader;
        $this->schedulePersistanceFilename = $schedulePersistanceFilename;
        $this->acquiredSchedules = new Set;
        $this->unacquiredSchedules = new Set;
        $this->scheduleLowerBound = new Map;
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
     * @param DateTime $lowerBound Threshold of future event eligibility (event-date > upper-bound).
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

    private function loadScheduleLowerBounds()
    {
        TypeCheck::get(__CLASS__)->loadScheduleLowerBounds(func_get_args());

        // TODO: use the isolator

        if (!is_file($this->schedulePersistanceFilename)) {
            return;
        }

        $result = file_get_contents($this->schedulePersistanceFilename);
        if ($result !== false) {
            $this->scheduleLowerBound->unserialize($result);
        }
    }

    private function saveScheduleLowerBounds()
    {
        TypeCheck::get(__CLASS__)->saveScheduleLowerBounds(func_get_args());

        // TODO: use the isolator

        $result = file_put_contents($this->schedulePersistanceFilename, $this->scheduleLowerBound->serialize());
        if ($result === false) {
            // throw?
        }
    }

    /**
     * @param FileSchedule $schedule
     *
     * @return DateTime|null
     */
    private function scheduleLowerBound(FileSchedule $schedule)
    {
        TypeCheck::get(__CLASS__)->scheduleLowerBound(func_get_args());

        return $this->scheduleLowerBound->getWithDefault($schedule->name());
    }

    /**
     * @param FileSchedule $schedule
     * @param DateTime     $lowerBound
     */
    private function setScheduleLowerBound(FileSchedule $schedule, DateTime $lowerBound)
    {
        TypeCheck::get(__CLASS__)->setScheduleLowerBound(func_get_args());

        $this->scheduleLowerBound[$schedule->name()] = $lowerBound;

        $this->saveScheduleLowerBounds();
    }

    /**
     * @param DateTime     $now      The current time.
     * @param FileSchedule $schedule The schedule to get the next run date time for.
     *
     * @return DateTime The next run date time.
     */
    private function nextRunDateTime(DateTime $now, FileSchedule $schedule)
    {
        TypeCheck::get(__CLASS__)->nextRunDateTime(func_get_args());

        $scheduleLowerBound = $this->scheduleLowerBound($schedule);
        if ($scheduleLowerBound) {
            $dateTime = $schedule->cronExpression()->getNextRunDate($scheduleLowerBound->nativeDateTime(), 1);
        } else {
            $dateTime = $schedule->cronExpression()->getNextRunDate($now->nativeDateTime());
        }

        return DateTime::fromNativeDateTime($dateTime);
    }

    private $typeCheck;
    private $directories;
    private $fileReader;
    private $schedulePersistanceFilename;
    private $acquiredSchedules;
    private $unacquiredSchedules;
    private $scheduleLowerBound;
    private $isolator;
}
