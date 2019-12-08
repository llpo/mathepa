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
     * Associative array where:
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

        return $this->clone($name);
    }

    /**
     * Delete / unset variable
     *
     * @param string $name
     * @return self
     */
    public function del(string $name): self
    {
        unset($this->storage[$name]);

        return $this;
    }

    /**
     * Clear storage
     *
     * @return self
     */
    public function clear(): self
    {
        $this->storage = [];
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

    /**
     * @return array Return associative array with cloned tokens
     */
    public function toArray(): array
    {
        $variables = [];
        foreach (array_keys($this->storage) as $name) {
            $variables[$name] = $this->clone($name);
        }

        return $variables;
    }

    /**
     * @param string $name Variable name
     * @return Token[]
     */
    private function clone(string $name)
    {
        $tokens = [];

        foreach ($this->storage[$name] as $token) {
            $tokens[] = clone $token;
        }

        return $tokens;
    }
}
