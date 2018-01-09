<?php

namespace Mathepa\Test;

// Extends
use PHPUnit\Framework\TestCase;

// Uses
use Mathepa\Lexer;
use Mathepa\Token;

// Exceptions
use Mathepa\Exception\SyntaxErrorException;
use Mathepa\Exception\InvalidFunctionException;
use Mathepa\Exception\InvalidLiteralException;

/**
 * Class LexerTest
 */
class LexerTest extends TestCase
{
    /**
     * @test
     */
    public function getLineAndColumn()
    {
        $vpos = Lexer::getVerticalPosition('');
        $this->assertSame(1, $vpos->line);
        $this->assertSame(0, $vpos->column);

        $vpos = Lexer::getVerticalPosition("Line1\r\nLine2");
        $this->assertSame(2, $vpos->line);
        $this->assertSame(5, $vpos->column);

        $vpos = Lexer::getVerticalPosition("1\r\n\n3");
        $this->assertSame(3, $vpos->line);
        $this->assertSame(1, $vpos->column);

        $vpos = Lexer::getVerticalPosition("\nLine\nLine\nLine");
        $this->assertSame(4, $vpos->line);
        $this->assertSame(4, $vpos->column);

        $vpos = Lexer::getVerticalPosition('(
                (8 - (2**2))
                / floor(4.2) - 2)');
        $this->assertSame(3, $vpos->line);
        $this->assertSame(33, $vpos->column);
    }

    /**
     * @test
     */
    public function findTokenPairs()
    {
        // Brackets

        list($t1, $t2) = Lexer::findTokenPair('(', ')', ')', 0);
        $this->assertNull($t1);
        $this->assertSame(0, $t2->position);
        $this->assertSame(')', $t2->value);
        $this->assertSame(Token::TYPE_CLOSING_BRAKET, $t2->type);

        list($t1, $t2) = Lexer::findTokenPair('(', ')', '2 + 2)', 0);
        $this->assertNull($t1);
        $this->assertSame(5, $t2->position);
        $this->assertSame(Token::TYPE_CLOSING_BRAKET, $t2->type);

        list($t1, $t2) = Lexer::findTokenPair('(', ')', '2 + (2 * 5) - 2)', 0);
        $this->assertNull($t1);
        $this->assertSame(15, $t2->position);

        list($t1, $t2) = Lexer::findTokenPair('(', ')', '((2 + (2 * (5) - 2) + 9))', 0);
        $this->assertSame(0, $t1->position);
        $this->assertSame(Token::TYPE_OPENING_BRAKET, $t1->type);
        $this->assertSame(24, $t2->position);
        $this->assertSame(Token::TYPE_CLOSING_BRAKET, $t2->type);

        list($t1, $t2) = Lexer::findTokenPair('(', ')', '(2 + 1', 0);
        $this->assertSame(0, $t1->position);
        $this->assertNull($t2);

        $this->assertSame([null, null], Lexer::findTokenPair('(', ')', '7 + 3', 0));

        // Ternary operator

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '1 : 2', 0);
        $this->assertNull($t1);
        $this->assertSame(2, $t2->position);

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '(2 > 1 ?) 1 : 2', 7);
        $this->assertSame(7, $t1->position);
        $this->assertNull($t2);

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '? (2 : 1)', 0);
        $this->assertSame(0, $t1->position);
        $this->assertNull($t2);

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '? : 1', 0);
        $this->assertSame(0, $t1->position);
        $this->assertSame(Token::TYPE_TERNARY_OPERATOR_THEN, $t1->type);
        $this->assertSame(2, $t2->position);
        $this->assertSame(Token::TYPE_TERNARY_OPERATOR_ELSE, $t2->type);

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '3 % 3 ? 3 : 1', 6);
        $this->assertSame(6, $t1->position);
        $this->assertSame(10, $t2->position);

        list($t1, $t2) = Lexer::findTokenPair('?', ':', '? (2 + (10 / 5)) : 0', 0);
        $this->assertSame(0, $t1->position);
        $this->assertSame(17, $t2->position);
    }

    /**
     * @test
     */
    public function readLiteralTokens()
    {
        $tok = Lexer::readLiteralToken('2 + 1', 0);
        $this->assertSame(0, $tok->position);
        $this->assertEquals(2, $tok->value);

        $tok = Lexer::readLiteralToken('2.46 + 1', 0);
        $this->assertSame('2.46', $tok->value);

        $tok = Lexer::readLiteralToken('-.46', 0);
        $this->assertSame('-.46', $tok->value);

        $tok = Lexer::readLiteralToken('-10.99 + 1', 0);
        $this->assertSame('-10.99', $tok->value);

        $tok = Lexer::readLiteralToken('1.92E+30 * 2', 0);
        $this->assertSame('1.92E+30', $tok->value);

        // No literal to be found
        $this->assertSame(null, Lexer::readLiteralToken('e2 + 1', 0));

        $this->expectException(InvalidLiteralException::class);
        Lexer::readLiteralToken('2e + 1', 0);
    }

    /**
     * @test
     */
    public function readFunctionTokens()
    {
        $tok = Lexer::readFunctionToken('abs(2 + 3)', 0);
        $this->assertSame(0, $tok->position);
        $this->assertSame('abs', $tok->value);

        $tok = Lexer::readFunctionToken('17.24 + abs(2 + 3)', 8);
        $this->assertSame(8, $tok->position);
        $this->assertSame('abs', $tok->value);

        $tok = Lexer::readFunctionToken('abs   (2 + 3)', 0);
        $this->assertSame(0, $tok->position);
        $this->assertSame('abs', $tok->value);

        // No valid function to be found
        $this->assertSame(null, Lexer::readFunctionToken('abs 2 + 3)', 0));
        $this->assertSame(null, Lexer::readFunctionToken('0abs(2 + 3)', 0));

        // Right syntax, but unrecognized function name
        $this->expectException(InvalidFunctionException::class);
        Lexer::readFunctionToken('zzz(2 + 3)', 0);
    }

    /**
     * @test
     */
    public function readVariableTokens()
    {
        $tok = Lexer::readVariableToken('24 + abs', 5);
        $this->assertSame(5, $tok->position);
        $this->assertSame('abs', $tok->value);

        $tok = Lexer::readVariableToken('abs + 3)', 0);
        $this->assertSame(0, $tok->position);
        $this->assertSame('abs', $tok->value);

        $tok = Lexer::readVariableToken('abs ', 0);
        $this->assertSame(0, $tok->position);
        $this->assertSame('abs', $tok->value);

        // No variable to be found
        $this->assertSame(null, Lexer::readVariableToken('abs(3)', 0));
        $this->assertSame(null, Lexer::readVariableToken('0abs', 0));
    }

    /**
     * @test
     */
    public function tokenizeExpressions()
    {
        $this->assertCount(2, Lexer::tokenize('4.21 711'));
        $this->assertCount(3, Lexer::tokenize('2 +1'));
        $this->assertCount(3, Lexer::tokenize('8**2'));
        $this->assertCount(3, Lexer::tokenize('8 ** 2'));
        $this->assertCount(3, Lexer::tokenize('5 %3'));
        $this->assertCount(3, Lexer::tokenize('-5 % 3'));
        $this->assertCount(7, Lexer::tokenize('2 + 1 * 3 + 2'));
        $this->assertCount(9, Lexer::tokenize('(2+1) *3 + 2'));
        $this->assertCount(11, Lexer::tokenize('(4 + 1) * (3 + 2)'));
        $this->assertCount(11, Lexer::tokenize('(2+1 ) * (3+2)'));

        // --

        $tokens = Lexer::tokenize('-19.88 +76E+30');
        $this->assertCount(3, $tokens);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[0]->type);
        $this->assertSame('-19.88', $tokens[0]->value);

        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[2]->type);
        $this->assertSame('76E+30', $tokens[2]->value);

        // --

        $tokens = Lexer::tokenize('-12.33 +28+53');
        $this->assertCount(5, $tokens);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[0]->type);
        $this->assertSame('-12.33', $tokens[0]->value);

        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[2]->type);
        $this->assertSame('28', $tokens[2]->value);

        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[3]->type);
        $this->assertSame('+', $tokens[3]->value);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[4]->type);
        $this->assertSame('53', $tokens[4]->value);

        // --

        $tokens = Lexer::tokenize('2+ +2');
        $this->assertCount(3, $tokens);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[0]->type);
        $this->assertSame('2', $tokens[0]->value);

        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[2]->type);
        $this->assertSame('+2', $tokens[2]->value);

        // --

        $tokens = Lexer::tokenize('3 + + 3');
        $this->assertCount(3, $tokens);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[0]->type);
        $this->assertSame('3', $tokens[0]->value);

        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);

        $this->assertSame(Token::TYPE_LITERAL, $tokens[2]->type);
        $this->assertSame('+ 3', $tokens[2]->value);

        // --

        $tokens = Lexer::tokenize('2 + - 1');
        $this->assertCount(3, $tokens);
        $this->assertSame(Token::TYPE_LITERAL, $tokens[0]->type);
        $this->assertSame('2', $tokens[0]->value);
        $this->assertSame(Token::TYPE_ARITHMETIC_OPERATOR, $tokens[1]->type);
        $this->assertSame('+', $tokens[1]->value);
        $this->assertSame(Token::TYPE_LITERAL, $tokens[2]->type);
        $this->assertSame('- 1', $tokens[2]->value);
    }

    /**
     * @test
     */
    public function recognizeMalformedDecimalWithMoreThanOneDot()
    {
        $this->expectException(InvalidLiteralException::class);
        Lexer::tokenize('4.217.3 + 1');
    }

    /**
     * @test
     */
    public function recognizeWrongOperator()
    {
        $this->expectException(SyntaxErrorException::class);
        Lexer::tokenize('4++1');
    }
}
