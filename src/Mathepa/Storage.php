<?php

namespace Mathepa;

// Exceptions
use Mathepa\Exception\InvalidVariableException;

/**
 * Class Storage
 */
class Storage implements \Iterator
{
    /**
     * Associative array, where:
     *  - key => variable name
     *  - value => array of tokens
     *
     * @var array
     */
    private $storage;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->storage = [];
    }

    /**
     * @param string $name
     * @param \Mathepa\Token[] $tokens
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return self
     */
    public function set(string $name, Token ...$tokens): self
    {
        if (!$this->isValidName($name)) {
            throw new InvalidVariableException(
                sprintf(
                    'Wrong variable name "%s", only ASCII characters ' .
                    'allowed, and the first character muss be a letter.',
                    $name
                )
            );
        }

        $this->storage[$name] = $tokens;
        foreach ($this->storage as $varName => $varTokens) {
            try {
                $path = [];
                $this->findCircularReferences($path, $varName, ...$varTokens);
            } catch (InvalidVariableException $exception) {
                $this->del($name);
                throw $exception;
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return \Mathepa\Token[]
     */
    public function get(string $name): array
    {
        if (!isset($this->storage[$name])) {
            throw new InvalidVariableException(
                sprintf('Variable "%s" not set', $name)
            );
        }

        return $this->storage[$name];
    }

    /**
     * Delete / unset variable
     *
     * @param string $name
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return self
     */
    public function del(string $name): self
    {
        $this->get($name);
        // If no exception thrown, var with "$name" exists
        unset($this->storage[$name]);

        return $this;
    }

    /**
     * @param string[] &$path Path where to save traversed references
     * @param string $varName
     * @param \Mathepa\Token[] $varTokens
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return void
     */
    protected function findCircularReferences(
        array &$path,
        string $varName,
        Token ...$varTokens
    ): void
    {
        $vars = [];
        foreach ($varTokens as $token) {
            if ($token->type !== Token::TYPE_VARIABLE) {
                continue;
            }
            if (!isset($this->storage[$token->value])) {
                continue;
            }
            if (in_array($token->value, $vars, true)) {
                // Variable could be used more than once in the current
                // expression, so that:
                // - There is no need to double check (performance)
                // - Prevent duplicates in $path that fakes circular references
                continue;
            }
            $vars[] = $token->value;
            if (in_array($varName, $path, true)) {
                throw new InvalidVariableException(
                    sprintf(
                        'Found circular reference in variable "%s"',
                        $path[0]
                    )
                );
            }
            $path[] = $varName;
            $this->findCircularReferences(
                $path,
                $token->value,
                ...$this->storage[$token->value]
            );
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isValidName(string $name): bool
    {
        return preg_match('/^'. Lexer::NAME_REGEX . '$/', $name) === 1;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        reset($this->storage);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->storage);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return key($this->storage);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->storage);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        $key = key($this->storage);

        return ($key !== null && $key !== false);
    }
}
