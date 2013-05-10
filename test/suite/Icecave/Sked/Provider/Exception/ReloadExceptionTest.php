<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class ReloadExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $exception = new ReloadException('This is a test');

        $this->assertSame('This is a test', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructWithException()
    {
        $previousException = new Exception;
        $exception = new ReloadException('This is a test', $previousException);

        $this->assertSame('This is a test', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
