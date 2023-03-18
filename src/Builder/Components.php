<?php

namespace Siktec\Bsik\Builder;

use \Exception;

/**
 * @method static string helloworld( string $name ) return a hello world message
 * @method static array html_ele( string $selector, array $add_attrs, string $content ) builds a html element give the elemnet definition
 * @method static string title( string $text, int $size, array $attrs ) build a simple title element
 * @method static string alert( string $text, string $color, string $icon, bool $dismiss, array $classes ) build an alert element
 * @method static string loader( string $color, string $size, string $align, bool $show, string $type, string $text ) a loader spinner generator
 * @method static string modal( string $id, string $title, string|array $body, string|array $footer, array $buttons, array $set ) a modal generator
 * @method static string confirm() generate confirmation modal
 * @method static string dynamic_table( string $id, string $ele_selector, array $option_attributes, string $api, string $table, array $fields, array $operations ) generate html and js of dynamic table
 * @method static string dropdown( array $buttons, string $text = "dropdown", string $id = "", array $class_main = [], array $class_list = [] ) generates a dropdown element
 * @method static string action_bar( array $actions = [], array $colors, string $class = "" ) generates a action bar menu like elements
 */
class Components {

    private static $components = [];

    public static function register(string $name, $set, $protected = true) : void {
        if (isset(self::$components[$name]) && self::$components[$name]["protected"]) {
            throw new Exception("Tried to override a protected component", \E_PLAT_ERROR);
        }
        self::$components[$name] = [
            "cb"        => $set,
            "protected" => $protected
        ];
    }
    
    public static function register_once(string $name, $set, $protected = true) : void {
        if (self::is_registered($name)) {
            return;
        }
        self::$components[$name] = [
            "cb"        => $set,
            "protected" => $protected
        ];
    }

    public static function is_registered(string $name) : bool {
        return isset(self::$components[$name]);
    }

    public static function __callstatic($name, $arguments) {
        
        if (!isset(self::$components[$name])) {
            throw new Exception("Tried to use an undefined component", \E_PLAT_ERROR);
        }
        if (is_callable(self::$components[$name]["cb"])) {
            return call_user_func_array(self::$components[$name]["cb"], $arguments);
        }
        return self::$components[$name]["cb"];
    }

}
