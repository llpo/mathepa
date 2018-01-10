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
        (new Storage())->set(
            '2var',
            ...[new Token(Token::TYPE_LITERAL, '2', 0, 1, 0)]
        );
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
    public function variablesWithCircularReferencesThrowsAnException()
    {
        $vars = new Storage();
        $this->expectException(InvalidVariableException::class);
        $this->expectExceptionMessage(
            'Found circular reference for variable "var1"'
        );
        $vars->set(
            'var1',
            ...[
                new Token(Token::TYPE_VARIABLE, 'var2', 0, 1, 0),
                new Token(Token::TYPE_ARITHMETIC_OPERATOR, '+', 4, 1, 5),
                new Token(Token::TYPE_VARIABLE, 'var1', 5, 1, 9)
            ]
        );
    }

    /**
     * @test
     */
    public function variablesWithIndirectCircularReferencesThrowsAnException()
    {
        $vars = new Storage();
        $vars->set(
            'var1',
            ...[
                new Token(Token::TYPE_VARIABLE, 'var2', 0, 1, 0),
                new Token(Token::TYPE_ARITHMETIC_OPERATOR, '+', 4, 1, 5),
                new Token(Token::TYPE_VARIABLE, 'var3', 5, 1, 9)
            ]
        );
        $vars->set(
            'var2',
            ...[new Token(Token::TYPE_LITERAL, '2', 0, 1, 0)]
        );
        $this->expectException(InvalidVariableException::class);
        $this->expectExceptionMessage(
            'Found circular reference for variable "var3"'
        );
        $vars->set(
            'var3',
            ...[
                new Token(Token::TYPE_VARIABLE, 'var2', 0, 1, 0),
                new Token(Token::TYPE_ARITHMETIC_OPERATOR, '+', 4, 1, 5),
                new Token(Token::TYPE_VARIABLE, 'var1', 5, 1, 9)
            ]
        );
    }

    /**
     * @test
     */
    public function storageObjectsAreIterable()
    {
        $vars = (new Storage())
            ->set('var1', ...[new Token(Token::TYPE_LITERAL, '1', 0, 1, 0)])
            ->set('var2', ...[new Token(Token::TYPE_LITERAL, '2', 0, 1, 0)])
            ->set('var3', ...[new Token(Token::TYPE_LITERAL, '3', 0, 1, 0)]);

        $x = 0;
        foreach ($vars as $key => $value) {
            $this->assertEquals('var' . ++$x, $key);
            $this->assertEquals($x, $value[0]->value);
        }
        $this->assertEquals($x, 3);
    }
}
