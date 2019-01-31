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
    ): \stdClass
    {
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
     * Find a pair of related tokens, such as brackets "(" and ")" and
     * tokens that forms a ternary operator "?" and ":". This function is
     * sensitive to bracket pairs. The first token (aka opening token) is
     * expected to be at the given offset position.
     *
     * For example, given following expression: '(2 + 3 / (4 - 1) + 10) + 2'
     * It would return two Token objects:
     * - Token '(' at postion 0
     * - Token ')' at position 21
     *
     * @param string $openingTokenValue
     * @param string $closingTokenValue
     * @param string $expression
     * @param int $offset
     * @return \Mathepa\Token[]
     */
    public static function findTokenPair(
        string $tokenValue,
        string $tokenPairValue,
        string $expression,
        int $offset
    ): array
    {
        $token = null;
        $tokenPair = null;
        $length = strlen($expression);
        $open = 0;
        $x = $offset;

        if ($expression[$x] === $tokenValue) {
            $vpos = self::getVerticalPosition($expression, 0, $x + 1);
            $token = new Token(
                Token::getTypeByValue($tokenValue),
                $tokenValue,
                $x,
                $vpos->line,
                $vpos->column
            );
            $x++;
        }

        for ($x; $x < $length; $x++) {
            if ($expression[$x] === $tokenPairValue && $open === 0) {
                $vpos = self::getVerticalPosition($expression, 0, $x + 1);
                $tokenPair = new Token(
                    Token::getTypeByValue($tokenPairValue),
                    $tokenPairValue,
                    $x,
                    $vpos->line,
                    $vpos->column
                );
                break;
            }
            if ($expression[$x] === ')') {
                $open--;
            } elseif ($expression[$x] === '(') {
                $open++;
            }
        }

        return [$token, $tokenPair];
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
    ): ?Token
    {
        $subject = substr($expression, $offset);
        $found = false;
        $isSigned = false;
        $vpos = self::getVerticalPosition($expression, 0, $offset + 1);

        foreach (self::LITERALS_REGEXS as $regex) {
            $matches = [];
            if (preg_match("/^$regex/", $subject, $matches, PREG_OFFSET_CAPTURE)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return null;
        }
        $literal = $matches[0][0];
        if ($literal[0] === '-' || $literal[0] === '+') {
            $isSigned = true;
        }
        // Detect edge case: malformed literal e.g. 42.79.24
        $next = $subject[strlen($literal)] ?? null;
        if ($next && !preg_match('/^[\s<>!+\-=\/)%*,]$/', $next)) {
            throw new InvalidLiteralException(
                sprintf(
                    'Invalid literal "%s" line %d, column %d',
                    $literal . $next,
                    $vpos->line,
                    $vpos->column
                )
            );
        }
        return new Token(
            $isSigned ? Token::TYPE_SIGNED_LITERAL : Token::TYPE_LITERAL,
            $literal,
            $offset,
            $vpos->line,
            $vpos->column
        );

        return null;
    }

    /**
     * Tries to read a function token starting from a given offset
     *
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidFunctionException
     * @throws \Mathepa\Exception\SyntaxErrorException
     * @return \Mathepa\Token|null NULL value if no function found
     */
    public static function readFunctionToken(
        string $expression,
        int $offset
    ): ?Token
    {
        $subject = substr($expression, $offset);
        $regexp = '/^(' . self::NAME_REGEX . ')(\s*\()/';
        $matches = [];
        $vpos = self::getVerticalPosition($expression, 0, $offset + 1);

        if (!preg_match($regexp, $subject, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $name = $matches[1][0];

        list(, $token) = self::findTokenPair('(', ')', $subject, strlen($matches[0][0]));
        if ($token === null) {
            throw new SyntaxErrorException(
                sprintf(
                    'Missing bracket after function "%s" line %d, column %d',
                    $name,
                    $vpos->line,
                    $vpos->column
                )
            );
        }

        if (!in_array($name, self::FUNCTIONS)) {
            throw new InvalidFunctionException(
                sprintf(
                    'Unknown function name "%s" line %d, column %d',
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
     * Tries to read a variable token starting from a given offset
     *
     * @param string $expression
     * @param int $offset
     * @return \Mathepa\Token|null If no variable found returns NULL value
     */
    public static function readVariableToken(
        string $expression,
        int $offset
    ): ?Token
    {
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
     * Creates tokens from a given expression. This method search for
     * well formed tokens and if unclassifiable tokens are found, a syntax
     * error exception will be thrown.
     *
     * This method neither makes a "grammar analysis" nor checks relations
     * between tokens, except relations between paired tokens like, for
     * instance brackets (open <=> close) or ternary operators (? then <=> :).
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param string
     * @throws \Mathepa\Exception\SyntaxErrorException
     * @return array
     */
    public static function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $tokensAhead = [];

        $pos = 0;
        while ($pos < $length) {
            $char = $expression[$pos];
            if (ctype_space($char)) {
                $pos++;
                continue;
            }

            if ($token = self::readLiteralToken($expression, $pos)) {
                $tokens[] = $token;
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
                    $lastToken = end($tokens);
                    if ($lastToken && $lastToken->type === Token::TYPE_FUNCTION) {
                        $openingType = Token::TYPE_OPENING_BRAKET_FUNCTION;
                        $closingType = Token::TYPE_CLOSING_BRAKET_FUNCTION;
                    } else {
                        $openingType = Token::TYPE_OPENING_BRAKET;
                        $closingType = Token::TYPE_CLOSING_BRAKET;
                    }
                    list($token, $tokenPair) = self::findTokenPair(
                        '(',
                        ')',
                        $expression,
                        $pos
                    );
                    if ($tokenPair === null) {
                        throw new SyntaxErrorException(
                            sprintf(
                                'Unclosed bracket "(" line %d, column %d',
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    $token->setType($openingType);
                    $tokenPair->setType($closingType);
                    $tokensAhead[$tokenPair->position] = $tokenPair;
                    break;

                case '?':
                    list($token, $tokenPair) = self::findTokenPair(
                        '?',
                        ':',
                        $expression,
                        $pos
                    );
                    if ($tokenPair === null) {
                        throw new SyntaxErrorException(
                            sprintf(
                                'Unclosed bracket "(" line %d, column %d',
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    $tokensAhead[$tokenPair->position] = $tokenPair;
                    break;

                case ')':
                case ':':
                    if (!isset($tokensAhead[$pos])) {
                        throw new \LogicException(
                            sprintf(
                                'Missing token "%s" line %d, column %d',
                                $char,
                                $vpos->line,
                                $vpos->column
                            )
                        );
                    }
                    $token = $tokensAhead[$pos];
                    break;

                case ',':
                    $token = new Token(
                        Token::TYPE_COMMA_FUNCTION,
                        $char,
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
                                'Invalid operator "%s" line %d, column %d',
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
                            'Unexpected character "%s" line %d, column %d',
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
