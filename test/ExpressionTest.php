<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Expression;

// Exceptions
use \Mathepa\Exception\InvalidExpressionException;
use \Mathepa\Exception\InvalidVariableException;
use \Mathepa\Exception\InvalidFunctionException;

/**
 * Class ExpressionTest
 */
class ExpressionTest extends TestCase
{
    /**
     * If declare(strict_types=1) this test will fail
     * @see http://php.net/manual/en/functions.arguments.php
     *
     * @test
     */
    public function passingANumericValueToTheConstructorShouldNotFail()
    {
        $this->assertEquals(3, (new Expression(2 + 1))->evaluate());
    }

    /**
     * @test
     */
    public function setExpressionWillFailIfNullPassed()
    {
        $this->expectException(\TypeError::class);
        (new Expression())->setExpression(null);
    }

    /**
     * @test
     */
    public function getNonexistentVariableThrowsAnException()
    {
        $this->expectException(InvalidVariableException::class);
        (new Expression())->getVariable('ThereIsNoSuchVariable');
    }

    /**
     * @test
     */
    public function setAWrongVariableNameThrowsAnException()
    {
        $this->expectException(InvalidVariableException::class);
        (new Expression())->setVariable('0var', 2);
    }

    /**
     * @test
     */
    public function evaluateSimpleExpressions()
    {
        $m = new Expression();

        $this->assertSame(1, $m->setExpression('2 + - 1')->evaluate());
        $this->assertSame(2, $m->setExpression('1 + + 1')->evaluate());
        $this->assertSame(3, $m->setExpression('2 + 1')->evaluate());
        $this->assertSame(7, $m->setExpression('2 + 1 * 3 + 2')->evaluate());
        $this->assertSame(11, $m->setExpression('(2 + 1) * 3 + 2')->evaluate());
        $this->assertSame(15, $m->setExpression('(2 + 1) * (3 + 2)')->evaluate());
        $this->assertSame(64, $m->setExpression('8 ** 2')->evaluate());
        $this->assertSame(-32, $m->setExpression('8**2 / -2')->evaluate());
        $this->assertSame(2, $m->setExpression('5 % 3')->evaluate());
        $this->assertSame(-2, $m->setExpression('-5 % 3')->evaluate());
    }

     /**
      * @test
      */
    public function evaluateExpressionsWithFunctions()
    {
        $m = new Expression();

        // Calculate radius
        $this->assertSame(
            6.366197723675814,
            $m->setExpression('40 / (2 * pi())')->evaluate()
        );

        // TODO add more
    }

    /**
     * @test
     */
    public function evaluateExpressionsWithVariables()
    {
        $m = new Expression();

        // Calculate radius
        $m->setVariable('pi', pi());
        $this->assertSame(
            6.366197723675814,
            $m->setExpression('40 / (2 * pi)')->evaluate()
        );

        // TODO add more
    }

    /**
     * @test
     */
    public function evaluateThrowsInvalidVariableException()
    {
        $this->expectException(InvalidVariableException::class);
        $this->expectExceptionMessage('Variable "pi" not set');
        (new Expression())->setExpression('40 / (2 * pi)')->evaluate();
    }

    /**
     * @test
     */
    public function evaluateThrowsInvalidFunctionException()
    {
        $this->expectException(InvalidFunctionException::class);
        $this->expectExceptionMessage('Unknown function name "eval"');
        (new Expression())->setExpression('40 / eval("return 2+1;")')->evaluate();
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionBecauseOfMissingParameter()
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('sin() expects exactly 1 parameter');
        (new Expression())->setExpression('sin()')->evaluate();
    }

    /**
     * @test
     */
    public function evaluateThrowsExceptionBecauseOfWrongNumberOfParameters()
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage('deg2rad() expects exactly 1 parameter');
        (new Expression())->setExpression('cos(deg2rad(90, 180))')->evaluate();
    }
}
