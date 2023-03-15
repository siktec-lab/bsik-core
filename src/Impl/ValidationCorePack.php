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

class MyValidators {
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

use \Siktec\Bsik\Api\Input\Validate;

class ValidationCorePack {

    final public static function in_array($input, string $allowed) {
        return in_array($input, explode("|", $allowed)) ? true : "@input@ is not a valid input.";
    }

    final public static function one_of($input, string $allowed) {
        return self::in_array($input, $allowed);
    }

    final public static function start_with(string $input, string $start = "") {
        if (!str_starts_with($input, $start)) {
            return "@input@ has to start with {$start}";
        }
        return true;
    }

    final public static function ends_with(string $input, string $end = "") {
        if (!str_ends_with($input, $end)) {
            return "@input@ has to end with {$end}";
        }
        return true;
    }

    final public static function required(mixed $input) {
        //Conditions:
        if ($input === null || $input === "") {
            return "@input@ is required";
        }
        return true;
    }

    final public static function optional(mixed $input) {
        //return true if the input is set to continue chain:
        return empty($input) ? "skip" : true;
    }

    final public static function type(mixed $input, string $type = 'string') {
        $input_type = gettype($input);
        if (gettype($input) !== strtolower($type)) {
            return "@input@ is not of type '{$type}' seen '{$input_type}'";
        }
        return true;
    }
    final public static function min_length(string $input, string $min = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_min = (int)$min;
        //Conditions:
        if ($length < $parsed_min) {
            return "@input@ should be at least - {$min} characters long";
        }
        return true;
    }
    final public static function max_length(string $input, string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_max = (int)$max;
        //Conditions:
        if ($length > $parsed_max) {
            return "@input@ should be at least - {$max} characters long";
        }
        return true;
    }
    final public static function count(array $input, string $min = '0', string $max = '1') {
        $parsed_min = (int)$min;
        $parsed_max = (int)$max;
        $c = count($input);
        if ($c > $parsed_max || $c < $parsed_min) {
            return "@input@ array should be at least {$parsed_min} and maximum {$parsed_max} elements long";
        }
        return true;
    }
    final public static function length(string $input, string $min = '1', string $max = '1') {
        //Handle inputs:
        $length = mb_strlen($input, Validate::$encoding);
        $parsed_min = (int)$min;
        $parsed_max = (int)$max;
        //Conditions:
        if ($length < $parsed_min || $length > $parsed_max) {
            return "@input@ should be at least {$min} and maximum {$max} characters long";
        }
        return true;
    }
    final public static function min(string|int|float $input, string $min = '0') {
        //Handle inputs:
        $input = +$input;
        $min   = +$min;
        //Conditions:
        if ($input < $min) {
            return "@input@ should be greater or equal to - {$min}";
        }
        return true;
    }
    final public static function max(string|int|float $input, string $max = '0') {
        //Handle inputs:
        $input = +$input;
        $max   = +$max;
        //Conditions:
        if ($input > $max) {
            return "@input@ should be smaller or equal to - {$max}";
        }
        return true;
    }
    final public static function range(string|int|float $input, string|int|float $min = '0', string|int|float $max = '1') {
        //Handle inputs:
        $input  = floatval($input);
        $min    = floatval($min);
        $max    = floatval($max);
        //Conditions:
        if ($input < $min || $input > $max) {
            return "@input@ should be in range - {$min}, {$max}";
        }
        return true;
    }
    final public static function email(string $input) {
        //Conditions:
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return "@input@ is not a valid email address";
        }
        return true;
    }
}


