
# Mathepa

Mathepa is a parser for mathematical expressions in PHP syntax.

## Why?

The idea arises from the need to save mathematical formulas into a database.
Furthermore, it was required to compute those formulas in the back-end, to
do that, the use of _[eval][1]_ is necessary. Mathepa acts as a proxy
preventing a direct use of _[eval][1]_.

Mathepa should be used in both directions, before saving data or processing any
input, as validator, and after fetching data as evaluator.

## Quick feature list

- Express mathematical expression using PHP syntax
- Use of variables to parametrize values
- Use of ternary operator
- Almost all [math functions](src/Mathepa/Lexer.php) are white listed
- Variables can contain complex expressions as well (nesting)
- _[eval][1]_ function will be called only with valid expressions
- No external dependencies required

[1]: http://php.net/eval

## How it works?

As already mentioned, Mathepa uses the _eval_ PHP function to evaluate
previously validated and, thus, secure expressions, so that _eval_ only will
evaluate an expression when syntax and grammar are valid. Here a list of
requirement to fulfill before an expression is considered as valid:

- Syntax is valid, i.e., all tokens are identified, e.g., "3.1.4" is invalid
- Grammar is valid, i.e., after a [literal] cannot follow a [variable]
- Used functions have to be [white listed](src/Mathepa/Lexer.php)
- Variables muss contain a valid expression as well
- No circular references in variables found

Mathepa **doesn't check the number of formal parameters** of a function but
only the syntax. Some examples below:

Following example will throw an exception because of a missing parameter,
syntax is valid, so _eval_ is called:

```php
(new Expression('cos()'))->evaluate();
```

This example Will throw an exception because of an unexpected parameter, syntax
is valid, in this case _eval_ is called:

```php
(new Expression('cos(30, 60)'))->evaluate();
```

Following examples will throw an exception because of a syntax error, in those
cases _eval_ is never called:

```php
(new Expression('cos(30,)'))->evaluate();
(new Expression('3 2'))->evaluate();
(new Expression('abs(-43.24), 2'))->evaluate();
```

See [units tests for more examples](test/)

Notice that only math function with numeric parameters are [white listed][lexer].

[lexer]: src/Mathepa/Lexer.php

## Examples

### Usage of variables

```php
use \Mathepa\Expression;

$m = new Expression('1 / 2 * gravity * seconds');
$m->setVariable('gravity', '9.8');
$m->setVariable('seconds', '2');
$height = $m->evaluate();
```

### Usage of functions

```php
use \Mathepa\Expression;

$m = new Expression();
$m->setVariable('distance', 40);
$m->setVariable('degrees', 35);
$height = $m->setExpression('distance * tan(degrees)')->evaluate();
```

### Usage of ternary operator in expressions

Only long syntax supported, i.e., (expr1) ? (expr2) : (expr3)

```php
$m = new Expression('round((price - (price * discount)) * vat,  2) * units');
$m->setVariable('discount', '(units > 100 ? (units > 500 ? 0.10 : 0.20) : 0)');
$m->setVariable('price', 20);
$m->setVariable('units', 125);
$m->setVariable('vat', 1.19);
$total = $m->evaluate();
```

## Installation

```bash
composer require llpo/mathepa
```

## Tests

Run unit tests:

```bash
docker-compose run test
```

## Development environment with docker

Open a shell:

```bash
docker-compose run shell
```

Install development packages:

```bash
docker-compose run install
```

Run some composer scripts:

```bash
composer test
```
