<?php
namespace Icecave\Sked\Provider\File;

use Cron\CronExpression;
use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Constraint\Reader\SchemaReaderInterface;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\SwitchingScopeResolvingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Validation\DefaultingConstraintValidator;
use Icecave\Collections\Map;
use Icecave\Isolator\Isolator;
use Icecave\Skew\Entities\TaskDetails;
use Icecave\Sked\Provider\Exception\ReloadException;
use Icecave\Sked\TypeCheck\TypeCheck;
use Zend\Uri\File As FileUri;
use LogicException;

class FileReader
{
    /**
     * @param ReaderInterface|null $reader
     * @param BoundConstraintValidator|null $constraintValidator
     * @param SchemaReaderInterface|null $schemaReader
     * @param Isolator|null $isolator
     */
    public function __construct(
        ReaderInterface $reader = null,
        BoundConstraintValidator $constraintValidator = null,
        SchemaReaderInterface $schemaReader = null,
        Isolator $isolator = null
    ) {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        if (null === $reader) {
            $reader = new SwitchingScopeResolvingReader;
        }

        if (null === $schemaReader) {
            $schemaReader = new SchemaReader;
        }

        if (null === $constraintValidator) {
            $constraintValidator = new BoundConstraintValidator(
                new DefaultingConstraintValidator,
                $schemaReader->readPath(__DIR__ . '/../../../../../res/schedule-config.schema.json')
            );
        }

        $this->reader = $reader;
        $this->constraintValidator = $constraintValidator;
        $this->schemaReader = $schemaReader;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param mixed<string> $directories
     *
     * @return Map<string, FileSchedule>
     */
    public function readDirectories($directories)
    {
        TypeCheck::get(__CLASS__)->readDirectories(func_get_args());

        $schedules = new Map;

// echo PHP_EOL . __METHOD__ . ' [directories=' . gettype($directories) . ']' . PHP_EOL;

        foreach ($directories as $dirname) {

// echo '- [is_dir() dirname=' . $dirname . ']' . PHP_EOL;

            if (!$this->isolator->is_dir($dirname)) {
                throw new LogicException('Not a directory: "' . $dirname . '".');
            }

// echo '- [scandir() dirname=' . $dirname . ']' . PHP_EOL;

            foreach ($this->isolator->scandir($dirname) as $entry) {
                if ('.' === $entry || '..' === $entry) {
                    continue;
                }

                $path = $dirname . '/' . $entry;

// echo '- [is_dir() path=' . $path . ']' . PHP_EOL;

                if ($this->isolator->is_dir($path)) {
// echo '- [readDirectories() array path=' . $path . ']' . PHP_EOL;
                    $schedules->combine(
                        $this->readDirectories(array($path))
                    );
                } else {
// echo '- [readFile() path=' . $path . ']' . PHP_EOL;
                    $schedules->combine(
                        $this->readFile($path)
                    );
                }
            }
        }

// echo '- Done.' . PHP_EOL;

        return $schedules;
    }

    /**
     * @param string $filename
     *
     * @return Map<string, FileSchedule>
     */
    public function readFile($filename)
    {
        TypeCheck::get(__CLASS__)->readFile(func_get_args());

        $value = $this->reader->readPath($filename);

        $result = $this->constraintValidator->validate($value);
        if (!$result->isValid()) {
            throw new ReloadException('Schedule file is invalid.');
        }

        $schedules = new Map;

        foreach ($value as $scheduleName => $details) {
            $taskDetails = new TaskDetails($details->task);
            $taskDetails->setPayload($details->payload);
            $taskDetails->setTags($details->tags);

            $schedule = new FileSchedule(
                $scheduleName,
                $taskDetails,
                CronExpression::factory($details->schedule),
                $details->skippable
            );

            $schedules->add($scheduleName, $schedule);
        }

        return $schedules;
    }

    private $typeCheck;
    private $reader;
    private $constraintValidator;
    private $schemaReader;
    private $isolator;
}
