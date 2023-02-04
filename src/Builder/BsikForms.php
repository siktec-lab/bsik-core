<?php

namespace Siktec\Bsik\Builder;

class BsikForms {

    const FORM_CONTROL_CLASS    = "form-control";
    const FORM_LABEL_CLASS      = "form-label";
    const FORM_CONTROL_SIZE     = "form-control-%s";

    public static function label($for, $text) {
        return "<label for='".htmlspecialchars($for)."' class='".self::FORM_LABEL_CLASS."'>{$text}</label>";
    }

    public static function file(
        string $name, 
        string $id, 
        string $label = "", 
        string $size = "", 
        array $classes = [], 
        array $wrapper = [], 
        array $states = [], 
        array $attrs = []) {
        
        $label = !empty($label) ? self::label($id, $label) : "";
        $input_classes = self::FORM_CONTROL_CLASS." ".self::_form_control_size($size)." ".implode(" ", $classes);
        $wrapper = implode(" ", $wrapper);
        $states = implode(" ", $states);
        $attrs = self::_attrs($attrs);

        return <<<HTML
            <div class="{$wrapper}">
                {$label}
                <input class='{$input_classes}' type="file" id='{$id}' name='{$name}' {$attrs} {$states} />
            </div>
        HTML;
    }



    private static function _attrs(array $attrs = []) {
        $html = "";
        foreach ($attrs as $attr => $value) {
            $html .= trim($attr)."='".htmlspecialchars($value)."'";
        }
        return $html;
    }

    private static function _form_control_size(string $size) {
        return !empty($size) ? sprintf(self::FORM_CONTROL_SIZE, $size) : "";
    }
}
