<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidExpressionException;
use Mathepa\Exception\InvalidVariableException;
use Mathepa\Exception\SyntaxErrorException;

/**
 * Class Parser
 */
class Parser
{
    /**
     * @const integer
     */
    const MAX_RECURSIVITY = 50;

    /**
     * Just a wrapper for a recursive call
     *
     * @param \Mathepa\Token[] $variables
     * @param \Mathepa\Token[] $tokens
     * @throws \UnexpectedValueException
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return string|null
     */
    public static function parse(array $variables, Token ...$tokens): ?string
    {
        if (!empty($variables)) {
            if (array_keys($variables) === range(0, count($variables) - 1)) {
                throw new \UnexpectedValueException(
                    'Argument "variables" expected to be an associative array'
                );
            }
        }

        $counter = 0;

        return self::parseExpression($variables, $tokens, $counter);
    }

    /**
     * Parse an expression and convert it to a valid mathematical
     * expression ready to be interpreted in PHP
     *
     * @param \Mathepa\Token[] $variables
     * @param \Mathepa\Token[] $tokens
     * @param int $counter Counter to control recursivity level
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return string|null
     */
    protected static function parseExpression(
        array $variables,
        array $tokens,
        int $counter
    ): ?string
    {
        $expression = [];

        if ($counter++ > self::MAX_RECURSIVITY) {
            throw new \RuntimeException(
                sprintf(
                    'Maximum recursivity / nesting level "%s" surpassed, ' .
                    'maybe there is a circular reference',
                    self::MAX_RECURSIVITY
                )
            );
        }

        foreach ($tokens as $token) {
            if ($token->type === Token::TYPE_VARIABLE) {
                $name = $token->value;
                if (!isset($variables[$name])) {
                    throw new InvalidVariableException(
                        sprintf('Variable "%s" not set', $name)
                    );
                }
                $expression[] = self::parseExpression(
                    $variables,
                    $variables[$name],
                    $counter
                );
            } else {
                $expression[] = $token->value;
            }
        }

        if (empty($expression)) {
            throw new InvalidExpressionException('No expression parsed');
        }

        $output = implode(' ', $expression);

        return $output;
    }

    /**
     * Checks the grammar of a mathematical expression.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param \Mathepa\Token[]
     * @return string[]
     */
    public static function checkGrammar(Token ...$tokens): array
    {
        $errors = [];
        $previousToken = null;
        $bracketFunctionScope = [];
        $scopePointer = -1;
        $last = func_num_args() - 1;

        for ($pos = 0; $pos <= $last; $pos++) {
            $token = $tokens[$pos];
            if ($pos === 0) {
                if ($token->value === '+' || $token->value === '-') {
                    // TODO review:
                    // Given the case '+ -2 -9...' the first token '+'
                    // is considered here as arithmetic operator, and
                    // followed by a signed literal '-2'. But maybe it would
                    // better consider a different classification, here
                    // are the option (* current):
                    // 1*) Operator '+' followed by signed literal '-2'
                    // 2) create a single signed literal '+ -2' instead two
                    // 3) consider it as literal (with zero value) '+'
                    // This is just a workarounds, the current option
                    // does not feel grammatically right.
                    $previousToken = $token;
                    continue;
                }
                $rules = FIRST_TOKEN_RULES;
            } else {
                $rules = BOUND_TOKEN_RULES[$previousToken->type] ?? null;
                if ($rules === null) {
                    throw new \UnexpectedValueException(
                        sprintf('Unexpected token type "%s"', $previousToken->type)
                    );
                }
            }
            if (!in_array($token->type, $rules)) {
                $errors[] = sprintf(
                    'Unexpected token "%s" in line %d, column %d',
                    $token->value,
                    $token->line,
                    $token->column
                );
            }
            if ($pos === $last && !in_array($token->type, LAST_TOKEN_RULES)) {
                $errors[] = sprintf(
                    'Unexpected token "%s" in line %d, column %d',
                    $token->value,
                    $token->line,
                    $token->column
                );
            }
            $previousToken = $token;

            // Check special case: Token::TYPE_COMMA_FUNCTION can be used
            // only inside function brackets.
            switch ($token->type) {
                case Token::TYPE_OPENING_BRAKET:
                    $bracketFunctionScope[++$scopePointer] = false;
                    break;
                case Token::TYPE_OPENING_BRAKET_FUNCTION:
                    $bracketFunctionScope[++$scopePointer] = true;
                    break;
                case Token::TYPE_CLOSING_BRAKET:
                case Token::TYPE_CLOSING_BRAKET_FUNCTION:
                    unset($bracketFunctionScope[$scopePointer--]);
                    break;
            }
            if ($token->type === Token::TYPE_COMMA_FUNCTION &&
                $bracketFunctionScope[$scopePointer] !== true
            ) {
                $errors[] = sprintf(
                    'Unexpected token "%s" in line %d, column %d. ' .
                        'This token only allowed inside function brackets.',
                    $token->value,
                    $token->line,
                    $token->column
                );
            }
        }

        return $errors;
    }
}
