<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.1
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial


Add custom validators Example:

class MyFilters {
    public static function start_with(string $input, string $letter = "") {
        if ($input[0] !== $letter[0] ?? "") {
            return "@input@ has to start with the letter {$letter[0]}";
        }
        return true;
    }
}
Validate::add_validator("start_with", "MyValidators::start_with");

or extend by using the entire class:
Validate::add_class_validator(new MyValidators);

*****************************************************************************/

namespace Siktec\Bsik\Impl;

class FiltersCorePack {

    final public static function none(mixed $input) {
        return $input;
    }

    final public static function trim(mixed $input) {
        //procedure
        if (is_array($input)) {
            return array_map(fn($el) => (is_string($el) ? trim($el) : $el), $input);
        }
        return is_string($input) ? trim($input) : $input;
    }
    
    final public static function max_length(mixed $input, mixed $length) {
        //procedure
        $length = intval($length);
        if (is_array($input)) {
            return array_map(
                fn($el) => (function_exists("mb_substr") ? mb_substr($el,0,$length) : substr($el,0,$length)),
                $input
            );
        }
        return (function_exists("mb_substr") ? mb_substr($input,0,$length) : substr($input,0,$length));
    }

    final public static function pad_end(mixed $input, mixed $length, string $with = " ") {
        //procedure
        $length = intval($length);
        if (is_array($input)) {
            return array_map(
                fn($el) => (is_string($el) ? str_pad($el, $length, $with, STR_PAD_RIGHT) : $el),
                $input
            );
        }
        return is_string($input) ? str_pad($input, $length, $with, STR_PAD_RIGHT) : $input;
    }
    final public static function pad_start(mixed $input, mixed $length, string $with = " ") {
        //procedure
        $length = intval($length);
        if (is_array($input)) {
            return array_map(
                fn($el) => (is_string($el) ? str_pad($el, $length, $with, STR_PAD_LEFT) : $el),
                $input
            );
        }
        return is_string($input) ? str_pad($input, $length, $with, STR_PAD_LEFT) : $input;
    }
    final public static function pad_both(mixed $input, mixed $length, string $with = " ") {
        //procedure
        $length = intval($length);
        if (is_array($input)) {
            return array_map(
                fn($el) => (is_string($el) ? str_pad($el, $length, $with, STR_PAD_BOTH) : $el),
                $input
            );
        }
        return is_string($input) ? str_pad($input, $length, $with, STR_PAD_BOTH) : $input;
    }
    final public static function type(mixed $input, string $type = "string") {
        //"string", "array", "number", "boolean"
        switch ($type) {
            case "string" : {
                if (is_array($input)) {
                    $input = implode($input);
                } else {
                    $input = "".$input;
                }
            } break;
            case "integer" :
            case "number" : {
                if (is_numeric($input)) {
                    $input = intval($input);
                } else {
                    $input = 0;
                }
            } break;
            case "float" : {
                if (is_numeric($input)) {
                    $input = floatval($input);
                } else {
                    $input = 0.0;
                }
            } break;
            case "boolean" : {
                $input = in_array($input, [true, 1, '1', "true", "TRUE"], true);
            } break;
            case "array" : {
                if (is_string($input)) {
                    $input = explode(',', $input);
                }
                if (!is_array($input)) {
                    $input = [];
                }
            } break;
        }
        return $input;
    }

    final public static function transform_spaces(mixed $input, mixed $replace = ' ') {
        if (is_array($input)) {
            return array_map(
                fn($el) => (is_string($el) ? preg_replace("/\s+/u", $replace, $el) : $el),
                $input
            );
        }
        return is_string($input) ? preg_replace("/\s+/u", $replace, $input) : $input;
    }

    final public static function utf_names(mixed $input) {
        //Nice about that: https://stackoverflow.com/questions/63235334/how-to-validate-multilingual-names-in-php
        return self::strchars(
            $input,
            "\p{L}", 
            "\p{M}", 
            "0-9",
            "_",
            "-",
            "'",
            '"',
            ' ',
        );
    }
    
    final public static function strchars(mixed $input, ...$allowed) {
        //procedure
        if (!is_string($input) && !is_array($input)) 
            return $input;
        if (is_array($allowed)) {
            $allowed = array_map(fn($ch) => in_array($ch, ['-','/','[',']','\\','^', '.']) ? '\\'.$ch : $ch, $allowed);
        }
        $regex = is_string($allowed) ? sprintf('/[^%s]/u', $allowed) : sprintf('/[^%s]/u', implode($allowed));
        return preg_replace($regex, '', $input);
    }

    final public static function sanitize(mixed $input, string|int $type) {
        if (is_string($type)) 
            $type = intval($type);
        //procedure
        if (is_array($input)) {
            return array_map(
                function($value) use ($type) {
                    return is_string($value) ? 
                                (filter_var($value, intval($type)) ?: '') : 
                                FiltersCorePack::sanitize($value, $type);
                },
                $input
            );
        }
        return is_string($input) ? (filter_var($input, intval($type)) ?: "") : "";
    }

    final public static function lowercase(mixed $input) {
        //procedure
        if (is_array($input)) {
            return array_map(fn($el) => (is_string($el) ? strtolower($el) : $el), $input);
        }
        return is_string($input) ? strtolower($input) : $input;
    }

    final public static function uppercase(mixed $input) {
        //procedure
        if (is_array($input)) {
            return array_map(fn($el) => (is_string($el) ? strtoupper($el) : $el), $input);
        }
        return is_string($input) ? strtoupper($input) : $input;
    }

}


