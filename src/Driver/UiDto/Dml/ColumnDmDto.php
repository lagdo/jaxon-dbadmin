<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function implode;
use function in_array;
use function preg_match;

class ColumnDmDto
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string
     */
    public string $fullType;

    /**
     * @var string|null
     */
    public string|null $comment;

    /**
     * @var mixed
     */
    public mixed $value;

    /**
     * @var string|null
     */
    public string|null $function;

    /**
     * @var array
     */
    public array $functions;

    /**
     * @var array
     */
    public array $valueInput;

    /**
     * @var array|null
     */
    public array|null $functionInput;

    /**
     * @var array
     */
    public array $enums = [];

    /**
     * @var bool|null
     */
    public bool|null $isText = null;

    /**
     * @param ColumnDto $column
     */
    public function __construct(public readonly ColumnDto $column)
    {
        $this->type = $column->type;
        $this->comment = $column->comment;
    }

    /**
     * @return boolean
     */
    public function isDisabled(): bool
    {
        return $this->column->isDisabled();
    }

    /**
     * @return boolean
     */
    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }

    /**
     * @return boolean
     */
    public function isSet(): bool
    {
        return $this->type === 'set';
    }

    /**
     * @return boolean
     */
    public function isBool(): bool
    {
        return preg_match('~bool~', $this->type);
    }

    /**
     * @return boolean
     */
    public function isJson(): bool
    {
        return $this->function === "json" || preg_match('~^jsonb?$~', $this->type);
    }

    /**
     * @return boolean
     */
    public function isText(): bool
    {
        return $this->isText ??= (bool)preg_match('~text|lob|memo~i', $this->type);
    }

    /**
     * @return boolean
     */
    public function hasNewLine(): bool
    {
        return preg_match("~\n~", $this->value ?? '');
    }

    /**
     * @return boolean
     */
    public function isSearch(): bool
    {
        // PostgreSQL search types.
        return in_array($this->type, ['tsvector', 'tsquery']);
    }

    /**
     * @return boolean
     */
    public function editText(): bool
    {
        return $this->isText() || $this->hasNewLine() || $this->isSearch();
    }

    /**
     * @return boolean
     */
    public function isChecked(): bool
    {
        return preg_match('~^(1|t|true|y|yes|on)$~i', $this->value ?? '');
    }

    /**
     * @return boolean
     */
    public function hasFunction(): bool
    {
        return in_array($this->function, $this->functions) ||
            isset($this->functions[$this->function]);
    }

    /**
     * @return boolean
     */
    public function isNumber(): bool
    {
        return (!$this->hasFunction() || $this->function === '') &&
            preg_match('~(?<!o)int(?!er)~', $this->type) &&
            !preg_match('~\[\]~', $this->column->fullType);
    }

    /**
     * @param int $maxlength
     *
     * @return boolean
     */
    public function bigSize(int $maxlength): bool
    {
        return preg_match('~char|binary~', $this->type) && $maxlength > 20;
    }

    /**
     * @return string
     */
    public function enumsLength(): string
    {
        return !$this->enums ? '' : "'" . implode("', '", $this->enums) . "'";
    }

    /**
     * @return mixed
     */
    public function functionValue(): mixed
    {
        return $this->function === null || $this->hasFunction() ? $this->function : '';
    }
}
