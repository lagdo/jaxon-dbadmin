<?php

namespace Lagdo\DbAdmin\Db\Facades\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function preg_match;

trait InputFieldTrait
{
    /**
     * @param TableFieldEntity $field
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    private function getInputFieldExpression(TableFieldEntity $field, string $value, string $function): string
    {
        $expression = $this->driver->quote($value);
        // Todo: use match
        if (preg_match('~^(now|getdate|uuid)$~', $function)) {
            return "$function()";
        }
        if (preg_match('~^current_(date|timestamp)$~', $function)) {
            return $function;
        }
        if (preg_match('~^([+-]|\|\|)$~', $function)) {
            return $this->driver->escapeId($field->name) . " $function $expression";
        }
        if (preg_match('~^[+-] interval$~', $function)) {
            return $this->driver->escapeId($field->name) . " $function " .
                (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) ? $value : $expression);
        }
        if (preg_match('~^(addtime|subtime|concat)$~', $function)) {
            return "$function(" . $this->driver->escapeId($field->name) . ", $expression)";
        }
        if (preg_match('~^(md5|sha1|password|encrypt)$~', $function)) {
            return "$function($expression)";
        }
        return $expression;
    }

    /**
     * @param TableFieldEntity $field Single field from fields()
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    protected function getUnconvertedFieldValue(TableFieldEntity $field, string $value, string $function = ''): string
    {
        if ($function === 'SQL') {
            return $value; // SQL injection
        }

        $expression = $this->getInputFieldExpression($field, $value, $function);
        return $this->driver->unconvertField($field, $expression);
    }
}
