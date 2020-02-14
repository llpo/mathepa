
# Mathepa

Mathepa is a parser for mathematical expressions using PHP syntax.

## Why?

The idea arises from the need to save mathematical formulas into a database
for a later usage in different backend processes.

It's strongly recommended to use Mathepa in both directions, before saving
formulas into a database - or any kind of storage - as security constraint
and validator, and after fetching, always acting as a proxy to prevent
direct usage of _[eval][1]_.

## Quick features list

- Express mathematical expression using one line PHP syntax
- Definition of variables to parametrize values
- Ternary operator supported
- Most common [math functions](src/Mathepa/Lexer.php) are white listed
- Variables can contain complex expressions and reference other variables
- _[eval][1]_ function will be called only with valid expressions
- No external dependencies required

[1]: http://php.net/eval

## How it works?

Mathepa uses the _eval_ function only after expressions are syntactically and
grammatically valid, and thus secure. An expression is considered as valid
only when:

- Syntax is valid, i.e. all tokens are identified. E.g. "3.1.4" would be invalid
- Grammar is valid, i.e. a literal can't be next to a variable
- Functions are [white listed](src/Mathepa/Lexer.php)
- Variables contains valid expressions
- No circular references found

Notice Mathepa does not check the number of formal parameters of a
function. When the number of parameters mismatches, execution will just
fail like in the PHP console:

```php
> abs(-2, -3);
PHP Warning:  abs() expects exactly 1 parameter, 2 given in php shell code on line 1
```

Calling functions in Mathepa either with wrong number of parameters or wrong
syntax will always throw an Exception. In the next examples _eval_ is called
because there's no invalid syntax, grammar or unknown function:

```php
(new Expression('cos()'))->evaluate();
(new Expression('cos(30, 60)'))->evaluate();
```

Following examples will throw an exception because of syntax error. In the following
cases _eval_ is never called:

```php
(new Expression('cos(30,)'))->evaluate();
(new Expression('3 2'))->evaluate();
(new Expression('abs(-43.24), 2'))->evaluate();
(new Expression('fakeMethod(22), 2'))->evaluate();
```

See [units tests for more examples](test/)

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

### Complex expressions

The ternary operator in expressions is allowed, but only the long syntax
is supported, i.e. (expr1) ? (expr2) : (expr3).

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

## Development environment with docker

Install development packages:

```bash
docker-compose run install
```

Open a shell:

```bash
docker-compose run shell
```

Run composer script inside a container:

```bash
docker-compose run shell
composer test
```
Direct call of phpunit:

```bash
docker-compose run shell
phpunit test/
```
