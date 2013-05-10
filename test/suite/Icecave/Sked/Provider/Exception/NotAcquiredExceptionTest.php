<?php
namespace Icecave\Sked\Provider\Exception;

use Exception;
use PHPUnit_Framework_TestCase;

class NotAcquiredExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $exception = new NotAcquiredException('foo');

        $this->assertSame('The schedule "foo" has not previously been acquired.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructWithException()
    {
        $previousException = new Exception;
        $exception = new NotAcquiredException('foo', $previousException);

        $this->assertSame('The schedule "foo" has not previously been acquired.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
