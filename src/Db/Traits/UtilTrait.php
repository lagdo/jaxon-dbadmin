<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function is_string;
use function preg_match;
use function ini_get;
use function strtolower;
use function substr;
use function intval;
use function str_replace;
use function preg_quote;
use function str_repeat;
use function strtr;

trait UtilTrait
{
    /**
     * Escape for HTML
     *
     * @param string|null $string
     *
     * @return string
     */
    abstract public function html($string): string;

    /**
     * Get escaped error message
     *
     * @return string
     */
    public function error(): string
    {
        return $this->html($this->driver->error());
    }

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

    /**
     * Escape column key used in where()
     *
     * @param string
     *
     * @return string
     */
    public function escapeKey(string $key): string
    {
        if (preg_match('(^([\w(]+)(' . str_replace('_', '.*',
                preg_quote($this->driver->escapeId('_'))) . ')([ \w)]+)$)', $key, $match)) {
            //! columns looking like functions
            return $match[1] . $this->driver->escapeId($this->driver->unescapeId($match[2])) . $match[3]; //! SQL injection
        }
        return $this->driver->escapeId($key);
    }

    /**
     * Create repeat pattern for preg
     *
     * @param string $pattern
     * @param int $length
     *
     * @return string
     */
    public function repeatPattern(string $pattern, int $length): string
    {
        // fix for Compilation failed: number too big in {} quantifier
        // can create {0,0} which is OK
        return str_repeat("$pattern{0,65535}", $length / 65535) . "$pattern{0," . ($length % 65535) . '}';
    }

    /**
     * Shorten UTF-8 string
     *
     * @param string $string
     * @param int $length
     * @param string $suffix
     *
     * @return string
     */
    public function shortenUtf8(string $string, int $length = 80, string $suffix = ''): string
    {
        if (!preg_match('(^(' . $this->repeatPattern("[\t\r\n -\x{10FFFF}]", $length) . ')($)?)u', $string, $match)) {
            // ~s causes trash in $match[2] under some PHP versions, (.|\n) is slow
            preg_match('(^(' . $this->repeatPattern("[\t\r\n -~]", $length) . ')($)?)', $string, $match);
        }
        return $this->html($match[1]) . $suffix . (isset($match[2]) ? '' : '<i>…</i>');
    }

    /**
     * Escape or unescape string to use inside form []
     *
     * @param string $idf
     * @param bool $back
     *
     * @return string
     */
    public function bracketEscape(string $idf, bool $back = false): string
    {
        // escape brackets inside name='x[]'
        static $trans = [':' => ':1', ']' => ':2', '[' => ':3', '"' => ':4'];
        return strtr($idf, ($back ? array_flip($trans) : $trans));
    }
}
