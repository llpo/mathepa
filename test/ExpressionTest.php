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
    public function evaluateExpressionsWithFunctions()
    {
        $m = new Expression();

        $this->assertSame(
            6.366197723675814,
            $m->setExpression('40 / (2 * pi())')->evaluate()
        );
    }

    /**
     * @test
     */
    public function evaluateExpressionsWithVariables()
    {
        $m = new Expression();

        $m->setVariable('pi', pi());
        $this->assertSame(
            6.366197723675814,
            $m->setExpression('40 / (2 * pi)')->evaluate()
        );
    }

    /**
     * @test
     */
    public function evaluateExpressionsWithTernaryOperators()
    {
        $m = new Expression();
        $m->setVariable('price', 2.40);
        $m->setVariable('vat', 1.19);

        $discount = '(units > 100 ? (units > 500 ? 0.20 : 0.10) : 0)';
        $m->setExpression($discount);
        $this->assertSame(0, $m->setVariable('units', 75)->evaluate());
        $this->assertSame(0.10, $m->setVariable('units', 125)->evaluate());
        $this->assertSame(0.20, $m->setVariable('units', 525)->evaluate());

        $m->setVariable('discount', $discount);
        $m->setExpression('round((price - (price * discount)) * vat,  2) * units');
        $this->assertSame(214.50, $m->setVariable('units', 75)->evaluate());
        $this->assertSame(321.25, $m->setVariable('units', 125)->evaluate());
        $this->assertSame(1197.00, $m->setVariable('units', 525)->evaluate());
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
     *  Data provider
     */
    public function wrongExpressions()
    {
        // Expression | Expected error message
        return [
            ['40 / eval("return 2+1;")', 'Unknown function name "eval"'],
            ['sin()', 'sin() expects exactly 1 parameter'],
            ['cos(deg2rad(90, 180))', 'deg2rad() expects exactly 1 parameter'],
            ['9 > 8 ? 1 :', 'Unexpected token ":": line 1, column 11'],
            ['+ -2 - 9', 'Unexpected token "+": line 1, column 1'],
        ];
    }

    /**
     * @dataProvider wrongExpressions
     * @test
     */
    public function evaluateThrowsInvalidExpressionException($expression, $message)
    {
        $this->expectException(InvalidExpressionException::class);
        $this->expectExceptionMessage($message);
        (new Expression())->setExpression($expression)->evaluate();
    }

    /**
     *  Data provider
     */
    public function rightExpressions()
    {
        // Expression | Variables | Expected result
        return [
            ['2 + - 1', [], 1],
            ['1 + + 1', [], 2],
            ['2 + 1 * 3 + 2', [], 7],
            ['5 + 4', [], 9],
            ['(2 + 1) * 3 + 2', [], 11],
            ['(2 + 1) * (3 + 2)', [], 15],
            ['8 ** 2', [], 64],
            ['8**2 / -2', [], -32],
            ['5 % 3', [], 2],
            ['-5 % 3', [], -2],
            ['9 > 8 ? 3 : 4', [], 3],
        ];
    }

    /**
     * @dataProvider rightExpressions
     * @test
     */
    public function evaluateReturnsTheExpectedResult($expression, $variables, $result)
    {
        $m = new Expression($expression);
        foreach ($variables as $name => $value) {
            $m->setVariable($name, $value);
        }
        $this->assertEquals($result, $m->evaluate());
    }
}
