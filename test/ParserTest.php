<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Lexer;
use Mathepa\Parser;
use Mathepa\Storage;

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
            'Unexpected token "2" in line 1, column 3',
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
            'Unexpected token "pi" in line 1, column 3',
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
            'Unexpected token "3" in line 1, column 5',
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
            'Unexpected token "var2" in line 1, column 6',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorBecauseOfCommaFollowedByALogicalOperator()
    {
        $tokens = Lexer::tokenize(', >=');
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "," in line 1, column 1',
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
            'Unexpected token "var1" in line 1, column 6',
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
            'Unexpected token "," in line 1, column 2. ' .
                'This token only allowed inside function brackets.',
            $errors[0]
        );
    }

    /**
     * @group wrong-syntax
     * @test
     */
    public function syntaxErrorInMultilineExpression()
    {
        $tokens = Lexer::tokenize(
            '(
                (16 - (2**2))
                / abs(-4) 2
            )'
        );
        $errors = Parser::checkGrammar(...$tokens);
        $this->assertEquals(
            'Unexpected token "2" in line 3, column 27',
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
            'Unexpected token ")" in line 1, column 10',
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

    /**
     * @test
     */
    public function circularReferenceThrowsAnException()
    {
        $variables = new Storage();
        $variables->set('var1', ...Lexer::tokenize('var1 + var2'));

        $expression = Lexer::tokenize('var1');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp(
            '/circular reference/'
        );

        Parser::parse($variables->toArray(), ...$expression);
    }

    /**
     * @test
     */
    public function testMaximumNestingReferences()
    {
        $variables = new Storage();
        $variables->set('var1', ...Lexer::tokenize('var2'))
            ->set('var2', ...Lexer::tokenize('var3'))
            ->set('var3', ...Lexer::tokenize('var4'))
            ->set('var4', ...Lexer::tokenize('var5'))
            ->set('var5', ...Lexer::tokenize('var6'))
            ->set('var6', ...Lexer::tokenize('var7'))
            ->set('var7', ...Lexer::tokenize('1'));

        $expression = Lexer::tokenize('var1');

        $result = Parser::parse($variables->toArray(), ...$expression);
        $this->assertEquals("1", $result);

        $variables->del('var7');
        for ($x = 7; $x <= Parser::MAX_RECURSIVITY; $x++) {
            $next = $x + 1;
            $variables->set("var$x", ...Lexer::tokenize("var$next"));
        }
        // Exeed Parser::MAX_RECURSIVITY + 1
        $variables->set("var$next", ...Lexer::tokenize("1"));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp(
            '/circular reference/'
        );

        Parser::parse($variables->toArray(), ...$expression);
    }
}
