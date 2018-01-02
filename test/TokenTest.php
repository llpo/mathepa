<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Token;

/**
 * Class TokenTest
 */
class TokenTest extends TestCase
{
    /**
     * @test
     */
    public function exceptionThrownWhenInvalidTokenTypePassed()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown type "200"');
        new Token(200, ')', 0, 1, 0);
    }

    /**
     * @test
     */
    public function exceptionThrownWhenInvalidTokenValuePassed()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected empty string as token value');
        new Token(Token::TYPE_OPENING_BRAKET, ' ', 0, 1, 0);
    }

    /**
     * @test
     */
    public function exceptionThrownWhenInvalidTokenPositionPassed()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong position value "-1"');
        new Token(Token::TYPE_CLOSING_BRAKET, ')', -1, 1, 0);
    }

    /**
     * @test
     */
    public function exceptionThrownWhenInvalidTokenLinePassed()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong line value "0"');
        new Token(Token::TYPE_CLOSING_BRAKET, ')', 0, 0, 0);
    }

    /**
     * @test
     */
    public function exceptionThrownWhenInvalidTokenColumnPassed()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong column value "-1"');
        new Token(Token::TYPE_CLOSING_BRAKET, ')', 0, 1, -1);
    }

    /**
     * @test
     */
    public function exceptionThrownWhenPropertyDoesNotExist()
    {
        $this->expectException(\DomainException::class);
        (new Token(Token::TYPE_OPENING_BRAKET, '(', 0, 1, 0))->doesNotExist;
    }
}
