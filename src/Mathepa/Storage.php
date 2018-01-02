<?php

namespace Mathepa;

// Exceptioncs
use Mathepa\Exception\InvalidVariableException;

/**
 * Class Storage
 * @author lpo
 */
class Storage implements \Iterator
{
    /**
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
     * @param string $expression
     * @throws \Mathepa\Exception\InvalidVariableException
     */
    public function set(string $name, string $expression): self
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
        $this->storage[$name] = $expression;

        return $this;
    }

    /**
     * @param string $name
     * @throws \Mathepa\Exception\InvalidVariableException
     * @return string
     */
    public function get(string $name): string
    {
        if (!isset($this->storage[$name])) {
            throw new InvalidVariableException(
                sprintf('Variable "%s" not set', $name)
            );
        }
        return $this->storage[$name]; // Oposite of parse ??
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
    public function rewind()
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
