<?php

namespace Mathepa;

/**
 * @var array
 *
 * This array lay down general rules to check syntax errors. The array
 * schema has following meaning:
 * <key>: token type
 * <values>: expected token types that are allowed to follow the <key> token
 *
 * Notice that some exceptions or concrete cases muss be coded elsewhere
 * e.g. TYPE_LITERAL + TYPE_COMMA_FUNCTION: that combination should
 * only exist inside function brackets.
 * @see \Mathepa\Parser::checkGrammar
 */
const RULES = [
    null => [
        Token::TYPE_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_LITERAL => [
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_COMMA_FUNCTION,
    ],
    Token::TYPE_OPENING_BRAKET => [
        Token::TYPE_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_CLOSING_BRAKET => [
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
    ],
    Token::TYPE_ARITHMETIC_OPERATOR => [
        Token::TYPE_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_COMPARISON_OPERATOR => [
        Token::TYPE_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_FUNCTION => [
        Token::TYPE_OPENING_BRAKET_FUNCTION,
    ],
    Token::TYPE_OPENING_BRAKET_FUNCTION => [
        Token::TYPE_LITERAL,
        Token::TYPE_OPENING_BRAKET,
        Token::TYPE_FUNCTION,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_CLOSING_BRAKET_FUNCTION => [
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
    ],
    Token::TYPE_COMMA_FUNCTION => [
        Token::TYPE_LITERAL,
        Token::TYPE_FUNCTION,
        Token::TYPE_VARIABLE,
    ],
    Token::TYPE_VARIABLE => [
        Token::TYPE_ARITHMETIC_OPERATOR,
        Token::TYPE_COMPARISON_OPERATOR,
        Token::TYPE_CLOSING_BRAKET,
        Token::TYPE_CLOSING_BRAKET_FUNCTION,
        Token::TYPE_COMMA_FUNCTION,
    ],
];
