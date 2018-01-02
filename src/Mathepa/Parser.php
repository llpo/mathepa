<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidExpressionException;
use Mathepa\Exception\SyntaxErrorException;

/**
 * Class Parser
 */
class Parser
{
    /**
     * @param \Mathepa\Storage $variables
     * @param \Mathepa\Token[]
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return string|null
     */
    public static function parse(Storage $variables, Token ...$tokens): ?string
    {
        $expression = [];

        foreach ($tokens as $token) {
            if ($token->type === Token::TYPE_VARIABLE) {
                $expression[] = $variables->get($token->value);
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
     * Checks the grammar of a mathematic expression. The main rules are
     * defined in {@see \Mathepa\RULES}. This method check some special cases,
     * that are not expressible as general rule. Check out the method
     * for more details.
     *
     * @param \Mathepa\Token[]
     * @return string[]
     */
    public static function checkGrammar(Token ...$tokens): array
    {
        $errors = [];
        $previousToken = null;
        $braketFunctionScope = [];
        $contextPointer = -1;

        foreach ($tokens as $token) {
            $key = $previousToken->type ?? null;
            if (!isset(RULES[$key])) {
                throw new \UnexpectedValueExpcetion(
                    sprintf('Unexpected token type "%s"', $previousToken->type)
                );
            }
            if (!in_array($token->type, RULES[$key], true)) {
                $errors[] = sprintf(
                    'Unexpected token "%s" on line %d, at column %d',
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
                    'Unexpected token "%s" on line %d, at column %d. ' .
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
