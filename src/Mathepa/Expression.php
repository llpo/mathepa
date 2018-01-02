<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidExpressionException;

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
    private $tokens;

    /**
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return void
     */
    public function __construct(?string $expression = null)
    {
        $this->tokens = [];

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
        $expression = Parser::parse($this->variables, ...$this->tokens);

        if ($expression === null) {
            throw new InvalidExpressionException('No expression set');
        }

        $result = null;

        try {
            eval('$result = ' . $expression . ';');
            if ($result === null) {
                throw new \Throwable('Unexpected null result');
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
     * @return self
     */
    public function setVariable(string $name, string $expression): self
    {
        $tokens = $this->anatomize($expression);

        foreach ($tokens as $token) {
            if ($token->type === Token::TYPE_VARIABLE) {
                throw new \UnexpectedValueException(
                    'Use of nested variables is not supported'
                );
            }
        }

        $this->variables->set($name, $expression);

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
        $this->tokens = $this->anatomize($expression);

        return $this;
    }

    /**
     * @var string $expression e.g. '(2 - 1) + 3'
     * @throws \Mathepa\Exception\InvalidExpressionException
     * @return \Mathepa\Token[]
     */
    protected function anatomize(string $expression)
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
            var_dump($tokens);
            throw new InvalidExpressionException(
                implode("\n", $errors)
            );
        }

        return $tokens;
    }
}
