<?php
namespace Icecave\Sked\Provider\File;

use Eloquent\Schemer\Constraint\Reader\SchemaReader;
use Eloquent\Schemer\Constraint\Reader\SchemaReaderInterface;
use Eloquent\Schemer\Reader\ReaderInterface;
use Eloquent\Schemer\Reader\SwitchingScopeResolvingReader;
use Eloquent\Schemer\Validation\BoundConstraintValidator;
use Eloquent\Schemer\Validation\DefaultingConstraintValidator;
use Eloquent\Schemer\Validation\DefaultingConstraintValidatorInterface;
use Icecave\Agenda\Parser\CronParser;
use Icecave\Collections\Map;
use Icecave\Isolator\Isolator;
use Icecave\Skew\Entities\TaskDetails;
use Icecave\Sked\Provider\Exception\ReloadException;
use Icecave\Sked\TypeCheck\TypeCheck;
use InvalidArgumentException;
use Zend\Uri\File As FileUri;

class FileReader
{
    /**
     * @param ReaderInterface|null          $reader
     * @param BoundConstraintValidator|null $constraintValidator
     * @param SchemaReaderInterface|null    $schemaReader
     * @param CronParser|null               $cronParser
     * @param Isolator|null                 $isolator
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

        if (null === $cronParser) {
            $cronParser = new CronParser;
        }

        $this->reader = $reader;
        $this->constraintValidator = $constraintValidator;
        $this->cronParser = $cronParser;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param mixed<string> $directories
     *
     * @return Map<string, FileSchedule>
     * @throws ReloadException
     */
    public function readDirectories($directories)
    {
        TypeCheck::get(__CLASS__)->readDirectories(func_get_args());

        $schedules = new Map;

        foreach ($directories as $dirname) {
            if (!$this->isolator->is_dir($dirname)) {
                throw new ReloadException('Schedule directory is invalid.');
            }

            foreach ($this->isolator->scandir($dirname) as $entry) {
                if ('.' === $entry || '..' === $entry) {
                    continue;
                }

                $path = $dirname . '/' . $entry;
                if ($this->isolator->is_dir($path)) {
                    $schedules = $schedules->combine(
                        $this->readDirectories(array($path))
                    );
                } else {
                    $schedules = $schedules->combine(
                        $this->readFile($path)
                    );
                }
            }
        }

        return $schedules;
    }

    /**
     * @param string $filename
     *
     * @return Map<string, FileSchedule>
     * @throws ReloadException
     */
    public function readFile($filename)
    {
        TypeCheck::get(__CLASS__)->readFile(func_get_args());

        $value = $this->reader->readPath($filename);

        if ($this->constraintValidator->validator() instanceof DefaultingConstraintValidatorInterface) {
            $result = $this->constraintValidator->validator()->validateAndApplyDefaults(
                $this->constraintValidator->constraint(),
                $value
            );
        } else {
            $result = $this->constraintValidator->validate($value);
        }

        if (!$result->isValid()) {
            throw new ReloadException('Schedule file is invalid.');
        }

        $schedules = new Map;

        try {
            foreach ($value as $scheduleName => $details) {
                $taskDetails = new TaskDetails($details->task->value());
                $taskDetails->setPayload($details->payload->value());
                $taskDetails->setTags($details->tags->value());

                $schedule = new FileSchedule(
                    $scheduleName,
                    $taskDetails,
                    $cronParser->parse($details->schedule->value()),
                    $details->skippable->value()
                );

                $schedules->add($scheduleName, $schedule);
            }
        } catch (\Eloquent\Schemer\Value\Exception\UndefinedPropertyException $e) {
            throw new ReloadException('Schedule file is invalid.');
        } catch (InvalidArgumentException $e) {
            throw new ReloadException($e->getMessage());
        }

        return $schedules;
    }

    private $typeCheck;
    private $reader;
    private $constraintValidator;
    private $schemaReader;
    private $cronParser;
    private $isolator;
}
