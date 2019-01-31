<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidExpressionException;
use Mathepa\Exception\InvalidVariableException;

/**
 * Class Expression
 */
class Expression
{
    /**
     * @var \Mathepa\Storage
     */
    private $variables;

    /**
     * @var \Mathepa\Token[]
     */
    private $expression;

    /**
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return void
     */
    public function __construct(?string $expression = null)
    {
        $this->expression = [];

        if ($expression !== null) {
            $this->setExpression($expression);
        }

        $this->variables = new Storage();
    }

    /**
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return int|float
     */
    public function evaluate()
    {
        $variables = $this->variables->toArray();
        $expression = Parser::parse($variables, ...$this->expression);

        if ($expression === null) {
            throw new InvalidExpressionException('No expression set');
        }

        $result = null;

        try {
            eval('$result = ' . $expression . ';');
            if ($result === null) {
                throw new \Throwable('Unexpected NULL value as result');
            }
        } catch (\Throwable $error) {
            throw new InvalidExpressionException($error->getMessage());
        }

        if (!is_numeric($result)) {
            throw new UnvalidValueException(
                sprintf('Unexpected result type "%s"', gettype($result))
            );
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return self
     */
    public function setVariable(string $name, string $expression): self
    {
        // If no exceptions, everything fine
        $tokens = $this->anatomize($expression);

        $this->variables->set($name, ...$tokens);

        return $this;
    }

    /**
     * @param string $name
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return string
     */
    public function getVariable(string $name): string
    {
        return $this->variables->get($name);
    }

    /**
     * @var string $expression e.g. '(2 - 1) + 3'
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return self
     */
    public function setExpression(string $expression): self
    {
        $this->expression = $this->anatomize($expression);

        return $this;
    }

    /**
     * @param string $expression e.g. '(2 - 1) + 3'
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return \Mathepa\Token[]
     */
    protected function anatomize(string $expression): array
    {
        $tokens = Lexer::tokenize($expression);
        if (empty($tokens)) {
            throw new InvalidExpressionException(
                sprintf(
                    'Invalid expression: "%s", empty value after tokenize',
                    $expression
                )
            );
        }

        $errors = Parser::checkGrammar(...$tokens);
        if (!empty($errors)) {
            throw new InvalidExpressionException(
                implode("\n", $errors)
            );
        }

        return $tokens;
    }
}
