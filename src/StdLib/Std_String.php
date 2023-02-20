<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/
namespace Siktec\Bsik\StdLib;

/**********************************************************************************************************
* String Methods:
**********************************************************************************************************/
class Std_String {
    
    public static $regex = [
        "filter-none" => '~[^%s]~',
        "version"     => '/^(\d+\.)?(\d+\.)?(\*|\d+)$/'
    ];

    /**
     * starts_with
     * Check if a string starts with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function starts_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }

    /**
     * ends_with
     * Check if a string ends with a string
     * 
     * @param  string $haystack
     * @param  string $needle
     * @return bool
     */
    final public static function ends_with(string $haystack, string $needle) : bool {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    } 

    /**
     * filter_string
     *
     * @param  string $str
     * @param  mixed $allowed - string or array
     * @return string - filtered string
     */
    final public static function filter_string(string $str, $allowed = ["A-Z","a-z","0-9"]) : string {
        $regex = is_string($allowed) ? 
            sprintf(self::$regex["filter-none"], $allowed) :
            sprintf(self::$regex["filter-none"], implode($allowed));
        return preg_replace($regex, '', $str);
    }
    
    /**
     * is_version - checks if a string is a valid version number D.D.D
     *
     * @param  mixed $version
     * @return bool
     */
    final public static function is_version(string $version) : bool {
        return preg_match(self::$regex["version"], $version);
    }
    
    /**
     * validate_version - compare versions
     * More: https://www.php.net/manual/en/function.version-compare.php
     * returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
     * When using the optional operator argument, the function will return true if the relationship is the one specified by the operator, false otherwise.
     * @param  mixed $version
     * @param  mixed $against
     * @param  mixed $condition - <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>
     * @return bool|int
     */
    final public static function validate_version(string $version, string $against, ?string $condition = null) {
        return version_compare(
            trim($version), 
            trim($against), 
            trim($condition)
        );
    }

    /**
     * is_json - validates a json string by safely parsing it
     * 
     * @param array ...$args => packed arguments to pass to json_decode
     * @return bool 
     */
    final public static function is_json(...$args) : bool {
        json_decode(...$args);
        return (json_last_error() === JSON_ERROR_NONE);
    }
        
    /**
     * parse_json
     * safely try to parse json.
     * @param string $json
     * @param mixed $onerror - what to return on error
     * @param bool  $assoc - force associative array
     * @return mixed
     */
    final public static function parse_json(string $json, $onerror = false, bool $assoc = true) {
        return json_decode($json, $assoc) ?? $onerror;
    }

    /**
     * parse_jsonc
     * safely try to parse jsonc (json with comments).
     * @param string $jsonc
     * @param bool   $remove_bom - try to remove byte order mark
     * @param mixed  $onerror - what to return on error
     * @param bool   $assoc - force associative array
     * @return mixed
     */
    final public static function parse_jsonc(string $jsonc, bool $remove_bom = true, $onerror = false, bool $assoc = true) {
        $json = trim(
            Std_String::strip_comments($jsonc), 
            $remove_bom ? "\xEF\xBB\xBF \t\n\r\0\x0B" : " \t\n\r\0\x0B"
        );
        return json_decode($json, $assoc) ?? $onerror;
    }

    /**
     * str_strip_comments - remove comments from strings
	 * From https://stackoverflow.com/a/19136663/319266
	 * @param string $str
	 */
	public static function strip_comments(string $str = '' ) : string {
		return preg_replace('~(" (?:\\\\. | [^"])*+ ") | \# [^\v]*+ | // [^\v]*+ | /\* .*? \*/~xs', '$1', $str);
	}

}
