<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function ini_get;
use function intval;
use function is_string;
use function preg_match;
use function strtolower;
use function substr;

trait AdminTrait
{
    /**
     * Check if the string is e-mail address
     *
     * @param mixed $email
     *
     * @return bool
     */
    public function isMail($email): bool
    {
        if (!is_string($email)) {
            return false;
        }
        $atom = '[-a-z0-9!#$%&\'*+/=?^_`{|}~]'; // characters of local-name
        $domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component
        $pattern = "$atom+(\\.$atom+)*@($domain?\\.)+$domain";
        return preg_match("(^$pattern(,\\s*$pattern)*\$)i", $email) > 0;
    }

    /**
     * Check if the string is URL address
     *
     * @param mixed $string
     *
     * @return bool
     */
    public function isUrl($string): bool
    {
        $domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN
        //! restrict path, query and fragment characters
        return preg_match("~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string) > 0;
    }

    /**
     * Check if field should be shortened
     *
     * @param TableFieldEntity $field
     *
     * @return bool
     */
    public function isShortable(TableFieldEntity $field): bool
    {
        return preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~', $field->type) > 0;
    }

    /**
     * Get INI boolean value
     *
     * @param string $ini
     *
     * @return bool
     */
    public function iniBool(string $ini): bool
    {
        $value = ini_get($ini);
        // boolean values set by php_value are strings
        return (preg_match('~^(on|true|yes)$~i', $value) || (int) $value);
    }

    /**
     * Get INI bytes value
     *
     * @param string
     *
     * @return int
     */
    public function iniBytes(string $ini): int
    {
        $value = ini_get($ini);
        $unit = strtolower(substr($value, -1)); // Get the last char
        $ival = intval(substr($value, 0, -1)); // Remove the last char
        switch ($unit) {
            case 'g': $value = $ival * 1024 * 1024 * 1024; break;
            case 'm': $value = $ival * 1024 * 1024; break;
            case 'k': $value = $ival * 1024; break;
        }
        return intval($value);
    }
}
