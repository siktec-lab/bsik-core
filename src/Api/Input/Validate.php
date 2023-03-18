<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.3
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
1.0.2:
    -> now can load entire classes as packs of validators and filters
    -> added by default the CorePacks - may be removed in the future to be registered loaded dynamically.
1.0.3:
    -> Improved registering filters / validators - now accepts callable syntax and not only static notations.
/************************  Examples of registering  ****************************
Add custom validators Example:

class MyValidators {
    public static function start_with(string $input, string $letter = "") {
        if ($input[0] !== $letter[0] ?? "") {
            return "@input@ has to start with the letter {$letter[0]}";
        }
        return true;
    }
}
Validate::add_validator("startWith", "MyValidators::start_with");

or extend by using the entire class:
Validate::add_class_validator(new MyValidators);
*******************************************************************************/

namespace Siktec\Bsik\Api\Input;

use \Exception;

//Make sure we have a charset definition: 
if (!defined("PLAT_VALIDATION_INPUT_CHARSET")) {
    if (!defined("PLAT_CHARSET")) {
        define("PLAT_CHARSET", "UTF-8");
    }
    define("PLAT_VALIDATION_INPUT_CHARSET", PLAT_CHARSET);
}

/** 
 * Validate
 * 
 * - A simple validation class that can be used to validate inputs of the Api endpoints.
 * - Can be used to validate inputs of any kind.
 * - it implements a simple chain syntax to allow for easy validation and filtering.
 * 
 * @package Siktec\Bsik\Api\Input
 */
class Validate {

    //Use encoding:
    public static $encoding = PLAT_VALIDATION_INPUT_CHARSET;

    //Defined filters:
    private static $filters = [
    ];

    //Defined rules and there name:
    private static $validators = [
    ];
    
    //A buffer that holds conditions until create rule is called:
    private static $filter_buffer   = [];
    private static $rule_buffer     = [];

    //Rule string symbols:
    private static $sym_chain       = "->";
    private static $sym_args        = "::";
    private static $sym_args_glue   = ",,";
    
    /**
     * register_condition 
     * - adds a validate function to be use WARNING can overwrite the current defined.
     * @param  string $name
     * @param  mixed $func - should be a callable
     * @return void
     */
    final public static function add_filter(string $name, mixed $func) : void {
        if (is_callable($func, false, $method)) {
            self::$filters[$name] = $method;
        } else {
            trigger_error("tried to register a filter in ".__CLASS__."  that is not reachable or defined", \E_PLAT_WARNING);
        }
    }
    /**
     * add_class_filter
     * - registers an entire class methods as filters functions
     * must be static public methods, the methods names are used as the filter name.
     * @param object $class
     * @return void
     */
    final public static function add_class_filter(object $class) : void {
        $name    = get_class($class);
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            self::add_filter($method, [$name, $method]);
        }
    }
    /**
     * has_filter
     * check if a filter rule is available and registered
     * @param string $name
     * @return bool
     */
    final public static function has_filter(string $name) : bool {
        return isset(self::$filters[$name]);
    }
    
    /**
     * filter_input
     * executes a string procedure definition against the input 
     * this will return the input filtered.
     * @param  mixed $input
     * @param  string $procedures
     * @return mixed
     * @throws Exception => E_NOTICE when trying to use an unknown filter
     */
    final public static function filter_input(mixed $input, string $procedures) {
        $parsed_procedures = self::parse_rule($procedures);
        foreach ($parsed_procedures as $procedure) {
            if (!isset(self::$filters[$procedure["func"]]) || !is_callable(self::$filters[$procedure["func"]])) 
                throw new Exception("Trying to filter with unknown procedure [".$procedure["func"]."]", E_NOTICE);
            $input = call_user_func_array(self::$filters[$procedure["func"]], [$input, ...$procedure["args"]]);
        }
        return $input;
    }

    /**
     * register_condition - 
     * adds a validate function to be use - can overwrite the current defined.
     * 
     * @param  string $name
     * @param  string $func -> should be callable
     * @return void
     */
    final public static function add_validator(string $name, mixed $func) : void {
        if (is_callable($func, false, $method)) {
            self::$validators[$name] = $method;
        } else {
            trigger_error("tried to register a validator in ".__CLASS__."  that is not reachable or defined", \E_PLAT_WARNING);
        }
    }
    /**
     * add_class_validator
     * - registers an entire class methods as validators functions
     * must be static public methods, the methods names are used as the validator name.
     * @param object $class
     * @return void
     */
    final public static function add_class_validator(object $class) : void {
        $name    = get_class($class);
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            self::add_validator($method, [$name, $method]);
        }
    }
    
    /**
     * has_validator
     * check if a validation rule is available and registered
     * @param string $name
     * @return bool
     */
    final public static function has_validator(string $name) : bool {
        return isset(self::$validators[$name]);
    }
   
    /**
     * validate_input
     * executes a string rule definition against the input 
     * will fill the given message array with error messages.
     * @param  mixed $input
     * @param  string $rule
     * @param  array $messages
     * @return bool
     * @throws Exception => E_NOTICE when trying to use an unknown validator
     */
    final public static function validate_input(mixed $input, string $rule, array &$messages) : bool {
        $parsed_rule = self::parse_rule($rule);
        $valid = true;
        foreach ($parsed_rule as $condition) {
            if (!isset(self::$validators[$condition["func"]]) || !is_callable(self::$validators[$condition["func"]])) 
                throw new Exception("Trying to validate with unknown func [".$condition["func"]."]", E_NOTICE);
            $test = call_user_func_array(self::$validators[$condition["func"]], [$input, ...$condition["args"]]);

            //Skip is a special return result to break the chain:
            if ($test === "skip") {
                break;
            }
            //Check and set validation messages if needed:
            if ($test !== true) {
                $valid = false;
                $test = !is_array($test) ? [$test] : $test;
                $messages[$condition["func"]] = $test;
            }
        }
        return $valid;
    }
    
    /**
     * filter 
     * - add a filter rule that is being built
     * @param  string $cond - the specific condition name
     * @param  array $args - packed arguments that the rule should use.
     * @return self
     */
    final public static function filter(string $procedure, ...$args) {
        self::$filter_buffer[] = trim($procedure).(!empty($args) ? 
            self::$sym_args.implode(self::$sym_args_glue, $args) : 
            "");
        return __CLASS__;
    }

    /**
     * condition 
     * - add a condition to the rule that is being built
     * @param  string $cond - the specific condition name
     * @param  array $args - packed arguments that the rule should use.
     * @return self
     */
    final public static function condition(string $cond, ...$args) {
        self::$rule_buffer[] = trim($cond).(!empty($args) ? 
            self::$sym_args.implode(self::$sym_args_glue, $args) : 
            "");
        return __CLASS__;
    }
    
    /**
     * create_rule 
     * - packs all the conditions and return the rule definition string
     * @return string
     */
    final public static function create_filter() : string {
        $ret = implode(self::$sym_chain, self::$filter_buffer);
        self::$filter_buffer = [];
        return $ret;
    }   

    /**
     * create_rule 
     * - packs all the conditions and return the rule definition string
     * @return string
     */
    final public static function create_rule() : string {
        $ret = implode(self::$sym_chain, self::$rule_buffer);
        self::$rule_buffer = [];
        return $ret;
    }    
    /**
     * parse_rule 
     * - parse a rule definition string to its parts
     * @param string $rule - the rule definition string (generated from create_rule)
     * @return array - ["func" => "{condition name}", "args" => [ ... ]]
     */
    private static function parse_rule(string $rule) : array {
        $parts = explode(self::$sym_chain, $rule);
        $conditions = [];
        foreach ($parts as $part) {
            $definition = explode(self::$sym_args, $part);
            $args       = isset($definition[1]) ? explode(self::$sym_args_glue, $definition[1]) : [];
            $conditions[] = [
                "func" => $definition[0],
                "args" => $args
            ];
        }
        return $conditions;
    }
}

