<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidExpressionException;
use Mathepa\Exception\InvalidFunctionException;
use Mathepa\Exception\InvalidLiteralException;
use Mathepa\Exception\SyntaxErrorException;

/**
 * Class Lexer
 */
class Lexer
{
    /**
     * @var string
     */
    const NAME_REGEX = '[A-Za-z]+[A-Za-z0-9]*';

    /**
     * @var string[]
     */
    const FUNCTIONS = [
        'abs', 'acos', 'acosh', 'asin', 'asinh', 'atan2', 'atan', 'atanh',
        'ceil', 'cos', 'cosh', 'exp', 'floor', 'fmod', 'hypot', 'intdiv',
        'log10', 'log', 'pi', 'pow', 'round', 'sin', 'sinh', 'sqrt',
        'tan', 'tanh', 'deg2rad',
    ];

    /**
     * @var string[]
     */
    const LITERALS_REGEXS = [
        '[+-]?\s*((\d+|(\d*\.\d+|\d+\.\d*))[eE][+-]?\d+)', // IEEE 754 format
        '[+-]?\s*(\d*\.\d+|\d+\.\d*)', // float
        '[+-]?\s*\d+', // integer
    ];

    /**
     * Gets vertical position (line/column) of the last character
     *
     * @param string $expression
     * @param int $offset
     * @param int|null $length If NULL will return last character position
     * @throws \UnexpectedValueException If expression parameter is empty
     * @return \stdClass e.g. {'line': 1, 'column': 2}
     */
    public static function getVerticalPosition(
        string $expression,
        int $offset = 0,
        ?int $length = null
    ): \stdClass {
        $line = 1;
        $column = 0;
        $length = $length ?? strlen($expression);
        $expression = substr($expression, $offset, $length);
        $chars = $expression !== '' ? str_split($expression) : [];

        foreach ($chars as $char) {
            if ($char === "\n" || $char === "\r") {
                if ($char === "\n") {
                    $column = 0;
                    $line++;
                }
                continue;
            }
            $column++;
        }

        return (object) ['line' => $line, 'column' => $column];
    }

    /**
     * Finds closing bracket. This function is sensitive to nested pairs.
     *
     * Given following expression: '(2 + 3 / (4 - 1) + 10) + 2'
     * It would return the Token ')' in the position 21.
     *
     * @param string $expression
     * @param int $offset
     * @return \Mathepa\Token|null
     */
    public static function findClosingBracket(
        string $expression,
        int $offset
    ): ?Token {
        $length = strlen($expression);
        if ($length == 0) {
            return null;
        }

        $open = 0;
        $x = $offset;
        $x += $expression[$x] === '(' ? 1 : 0;

        for ($x; $x < $length; $x++) {
            if ($expression[$x] === ')' && $open == 0) {
                $vpos = self::getVerticalPosition($expression, 0, $x + 1);
                return new Token(
                    Token::TYPE_CLOSING_BRAKET,
                    ')',
                    $x,
                    $vpos->line,
                    $vpos->column
                );
            }
            if ($expression[$x] === ')') {
                $open--;
            } elseif ($expression[$x] === '(') {
                $open++;
            }
        }

        return null;
    }

    /**
     * Tries to read a valid literal starting from a given offset
     *
     * @param string $expression
     * @param int $offset
     * @throws \Mathepa\Exception\InvalidLieteralException
     * @return \Mathepa\Token|null NULL value if no literal found
     */
    public static function readLiteralToken(
        string $expression,
        int $offset
    ): ?Token {
        $subject = substr($expression, $offset);
        $matches = [];
        $vpos = self::getVerticalPosition($expression, 0, $offset + 1);

        foreach (self::LITERALS_REGEXS as $regex) {
            if (!preg_match("/^$regex/", $subject, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $literal = $matches[0][0];
            $length = strlen($literal);
            // Detect edge case: malformed literal e.g. 42.79.24
            $next = $subject[$length] ?? null;
            if ($next && !preg_match('/^[\s<>!+\-=\/)%*,]$/', $next)) {
                throw new InvalidLiteralException(
                    sprintf(
                        'Invalid literal: "%s" on line %d, at column %d',
                        $literal . $next,
                        $vpos->line,
                        $vpos->column
                    )
                );
            }
            return new Token(
                Token::TYPE_LITERAL,
                $literal,
                $offset,
                $vpos->line,
                $vpos->column
            );
        }

        return null;
    }

    /**
     * Tries to read a function starting from a given offset
     *
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidFunctionException
     * @throws \Mathepa\Exception\SyntaxErrorException
     * @return \Mathepa\Token|null NULL value if no function found
     */
    public static function readFunctionToken(
        string $expression,
        int $offset
    ): ?Token {
        $subject = substr($expression, $offset);
        $regexp = '/^(' . self::NAME_REGEX . ')(\s*\()/';
        $matches = [];
        $vpos = self::getVerticalPosition($expression, 0, $offset + 1);

        if (!preg_match($regexp, $subject, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $name = $matches[1][0];
        if (!self::findClosingBracket($subject, strlen($matches[0][0]))) {
            throw new SyntaxErrorException(
                sprintf(
                    'Missing bracket after function "%s" on line %d, at column %d',
                    $name,
                    $vpos->line,
                    $vpos->column
                )
            );
        }
        if (!in_array($name, self::FUNCTIONS)) {
            throw new InvalidFunctionException(
                sprintf(
                    'Unknown function name "%s" on line %d, at column %d',
                    $name,
                    $vpos->line,
                    $vpos->column
                )
            );
        }

        return new Token(
            Token::TYPE_FUNCTION,
            $name,
            $offset,
            $vpos->line,
            $vpos->column
        );
    }

    /**
     * Tries to read a variable starting from a given offset
     *
     * @param string $expression
     * @param int $offset
     * @return \Mathepa\Token|null If no variable found returns NULL value
     */
    public static function readVariableToken(
        string $expression,
        int $offset
    ): ?Token {
        $subject = substr($expression, $offset);
        $regexp = '/^(' . self::NAME_REGEX . ')\s*([^(A-Za-z0-9]|$)/';
        $matches = [];
        $vpos = self::getVerticalPosition($expression, 0, $offset + 1);

        if (preg_match($regexp, $subject, $matches, PREG_OFFSET_CAPTURE)) {
            return new Token(
                Token::TYPE_VARIABLE,
                $matches[1][0],
                $offset,
                $vpos->line,
                $vpos->column
            );
        }

        return null;
    }

    /**
     * Determines if the sign before a literal represents an operator, and if
     * so, creates the correspondent Token as arithmetic operator.
     *
     * @param \Mathepa\Token[] $tokens
     * @param \Mathepa\Token $token
     * @throws \UnexpectedValueException
     * @return \Mathepa\Token[]
     */
    public static function splitByOperator(array $tokens, Token $literalToken): array
    {
        if ($literalToken->type !== Token::TYPE_LITERAL) {
            throw new \UnexpectedValueException(
                sprintf('Unexpected token type "%s"', $literalToken->type)
            );
        }
        $sign = $literalToken->value[0];
        $lastType = $tokens[count($tokens) - 1]->type ?? null;
        if (($sign != '-' && $sign != '+') || $lastType === null) {
            // Not signed or this is the first token, ignore
            return [$literalToken];
        }
        $isSignedLiteral = in_array(
            $lastType,
            [
                Token::TYPE_ARITHMETIC_OPERATOR,
                Token::TYPE_OPENING_BRAKET,
                Token::TYPE_OPENING_BRAKET_FUNCTION,
                Token::TYPE_COMMA_FUNCTION,
            ],
            true
        );
        if ($isSignedLiteral) {
            // Don not split, ignore
            return [$literalToken];
        }
        // This isn't a signed literal, new token as operator has to be created
        return [
            new Token(
                Token::TYPE_ARITHMETIC_OPERATOR,
                $literalToken->value[0],
                $literalToken->position,
                $literalToken->line,
                $literalToken->column
            ),
            new Token(
                Token::TYPE_LITERAL,
                substr($literalToken->value, 1),
                $literalToken->position + 1,
                $literalToken->line,
                $literalToken->column + 1
            ),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * Creates tokens from a given expression. This method search for
     * well formed tokens. If wrong tokens or unclassifiable are found,
     * a syntax error exception will be thrown. This method neither makes
     * a "grammar analysis" {@see \Mathepa\Parser::checkGrammar} nor checks
     * relations between tokens.
     *
     * @param string
     * @throws \Mathepa\Exception\SyntaxErrorException
     * @return array
     */
    public static function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $closingGroup = [];

        $pos = 0;
        while ($pos < $length) {
            $char = $expression[$pos];
            if (ctype_space($char)) {
                $pos++;
                continue;
            }

            if ($token = self::readLiteralToken($expression, $pos)) {
                array_push($tokens, ...self::splitByOperator($tokens, $token));
            } elseif ($token = self::readFunctionToken($expression, $pos)) {
                $tokens[] = $token;
            } elseif ($token = self::readVariableToken($expression, $pos)) {
                $tokens[] = $token;
            }

            if ($token) {
                $pos += strlen($token->value);
                continue;
            }

            $vpos = self::getVerticalPosition($expression, 0, $pos + 1);
            $nextChar= $expression[$pos + 1] ?? null;

            switch ($char) {
                case '(':
                    $lastToken = $tokens[count($tokens) - 1];
                    if ($lastToken->type === Token::TYPE_FUNCTION) {
                        $openingType = Token::TYPE_OPENING_BRAKET_FUNCTION;
                        $closingType = Token::TYPE_CLOSING_BRAKET_FUNCTION;
                    } else {
                        $openingType = Token::TYPE_OPENING_BRAKET;
                        $closingType = Token::TYPE_CLOSING_BRAKET;
                    }
                    $token = self::findClosingBracket($expression, $pos);
                    if ($token === null) {
                        throw new SyntaxErrorException(
                            sprintf(
                                'Unclosed bracket "(" on line %d, at column %d',
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    $token->setType($closingType);
                    $closingGroup[$token->position] = $token;
                    $token = new Token(
                        $openingType,
                        '(',
                        $pos,
                        $vpos->line,
                        $vpos->column
                    );
                    break;

                case ')':
                    if (!isset($closingGroup[$pos])) {
                        throw new SyntaxErrorException(
                            sprintf(
                                'Unclosed bracket "(" on line %d, at column %d',
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    $token = $closingGroup[$pos];
                    break;

                case ',':
                    $token = new Token(
                        Token::TYPE_COMMA_FUNCTION,
                        ',',
                        $pos,
                        $vpos->line,
                        $vpos->column
                    );
                    break;

                // Arithmetic operators: '+' '-' '*' '**' '/' '%'
                case '+':
                case '-':
                case '*':
                case '/':
                case '%':
                    $token = new Token(
                        Token::TYPE_ARITHMETIC_OPERATOR,
                        $char,
                        $pos,
                        $vpos->line,
                        $vpos->column
                    );
                    if ($char == '*' && $nextChar == '*') {
                        $token->setValue($char . $nextChar);
                        $pos++;
                    } elseif ($nextChar === $char) {
                        throw new SyntaxErrorException(
                            sprintf(
                                'Invalid operator "%s" on line %d, at column %d',
                                $char . $nextChar,
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    break;
                
                // Comparison operators: '==' '!=' '<>' '<' '>' '<=' '>='
                case '=':
                case '!':
                case '<':
                case '>':
                    $token = new Token(
                        Token::TYPE_COMPARISON_OPERATOR,
                        $char,
                        $pos,
                        $vpos->line,
                        $vpos->column
                    );
                    if ($nextChar == '=') {
                        $token->setValue($char . $nextChar);
                        $pos++;
                    }
                    break;

                default:
                    throw new SyntaxErrorException(
                        sprintf(
                            'Unexpected character "%s" on line %d, at column %d',
                            $char,
                            $vpos->line,
                            $vpos->column
                        )
                    );
            }

            $pos++;

            $tokens[] = $token;
        }

        return $tokens;
    }
}
