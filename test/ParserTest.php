<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Lexer;
use Mathepa\Parser;

// Exceptions
use Mathepa\Exception\SyntaxErrorException;

/**
 * Class ParserTest
 */
class ParserTest extends TestCase
{
    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfLiteralFollowedByALiteral()
    {
        $tokens = Lexer::tokenize('3 2');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "2" on line 1, at column 3',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfLiteralFollowedByAFunction()
    {
        $tokens = Lexer::tokenize('3 pi()');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "pi" on line 1, at column 3',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfVariableFollowedByALiteral()
    {
        $tokens = Lexer::tokenize('var 3');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "3" on line 1, at column 5',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfVariableFollowedByAVariable()
    {
        $tokens = Lexer::tokenize('var1 var2');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "var2" on line 1, at column 6',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfCommaFollowedByALogicalOperator()
    {
        $tokens = Lexer::tokenize(', =>');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "," on line 1, at column 1',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfFunctionFollowedByAVariable()
    {
        $tokens = Lexer::tokenize('pi() var1');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "var1" on line 1, at column 6',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfUseOfCommasOutsideFunctionBrackets()
    {
        $tokens = Lexer::tokenize('3, var1');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "," on line 1, at column 2. ' .
                'This token only allowed inside function brackets.',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorMultilineExpression()
    {
        $tokens = Lexer::tokenize(
            '(
                (16 - (2**2))
                / abs(-4) 2
            )'
        );
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "2" on line 3, at column 27',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfWrongSyntaxPassingFunctionParameters()
    {
        $tokens = Lexer::tokenize('pow(2, 8,) + 128');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token ")" on line 1, at column 10',
            $errors[0]
        );
    }

    /**
     * @group right-syntax
     * @test
     */
    public function rightSyntaxBecauseOfUseOfCommasInsideFunctionBrackets()
    {
        $tokens = Lexer::tokenize('pow(2, 8) + 128');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEmpty($errors);
    }
}
