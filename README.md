
# Mathepa

Mathepa is a parser for mathematical expressions in PHP.

## Why?

I needed some users to be able to enter mathematical formulas through a form
and save them in a database. Furthermore, I wanted to be able to compute
those formulas in the back-end. The problem of that is having to use directly
a dangerous function as _[eval][1]_.

Mathepa acts as a proxy and should be used in both directions, before
saving data or processing input, as validator, and after fetching data, as
evaluator instead using directly _[eval][1]_.

[1]: http://php.net/eval

## Quick feature list

- Express mathematical expression as in PHP
- Use of ternary operator allowed, only long syntax: (expr1) ? (expr2) : (expr3)
- Almost all [Math functions](src/Mathepa/Lexer.php) are allowed to use
- Use of variables to parametrize values
- Variables can contain complex expressions as well
- Function _[eval][1]_ will be called only with valid expressions, and thus
secure
- No external dependencies required

## How it works?

Mathepa uses the PHP function _[eval][1]_. An expression will be evaluated
with _eval_ only when syntax and grammar are valid. Here some requirement to
fulfill before an expression is consider as valid:

- Syntax is valid, i.e. all tokens are identified e.g. "3.1.4" is invalid
- Grammar is valid, i.e. after a [literal] cannot follow a [variable]
- Used functions are [white listed](src/Mathepa/Lexer.php)
- Variables contains a valid expression

Mathepa **doesn't check the number of formal parameters** of a function but
only the syntax. Some examples below:

Will throw an exception because of a missing parameter, syntax is valid so the
expression will be evaluated with _eval_:

```php
(new Expression('cos()'))->evaluate();
```

Will throw an exception because of unexpected parameter, syntax is fine,
so _eval_ is called:

```php
(new Expression('cos(30, 60)'))->evaluate();
```

Those examples will throw an exception because of syntax error, in those
cases _eval_ is not called:

```php
(new Expression('cos(30,)'))->evaluate();
(new Expression('3 2'))->evaluate();
(new Expression('abs(-43.24), 2'))->evaluate();
```

See [units tests for some examples](test/)

Notice that only Math function with numeric values are [white listed][lexer].

[lexer]: src/Mathepa/Lexer.php

## Examples

### Use of variables

```php
use \Mathepa\Expression;

$m = new Expression('1 / 2 * gravity * seconds');
$m->setVariable('gravity', '9.8');
$m->setVariable('seconds', '2');
$height = $m->evaluate();
```

### Use of functions

```php
use \Mathepa\Expression;

$m = new Expression();
$m->setVariable('distance', 40);
$m->setVariable('degrees', 35);
$height = $m->setExpression('distance * tan(degrees)')->evaluate();
```

### Use of ternary operators in expressions

```php
$m = new Expression('round((price - (price * discount)) * vat,  2) * units');
$m->setVariable('discount', '(units > 100 ? (units > 500 ? 0.10 : 0.20) : 0)');
$m->setVariable('price', 20);
$m->setVariable('units', 125);
$m->setVariable('vat', 1.19);
$total = $m->evaluate();
```

## Installation

Install using composer:

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
docker-compose run shell sh
```

Install development packages:

```bash
docker-compose run install
```

Run some composer scripts:

```bash
composer test
```
