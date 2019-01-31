<?php

namespace Mathepa;

/**
 * Definition of rules to check for the first token in a expression:
 * [»Token« Token Token Token]
 *
 * @var array
 */
const FIRST_TOKEN_RULES = [
    Token::TYPE_LITERAL,
    Token::TYPE_SIGNED_LITERAL,
    Token::TYPE_OPENING_BRAKET,
    Token::TYPE_FUNCTION,
    Token::TYPE_VARIABLE,
];

/**
 * Definition of rules to check for the last token in a expression:
 * [Token Token Token »Token«]
 *
 * @var array
 */
const LAST_TOKEN_RULES = [
    Token::TYPE_LITERAL,
    Token::TYPE_SIGNED_LITERAL,
    Token::TYPE_CLOSING_BRAKET,
    Token::TYPE_CLOSING_BRAKET_FUNCTION,
    Token::TYPE_VARIABLE,
];

/**
 * Definition of general rules to check syntax errors for tokens that are
 * preceded by other tokens.
 * [Token »Token« ... »Token«]
 *
 * The array has following schema:
 * <key>: Token type of the previous token
 * <values>: Token types that are allowed to follow the previous token (<key>)
 *
 * Notice that some complex cases must be elsewhere expressed and checked.
 * This arrays is not intended to cover such complex cases, they too
 * difficult to express in a declarative way:
 * E.g. TYPE_LITERAL + TYPE_COMMA_FUNCTION is allowed only inside function
 * brackets. Such case is checked in {@see \Mathepa\Parser::checkGrammar}
 *
 * @var array
 */
const BOUND_TOKEN_RULES = [
    Token::TYPE_LITERAL => [
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_COMMA_FUNCTION,
        Token::TYPE_TERNARY_OPERATOR_THEN,
        Token::TYPE_TERNARY_OPERATOR_ELSE,
    ],
    Token::TYPE_SIGNED_LITERAL => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_COMMA_FUNCTION,
        Token::TYPE_TERNARY_OPERATOR_THEN,
        Token::TYPE_TERNARY_OPERATOR_ELSE,
    ],
    Token::TYPE_OPENING_BRAKET => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_CLOSING_BRAKET => [
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_TERNARY_OPERATOR_THEN,
        Token::TYPE_TERNARY_OPERATOR_ELSE,
    ],
    Token::TYPE_ARITHMETIC_OPERATOR => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_COMPARISON_OPERATOR => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_FUNCTION => [
        Token::TYPE_OPENING_BRAKET_FUNCTION,
    ],
    Token::TYPE_OPENING_BRAKET_FUNCTION => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_CLOSING_BRAKET_FUNCTION => [
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_TERNARY_OPERATOR_THEN,
        Token::TYPE_TERNARY_OPERATOR_ELSE,
    ],
    Token::TYPE_COMMA_FUNCTION => [
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_VARIABLE => [
        Token::TYPE_SIGNED_LITERAL,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_COMMA_FUNCTION,
        Token::TYPE_TERNARY_OPERATOR_THEN,
        Token::TYPE_TERNARY_OPERATOR_ELSE,
    ],
    Token::TYPE_TERNARY_OPERATOR_THEN => [
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
    ],
    Token::TYPE_TERNARY_OPERATOR_ELSE => [
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
        Token::TYPE_LITERAL,
        Token::TYPE_SIGNED_LITERAL,
    ],
];
