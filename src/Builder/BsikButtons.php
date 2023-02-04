<?php

namespace Siktec\Bsik\Builder;

class BsikButtons {

    const BTN_CLASS = "btn";
    const BTN_COLOR_CLASS = "btn-%s";
    const BTN_SIZE_CLASS = "btn-%s";

    public static function button(
        string $id,
        string $text,
        string $color = "primary", 
        string $size = "",
        string $type = "button",
        array $attrs = [],
        array $classes = [],
        string $spinner = ""
    ) : string {
        
        $class = self::BTN_CLASS." ".self::_btn_size($size)." ".self::_btn_color($color)." ".implode(" ", $classes);
        $attrs = self::_attrs($attrs);
        $spinner = !empty($spinner) ? self::_spinner($spinner) : "";
        return <<<HTML
            <button type="{$type}" id="{$id}" class="{$class}" {$attrs}>
                {$spinner}
                {$text}
            </button>
        HTML;
    }
    
    private static function _spinner(string $type_size = "border-sm") {
        $type = explode('-', $type_size);
        return "<span class='spinner-{$type[0]} spinner-{$type_size}' role='status' aria-hidden='true' style='display:none'></span>";
    }

    private static function _attrs(array $attrs = []) {
        $html = "";
        foreach ($attrs as $attr => $value) {
            $html .= trim($attr)."='".htmlspecialchars($value)."'";
        }
        return $html;
    }

    private static function _btn_size(string $size) {
        return !empty($size) ? sprintf(self::BTN_SIZE_CLASS, $size) : "";
    }

    private static function _btn_color(string $color) {
        return !empty($color) ? sprintf(self::BTN_COLOR_CLASS, $color) : "";
    }

}
