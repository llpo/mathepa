<?php

namespace Mathepa;

/**
 * Class Token
 */
class Token
{
    /**
     * Types of tokens
     */
    const TYPE_LITERAL                  = 'literal';
    const TYPE_CLOSING_BRAKET           = 'closing_bracket';
    const TYPE_OPENING_BRAKET           = 'opening_bracket';
    const TYPE_ARITHMETIC_OPERATOR      = 'arithmetic_operator';
    const TYPE_COMPARISON_OPERATOR      = 'comparison_operator';
    const TYPE_FUNCTION                 = 'function';
    const TYPE_OPENING_BRAKET_FUNCTION  = 'opening_bracket_function';
    const TYPE_CLOSING_BRAKET_FUNCTION  = 'closing_bracket_function';
    const TYPE_COMMA_FUNCTION           = 'comma_function';
    const TYPE_VARIABLE                 = 'variable';

    /**
     * @var array
     */
    const TYPES = [
        self::TYPE_LITERAL,
        self::TYPE_OPENING_BRAKET,
        self::TYPE_CLOSING_BRAKET,
        self::TYPE_ARITHMETIC_OPERATOR,
        self::TYPE_COMPARISON_OPERATOR,
        self::TYPE_FUNCTION,
        self::TYPE_OPENING_BRAKET_FUNCTION,
        self::TYPE_CLOSING_BRAKET_FUNCTION,
        self::TYPE_COMMA_FUNCTION,
        self::TYPE_VARIABLE,
    ];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $value;

    /**
     * Absolute position in a string (mathematic expression)
     * @see \Mathepa\Token::setPosition()
     *
     * @var int
     */
    protected $position;

    /**
     * Line number in a text
     * @see \Mathepa\Token::setLine()
     *
     * @var int
     */
    protected $line;

    /**
     * Column number in a text
     * @see \Mathepa\Token::setColumn()
     *
     * @var int
     */
    protected $column;

    /**
     * Length of token's value
     *
     * @var int
     */
    protected $length;

    /**
     * @param string $type
     * @param string $value
     * @param int $position
     * @param int $line
     * @param int $column
     * @throws \InvalidArgumentException;
     */
    public function __construct(
        string $type,
        string $value,
        int $position,
        int $line,
        int $column
    ) {
        $this->setType($type);
        $this->setValue($value);
        $this->setPosition($position);
        $this->setLine($line);
        $this->setColumn($column);
    }

    /**
     * @param string $value
     * @throws \UnexpectedValueException
     * @return self
     */
    public function setType(string $type): self
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \UnexpectedValueException(
                sprintf('Unknown type "%s"', $type)
            );
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @param string $value
     * @throws \UnexpectedValueException;
     * @return self
     */
    public function setValue(string $value): self
    {
        if (trim($value) === '') {
            throw new \UnexpectedValueException(
                'Unexpected empty string as token value'
            );
        }

        $this->value = $value;
        $this->length = strlen($value);

        return $this;
    }

    /**
     * @param int $position
     * @throws \UnexpectedValueException;
     * @return self
     */
    public function setPosition(int $position): self
    {
        if ($position < 0) {
            throw new \UnexpectedValueException(
                sprintf('Wrong position value "%d"', $position)
            );
        }

        $this->position = $position;

        return $this;
    }

    /**
     * @param int $line
     * @throws \UnexpectedValueException;
     * @return self
     */
    public function setLine(int $line): self
    {
        if ($line < 1) {
            throw new \UnexpectedValueException(
                sprintf('Wrong line value "%d"', $line)
            );
        }

        $this->line = $line;

        return $this;
    }

    /**
     * @param int $column
     * @throws \UnexpectedValueException;
     * @return self
     */
    public function setColumn(int $column): self
    {
        if ($column < 0) {
            throw new \UnexpectedValueException(
                sprintf('Wrong column value "%d"', $column)
            );
        }

        $this->column = $column;

        return $this;
    }

    /**
     *  @param string $name
     *  @throws \UnexpectedValueException
     *  @return miexed
     */
    public function __get($name)
    {
        if (!isset($this->$name)) {
            throw new \UnexpectedValueException(
                sprintf('Unknown property name "%s"', $name)
            );
        }

        return $this->$name;
    }
}
