<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;

use function implode;
use function in_array;
use function preg_match;

class FieldEditDto
{
    /**
     * @var mixed
     */
    public $name;

    /**
     * @var mixed
     */
    public $type;

    /**
     * @var mixed
     */
    public $fullType;

    /**
     * @var mixed
     */
    public $comment;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string|null
     */
    public $function;

    /**
     * @var array
     */
    public $functions;

    /**
     * @var array
     */
    public $valueInput;

    /**
     * @var array|null
     */
    public $functionInput;

    /**
     * @var array
     */
    public $enums = [];

    /**
     * @var bool|null
     */
    public $isText = null;

    /**
     * @param TableFieldDto $field
     */
    public function __construct(public TableFieldDto $field)
    {
        $this->type = $field->type;
        $this->comment = $field->comment;
    }

    /**
     * @return boolean
     */
    public function isDisabled(): bool
    {
        return $this->field->isDisabled();
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
            isset($functions[$this->function]);
    }

    /**
     * @return boolean
     */
    public function isNumber(): bool
    {
        return (!$this->hasFunction() || $this->function === "") &&
            preg_match('~(?<!o)int(?!er)~', $this->type) &&
            !preg_match('~\[\]~', $this->field->fullType);
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
