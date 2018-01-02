<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Storage;
use Mathepa\Token;

// Exceptions
use Mathepa\Exception\InvalidVariableException;

/**
 * Class StorageTest
 */
class StorageTest extends TestCase
{
    /**
     * @test
     */
    public function variableNamesMustStartWithAnAsciiLetter()
    {
        $vars = new Storage();

        $this->assertFalse(invokeMethod($vars, 'isValidName', ['00']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['0var']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['#var3']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['.var4']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['-var1']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['_var2']));
    }

    /**
     * @test
     */
    public function variableNamesMustConsistOnlyOfAsciiLettersAndDigits()
    {
        $vars = new Storage();

        $this->assertTrue(invokeMethod($vars, 'isValidName', ['v']));
        $this->assertTrue(invokeMethod($vars, 'isValidName', ['var0']));
        $this->assertTrue(invokeMethod($vars, 'isValidName', ['v00R']));
        $this->assertTrue(invokeMethod($vars, 'isValidName', ['vAr00']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['var3#']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['va.r4']));
        $this->assertFalse(invokeMethod($vars, 'isValidName', ['va_r1-']));
    }

    /**
     * @test
     */
    public function setAWrongVariableNameThrowsAnException()
    {
        // Varialbe name cannot begin  with a digit
        $this->expectException(InvalidVariableException::class);
        (new Storage())->set('2var', '1 + 2');
    }

    /**
     * @test
     */
    public function getANonExistentVariableNameThrowsAnException()
    {
        $this->expectException(InvalidVariableException::class);
        (new Storage())->get('myVar');
    }

    /**
     * @test
     */
    public function storageObjectsAreIterable()
    {
        $vars = (new Storage())
            ->set('var1', 1)
            ->set('var2', 2)
            ->set('var3', 3);

        $x = 0;
        foreach ($vars as $key => $value) {
            $this->assertEquals('var' . ++$x, $key);
            $this->assertEquals($x, $value);
        }
        $this->assertEquals($x, 3);
    }
}
