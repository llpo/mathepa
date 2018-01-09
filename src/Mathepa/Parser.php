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
     * @param \Mathepa\Token[] $variables
     * @param \Mathepa\Token[] $tokens
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return string|null
     */
    public static function parse(array $variables, Token ...$tokens): ?string
    {
        $expression = [];

        foreach ($tokens as $token) {
            if ($token->type === Token::TYPE_VARIABLE) {
                $name = $token->value;
                if (!isset($variables[$name])) {
                    throw new InvalidVariableException(
                        'Variable "' . $name . '" not found, exception ' .
                        'maybe caused by a circular reference'
                    );
                }
                $expression[] = self::parse(
                    array_filter(
                        $variables,
                        function ($token) use ($name) {
                            return $token->value !== $name;
                        }
                    ),
                    ...$variables[$name]
                );
            } else {
                $expression[] = $token->value;
            }
        }

        if (empty($expression)) {
            throw new InvalidExpressionException('No expression parsed');
        }

        return implode(' ', $expression);
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
                $rules = FIRST_TOKEN_RULES;
            } else {
                $rules = BOUND_TOKEN_RULES[$previousToken->type] ?? null;
                if ($rules === null) {
                    throw new \UnexpectedValueExpcetion(
                        sprintf('Unexpected token type "%s"', $previousToken->type)
                    );
                }
            }
            if (!in_array($token->type, $rules, true)) {
                $errors[] = sprintf(
                    'Unexpected token "%s": line %d, column %d',
                    $token->value,
                    $token->line,
                    $token->column
                );
            }
            if ($pos === $last && !in_array($token->type, LAST_TOKEN_RULES, true)) {
                $errors[] = sprintf(
                    'Unexpected token "%s": line %d, column %d',
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
                    'Unexpected token "%s": line %d, column %d. ' .
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
