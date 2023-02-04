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
namespace Siktec\Bsik\Objects;

use \Siktec\Bsik\Std;

/**
 * SettingsObject
 * 
 * This class is used to construct a settings object which is used to store settings in a bsik way.
 * 
 * @package Bsik\Objects
 */
class SettingsObject {

    const OPT_INT      = "integer"; //TODO: remove later this is from old version
    const OPT_FLOAT    = "float";
    const OPT_STR      = "string";
    const OPT_BOOL     = "boolean";
    const OPT_NOTEMPTY = "notempty";
    
    
    const FLAG_REMOVE       = "@remove@"; 
    const CHAIN_DELI        = ":";
    const TYPE_OR           = "|";
    const CONSIDERED_TRUE   = [true, 1, '1', "true", "TRUE"];
    const VALIDATE_TYPES    = ["integer", "float", "string", "boolean", "array", "any"];
    public static $validators = [];

    public array $values        = [];
    public array $defaults      = [];
    public array $options       = [];
    public array $descriptions  = [];
    

    public function __construct(
        array|string $defaults      = [],
        array|string $options       = [],
        array|string $descriptions  = [],
    ) {
        $this->extend_descriptions($descriptions);
        $this->extend_options($options);
        $this->extend_defaults($defaults);
    }
        
    /**
     * is_empty
     * checks is there are any settings defined is this instance
     * @return bool
     */
    public function is_empty() : bool {
        return empty($this->values) && empty($this->defaults);
    }
    
    /**
     * import
     * import settings to this instance
     * @param  string|array $from
     * @return void
     */
    public function import(string|array $from) : void {
        //If json:
        if (is_string($from)) {
            $from = Std::$str::parse_json($from, onerror: []);
        }
        //Get parts:
        $settings = Std::$arr::get_from($from, ["values", "defaults", "options", "descriptions"], []);
        $this->extend_descriptions($settings["descriptions"]);
        $this->extend_options($settings["options"]);
        $this->extend_defaults($settings["defaults"]);
        $this->extend($settings["values"]);
    }

    /** 
     * is_valid
     * mostly inner use that checks if given values are valid based on the options definition,
     * set $cast to true to force the types.
     * @param  array $values
     * @param  array &$errors
     * @param  bool  $cast
     * @return array - tuple [boolean:valid, array:final_values] 
     */
    public function is_valid(array $values, array &$errors, bool $cast = true) : array {
        $validated = [];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $this->options) && $value !== self::FLAG_REMOVE) {

                //Array options validator -> they mean one of those:
                if (is_array($this->options[$key])) {
                    if (!in_array($value, $this->options[$key]))
                        $errors[] = "{$key} value is not allowed for this setting";
                    else 
                        $validated[$key] = $value;
                    continue;
                }

                //get options:
                $chain = explode(self::CHAIN_DELI, $this->options[$key]);
                $type  = (str_contains($chain[0] ?? "", self::TYPE_OR) || in_array($chain[0] ?? "", self::VALIDATE_TYPES)) ? array_shift($chain) : "any";
                $condition_str = implode(self::CHAIN_DELI, [$type, ...$chain]);

                $final_value = $value;
                //First cast:
                if ($cast && $type !== "any" && !str_contains($type ,self::TYPE_OR)) {
                    switch ($type) {
                        case "integer": 
                            $final_value  = is_numeric($value) ? intval($value) : $value;
                        break;
                        case "float":
                            $final_value  = is_numeric($value) ? floatval($value) : $value;
                        break;
                        case "string":
                            $final_value  = !is_string($value) ? @strval($value) : $value;
                        break;
                        case "boolean":
                            $final_value  = in_array($value, self::CONSIDERED_TRUE, true);
                        break;
                        default:
                        $final_value  = $value;
                    }
                }

                $value_errors = [];
                if (!Std::$arr::validate([$key => $condition_str], [$key => $final_value ], self::$validators, $value_errors)) {
                    if (!empty($value_errors)) {
                        $value_errors = array_values($value_errors);
                        foreach ($value_errors as $err) {
                            if (is_array($err)) {
                                foreach ($err as $er) {
                                    $errors[] = str_contains($er, "%s") ? sprintf($er, $key) : $key." - ".$er;
                                }
                            } else {
                                $errors[] = str_contains($err, "%s") ? sprintf($err, $key) : $key." - ".$err;
                            }
                        }
                    }
                } else {
                    $validated[$key] = $final_value;
                }
            } else {
                $validated[$key] = $value;
            }
        }
        return [empty($errors), $validated];
    }

    /**
     * extend_options
     * extend the options array
     * @param  array|string $options - string will be treated as json.
     * @return void
     */
    public function extend_options(array|string $options) {
        //If json:
        if (is_string($options)) {
            $options = Std::$str::parse_json($options, onerror: []);
        }
        $this->options = Std::$arr::extend($this->options, $options);
    }

    /**
     * extend_descriptions
     * extend the descriptions array
     * @param  array|string $descriptions - string will be treated as json.
     * @return void
     */
    public function extend_descriptions(array|string $descriptions) {
        //If json:
        if (is_string($descriptions)) {
            $descriptions = Std::$str::parse_json($descriptions, onerror: []);
        }
        $this->descriptions = Std::$arr::extend($this->descriptions, $descriptions);
    }

    /**
     * extend_descriptions
     * extend the defaults array
     * will check values against the options definition
     * @param  array|string $extend - string will be treated as json.
     * @param  array        &$errors
     * @param  bool         $cast - set $cast to true to force the types
     * @return bool
     */
    public function extend_defaults(array|string $extend, array &$errors = [], bool $cast = true) : bool {
        if (is_string($extend)) {
            $extend = Std::$str::parse_json($extend, onerror: []);
        }
        [$valid, $values] = $this->is_valid($extend, $errors, $cast);
        if ($valid) {
            $this->defaults = Std::$arr::extend($this->defaults, $values);
        }
        return $valid;
    }

    /**
     * extend
     * extend the values array - current set values
     * will check values against the options definition
     * @param  array|string $extend - string will be treated as json.
     * @param  array        &$errors
     * @param  bool         $cast - set $cast to true to force the types
     * @return bool
     */
    public function extend(array|string $extend, array &$errors = [], bool $cast = true) : bool {
        if (is_string($extend)) {
            $extend = Std::$str::parse_json($extend, onerror: []);
        }
        [$valid, $values] = $this->is_valid($extend, $errors, $cast);
        if ($valid) {
            $this->values = Std::$arr::extend($this->values, $values);
            $this->unset(); // removes all flagged values
        }
        return $valid;
    }
    
    /**
     * unset_default
     * delete a default key
     * @param  string $key
     * @return void
     */
    public function unset_default(string $key) : void {
        if (array_key_exists($key, $this->defaults))
            unset($this->defaults[$key]);
    }
    
    /**
     * unset_option
     * delete a option key
     * @param  string $key
     * @return void
     */
    public function unset_option(string $key) : void {
        if (array_key_exists($key, $this->options))
            unset($this->options[$key]);
    }
    
    /**
     * unset_description
     * delete a descriptions key
     * @param  string $key
     * @return void
     */
    public function unset_description(string $key) : void {
        if (array_key_exists($key, $this->descriptions))
            unset($this->descriptions[$key]);
    }

    /**
     * unset
     * unset only in values
     * if key is omitted or empty then will scan for @remove@ tags to unset.
     * @param  string|null $key
     * @return void
     */
    public function unset(?string $key = null) : void {
        if (is_null($key)) {
            foreach ($this->values as $k => $value) {
                if ($value === self::FLAG_REMOVE)
                    unset($this->values[$k]);
            }
        } elseif (array_key_exists($key, $this->values)) {
            unset($this->values[$key]);
        }
    }

    /**
     * get_default
     * get specific default value
     * @param  string $key
     * @param  mixed $default
     * @return mixed - the value
     */
    public function get_value(string $key = "", $default = null) : mixed {
        return $this->values[$key] ?? $default;
    }

    /**
     * get_default
     * get specific default value
     * @param  string $key
     * @param  mixed $default
     * @return mixed - the value
     */
    public function get_default(string $key = "", $default = null) : mixed {
        return $this->defaults[$key] ?? $default;
    }

    /**
     * get_option
     * get specific option value
     * @param  string $key
     * @param  mixed $default
     * @return mixed - the value
     */
    public function get_option(string $key = "", $default = null) : mixed {
        return $this->options[$key] ?? $default;
    }

    /**
     * get_description
     * get specific description value
     * @param  string $key
     * @param  mixed $default
     * @return mixed - the value
     */
    public function get_description(string $key = "", $default = null) : mixed {
        return $this->descriptions[$key] ?? $default;
    }

    /**
     * get
     * get a value from current values > defaults > fallback default
     * @param  string $key
     * @param  mixed $default
     * @return mixed - the value
     */
    public function get(string $key = "", $default = null) : mixed {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } elseif (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }
        return $default;
    }
    
    public function get_key(string $key = "") : null|array {
        if ($this->has($key)) {
            return [
                "value"         => $this->get_value($key),
                "default"       => $this->get_default($key),
                "option"        => $this->get_option($key),
                "description"   => $this->get_description($key),
            ];
        }
        return null;
    }
    /**
     * get_all
     * return all values merged with defaults or not
     * @param  bool $merged
     * @return array
     */
    public function get_all(bool $merged = true) : array {
        return $merged ? Std::$arr::extend($this->defaults, $this->values)
                       : $this->values;
    }
    
    /**
     * has
     * check if a key is set in defaults or values
     * @param  string|array $key - if array all keys will be checked.
     * @return bool
     */
    public function has(string|array $key) : bool {
        if (is_array($key)) {
            foreach ($key as $k) {
                if (array_key_exists($k, $this->defaults) || array_key_exists($k, $this->values))
                    continue;
                return false;
            }
        } else {
            return (
                array_key_exists($key, $this->defaults) 
                || 
                array_key_exists($key, $this->values)
            );
        } 
        return true;
    }        
    
    /**
     * set_option
     * sets a key option value
     * @param  string $key
     * @param  mixed $value
     * @return bool
     */
    public function set_option(string $key, mixed $value) : bool {
        $this->extend_options([ $key => $value]);
        return true;
    }

    /**
     * set_description
     * sets a key description value
     * @param  string $key
     * @param  mixed $value
     * @return bool
     */
    public function set_description(string $key, mixed $value) : bool {
        $this->extend_descriptions([ $key => $value]);
        return true;
    }

    /**
     * set_default
     * sets a key default value
     * @param  string $key
     * @param  mixed $value
     * @param  array $errors - errors messages will be added here
     * @param  bool $cast
     * @return bool
     */
    public function set_default(string $key, mixed $value, array &$errors = [], bool $cast = true) : bool {
        return $this->extend_defaults([ $key => $value], $errors, $cast);
    }

    
    /**
     * set
     * sets a key value
     * @param  string $key
     * @param  mixed $value
     * @param  array $errors - errors messages will be added here
     * @param  bool $cast
     * @return bool
     */
    public function set(string $key, mixed $value, array &$errors = [], bool $cast = true) : bool {
        return $this->extend([$key => $value], $errors, $cast);
    }

    /**
     * is
     * convenient method to quickly check if  a setting is equal to value 
     * @param  string $key
     * @param  mixed $value
     * @return bool
     */
    public function is(string $key, mixed $value) : bool {
        return $this->get($key) === $value;
    }
    /**
     * is_true
     * convenient method to quickly check if a setting is equal to TRUE 
     * @param  string $key
     * @return bool
     */
    public function is_true(string $key) : bool {
        return $this->is($key, true);
    }
    /**
     * is_false
     * convenient method to quickly check if a setting is equal to FALSE 
     * @param  string $key
     * @return bool
     */
    public function is_false(string $key) : bool {
        return $this->is($key, false);
    }
    /**
     * is_defined
     * same as has - check if a key is set in defaults or values
     * @param  string|array $key
     * @return bool
     */
    public function is_defined(string|array $key) : bool {
        return $this->has($key);
    }

    public function diff_summary() {
        return [
            "overridden"    => array_keys(array_intersect_key($this->defaults, $this->values)),
            "inherited"     => array_keys(array_diff_key($this->defaults, $this->values)),
            "unique"        => array_keys(array_diff_key($this->values, $this->defaults))
        ];
    }
    /**
     * printing methods:
     */
    public function dump_parts($json = false, ...$parts) : array|string {
        $arr = [];
        $parts = empty($parts) ? ["values", "defaults", "options", "descriptions"] : $parts;
        foreach ($parts as $part) {
            switch ($part) {
                case "values": {
                    $arr["values"] = $this->values;
                } break;
                case "values-merged": {
                    $arr["values"] = $this->get_all(true);
                } break;
                case "defaults": {
                    $arr["defaults"] = $this->defaults;
                } break;
                case "options": {
                    $arr["options"] = $this->options;
                } break;
                case "descriptions": {
                    $arr["descriptions"] = $this->descriptions;
                } break;
            }
        }
        return $json ? json_encode($arr, JSON_PRETTY_PRINT) : $arr;
    }
    public function values_json(bool $pretty = false) : string {
        return \json_encode($this->values, $pretty ? JSON_PRETTY_PRINT : 0);
    }
    public function defaults_json(bool $pretty = false) : string {
        return \json_encode($this->defaults, $pretty ? JSON_PRETTY_PRINT : 0);
    }
    public function options_json(bool $pretty = false) : string {
        return \json_encode($this->options, $pretty ? JSON_PRETTY_PRINT : 0);
    }
    public function descriptions_json(bool $pretty = false) : string {
        return \json_encode($this->descriptions, $pretty ? JSON_PRETTY_PRINT : 0);
    }
    public function __toString() : string {
        return \json_encode([
            "values"        => $this->values,
            "defaults"      => $this->defaults,
            "options"       => $this->options,
            "descriptions"  => $this->descriptions,
        ]);
    }
}

SettingsObject::$validators["notempty"] = function($value, $path) {
    return $value === "" || is_null($value) ? "%s value can not be empty" : true;
};