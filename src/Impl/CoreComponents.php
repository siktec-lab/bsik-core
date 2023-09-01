<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Builder\Components;
use \Siktec\Bsik\Builder\BsikButtons;
use \Siktec\Bsik\Render\Templates\Template;
use \Siktec\Bsik\Objects\SettingsObject;

/**
 * helloworld - a helloworld component.
 * @param string $name
 * @return string
 */
Components::register("helloworld", function(string $name) {
    return "Hello {$name}";
});

/**
 * html_ele - Builds an html element defined by a selector.
 * @param string $selector
 * @param array $add_attrs
 * @param string $content
 * @return array [string openTag, string content, string closingTag]
 */
Components::register("html_ele", function(string $selector = "div", array $add_attrs = [], string $content = "") {
    $parts = explode(".", $selector);
    $tag   = explode("#", array_shift($parts));
    $id    = $tag[1] ?? null;
    $tag   = $tag[0];
    $attrs = [];

    // has classes:
    $attrs["class"] = !empty($parts) ? $parts : [];
    if (isset($add_attrs["class"])) {
        array_push($attrs["class"], ...explode(".",$add_attrs["class"]));
        unset($add_attrs["class"]);
    }

    // has id:
    if (!empty($id)) $attrs["id"] = $id;
    
    //merge additional attributes:
    $attrs = array_merge($attrs, $add_attrs);
    $attrs_str = [];
    foreach ($attrs as $a => $value) {
        if (!empty($value))
            $attrs_str[] = $a.'="'.(is_array($value) ? implode(" ", $value) : $value).'"';
    }
    $ele = [sprintf("<%s %s>", $tag, implode(" ", $attrs_str)), $content , ""];
    if (!in_array($tag, [
        "meta","area","base","br","call","command","embed","hr",
        "img","input","keygen","link","param","source","track","wbr"
    ])) {
        $ele[2] = "</$tag>";
    }
    return $ele;
});

Components::register("html_select", function(
    string $selector, 
    array $options = [], 
    array $add_attrs = [], 
    int|string|null $selected = null
) {
    
    if (!BsikStd\Strings::starts_with($selector, "select"))
        $selector = "select.".$selector;
    $tags = Components::html_ele($selector, $add_attrs);
    $select = [$tags[0]];
    foreach ($options as $value => $text) {
        if (!is_null($selected) && $selected === $value) {
            $option = Components::html_ele("option", 
                ["value" => $value, "selected" => "selected"], $text);
        } else {
            $option = Components::html_ele("option", ["value" => $value], $text);
        }
        
        $select[] = implode($option);
    }
    $select[] = $tags[2];
    return implode($select);

});

/**
 * title - Builds an basic title element.
 * @param string    $text  => element text.
 * @param int       $size  => 1 - 7 the H ele size
 * @param array    $attrs => optional attributes to add.
 * @return string 
 */
Components::register("title", function(string $text, int $size = 2, array $attrs = []) {
    return implode(Components::html_ele("h{$size}", $attrs, $text));
});


/** Alert Component html renderer.
 *  @param string $text     => any string to be added - will not escape HTML
 *  @param string $color    => color of the modal use naming convention.
 *  @param string $icon     => icon classes such as 'fas fa-user'.
 *  @param bool $dismiss    => dismissible alert?.
 *  @param array $classes   => array of class names to append to the alert DIV element.
 *  @return string          => HTML of the dropdown
*/
Components::register("alert", function(
    string $text = "alert message", 
    string $color = "",
    string $icon = "",
    bool $dismiss = false,
    array  $classes = [],
) {
    $tmpl = '<div class="alert %s" role="alert">
                <span class="bg-icon">%s</span>
                %s
                %s
            </div>';
    if (!empty($icon))  array_unshift($classes, "add-icon");
    if ($dismiss)       array_unshift($classes, "alert-dismissible");
    array_unshift($classes, "alert-".(!empty($color) ? trim($color) : "light"));
    
    return sprintf($tmpl,
        implode(" ", $classes),
        !empty($icon) ? '<i class="'.$icon.'"></i>' : "", 
        $text,
        $dismiss ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : "",
    );
});


/** Loader builder of two types - spinner, grow.
 * @param string $color = "primary"....,
 * @param string $size 
 * @param string $align
 * @param bool   $show  => default show?
 * @param string $type  => border | grow,
 * @param string $text  => the hidden text fallback    
 * @return string       => HTML of the dropdown
*/
Components::register("loader", function(
    string $class = "",
    string $color = "primary",
    string $size  = "md",
    string $align = "center",
    bool   $show  = true,
    string $type  = "border",
    string $text  = "Loading..."    
) {
    $type = !in_array($type, ["border", "grow"]) ? "border" : $type;
    $size = !in_array($size, ["sm", "md", "lg"]) ? "md" : $size;
    $align = !in_array($align, ["start", "end", "center"]) ? "center" : $align;
    $color = !empty($color) ? "text-".$color : "";
    $hide = $show ? "" : "d-none";
    return <<<HTML
        <div class="d-flex justify-content-{$align} {$class} {$hide}" style="">
            <div class="spinner-{$type} spinner-{$type}-{$size} {$color} " role="status">
                <span class="visually-hidden">{$text}</span>
            </div>
        </div>
    HTML;
});

/** Modal Component.
 *  @param string       $id => the modal unique id
 *  @param string       $title => the modal title - can be HTML too.
 *  @param string|array $body => the modal body - can be HTML too.
 *  @param string|array $footer => the modal footer - can be HTML too.
 *  @param array        $buttons => the modal main buttons - expect button structure with html_ele structure.
 *  @param array        $set => the modal set attributes - [backdrop, keyboard, close, size].
 *  @return string => HTML of the modal
*/
Components::register("modal", function(
    string          $id, 
    string          $title = "Modal", 
    string|array    $body = "", 
    string|array    $footer = "", 
    array           $buttons = [], 
    array           $set = []
) {

    //Create buttons:
    $btns = [];
    foreach ($buttons as $button) {
        if (count($button) < 4 || empty($button[0]) || !is_string($button[0])) {
            continue;
        }
        if (!isset($button[1]["type"]) && BsikStd\Strings::starts_with($button[0], "button"))
            $button[1]["type"] = "button";
        if (!isset($button[1]["data-bs-dismiss"]) && $button[3])
            $button[1]["data-bs-dismiss"] = "modal";
        $btns[] = implode(Components::html_ele($button[0], $button[1], $button[2]));
    }
    $btns = implode('', $btns);
    //Backdrop:
    $backdrop = "";
    if (isset($set["backdrop"])) {
        $backdrop = "data-bs-backdrop='{$set["backdrop"]}'";
    }
    //keyboard:
    $keyboard = "";
    if (isset($set["keyboard"])) {
        $keyboard = "data-bs-keyboard='{$set["keyboard"]}'";
    }
    //close general:
    $close = "";
    if ($set["close"] ?? false) {
        $close = '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    }
    if ($set["close-white"] ?? false) {
        $close = '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>';
    }
    //size:
    $size = "";
    if ($set["size"] ?? false) {
        $size = "modal-{$set["size"]}";
    }
    //body and footer:
    $body   = is_array($body) ? implode(PHP_EOL, $body) : $body;
    $footer = is_array($footer) ? implode(PHP_EOL, $footer) : $footer;
    //Render:
    return <<<HTML
        <div class="bsik-modal modal fade" id="{$id}" {$backdrop} {$keyboard} tabindex="-1" aria-labelledby="{$id}Label" aria-hidden="true">
            <div class="modal-dialog {$size}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="{$id}Label">{$title}</h5>
                        {$close}
                    </div>
                    <div class="modal-body">
                        {$body}
                    </div>
                    <div class="modal-footer">
                        {$footer}
                        {$btns}
                    </div>
                </div>
            </div>
        </div>
    HTML;
});

/** Confirm Modal Component.
 *  @return string => HTML of the modal
*/
Components::register("confirm", function(){
    $confirm_modal = Components::modal(
        "bsik-confirm-modal", 
        "Confirmation required",
        "Confirmation body",
        "",
        [
            ["button.confirm-action-modal.btn.btn-secondary",    [],     "Confirm", false],
            ["button.reject-action-modal.btn.btn-primary",      [],     "Cancel", false],
        ],
        [
            "close"    => false,
            "size"     => "md",
            "backdrop" => "static",
            "keyboard" => "false",
        ]
    );
    return $confirm_modal;
});

/** Global dynamic table options.
*/
Components::register("defaults_dynamic_table", [ //NULL omits field option
    "visible"           => true,
    "field"             => null,        // Field name in db
    "title"             => null,        // Display Title string 
    "sortable"          => false,       // Set sort control
    "searchable"        => true,        // Set search control
    "rowspan"           => 1,           // Header rowspan
    "colspan"           => 1,           // Header colspan
    "align"             => "center",    // Align header
    "valign"            => 'middle',    // Vertical align header
    "clickToSelect"     => true,        // Enable / Disable header clickable
    "checkbox"          => false,       // Add row select checkbox
    "events"            => null,        // Events are sent to? a function.
    "formatter"         => null,        // Column formatter function
    "footerFormatter"   => null         // Footer Formatter function
]);

/** dynamic_table Component html renderer.
 *  @param string $id => table unique id
 *  @param string $ele_selector => the table element selector.
 *  @param array $option_attributes => all the tables options extends `defaults_dynamic_table`.
 *  @param string $api => api endpoint to get the results from.
 *  @param string $table => the database table or view name. 
 *                          use a format of [connection optional]:[table_name required]>[table_name * for count optional] 
 *                          to use a different connection.
 *  @param array  $fields => fields definition object.
 *  @param array $operations => main operations to attach to the table
 *  @return string => HTML of the table with js inline
*/
Components::register("dynamic_table", function(
    string $id, 
    string $ele_selector, 
    array  $option_attributes,
    string $api, 
    string $table, 
    array  $fields, 
    array  $operations = []
) {
    //Table js:
    $tpl_js   = "
        %s
        <script>
            document.addEventListener('DOMContentLoaded', function(event) {
                $('#%s').bootstrapTable({
                    ajax: function(params) {
                        params.data['fields'] = %s;
                        params.data['table_name'] = '%s';
                        Bsik.dataTables.get('%s', 'core.get_for_datatable', params);
                    },
                    columns: %s
                });
            });
            %s
        </script>".PHP_EOL;
    $tpl_operations = "function %s() { return '%s'; }";
    $operate_formatter_name = BsikStd\Strings::filter_string($id)."_operateFormatter";
    //Build columns:
    $columns = [];
    foreach ($fields as $field) {
        if (!empty($field["field"])) {
            $merged = array_merge(Components::defaults_dynamic_table(), $field);
            if ($field["field"] === "operate") {
                if ($merged["formatter"]) {
                    $tpl_operations = "function %s(value, row, index) { return {$merged["formatter"]}('%s', row, index); }";
                }
                $merged["formatter"] = $operate_formatter_name;
            }
            $columns[] = array_filter($merged,fn($v) => !is_null($v));
        }
    }
    $columns = json_encode($columns);
    //Build Operations:
    $eles_operation = [];
    foreach ($operations as $op) {
        $eles_operation[] = implode(Components::html_ele(
            "a.".($op["name"] ?? "notset"), 
            array_merge(["href" => "javascript:void(0)"], BsikStd\Arrays::filter_out($op, ["name", "href"])),
            implode(Components::html_ele("i", ["class" => $op["icon"] ?? "no-icon"]))
        ));
    }
    return sprintf(
        $tpl_js,
        implode(Components::html_ele($ele_selector, $option_attributes)).PHP_EOL,
        $id,
        json_encode(array_filter(array_column($fields, 'field'), fn($v) => $v !== "operate")),
        $table,
        $api,
        preg_replace('/"@js:([\w.]+)"/m', '$1', $columns), // Fixed a bug this allows to define object names and functions 
        sprintf($tpl_operations, $operate_formatter_name, implode('', $eles_operation))
    );
});

/** dropdown Component html renderer.
 *  @param array $buttons => a buttons (of the list) definition array that uses the html_ele structure
 *  @param string $text => the button open text.
 *  @param string $id => the id of the button that open the dropdown.
 *  @param array $class_main => array of class names to append to the main button.
 *  @param array $class_list => array of class names to append to the list of buttons.
 *  @return string => HTML of the dropdown
*/
Components::register("dropdown", function(
    array $buttons, 
    string $text = "dropdown", 
    string $id = "", 
    array $class_main = [], 
    array $class_list = []
) {
    $tmpl = '<div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle %s" type="button" id="%s" data-bs-toggle="dropdown" aria-expanded="false">
            %s
        </button>
        <ul class="dropdown-menu %s" aria-labelledby="%s">
            %s
        </ul>
    </div>';
    $buttons_html = '';
    foreach ($buttons as $b) {
        $buttons_html .= "<li>".implode(Components::html_ele(...$b))."</li>".PHP_EOL;
    }
    return sprintf($tmpl, 
        implode(" ", $class_main), 
        $id, 
        $text,
        implode(" ", $class_list),
        $id, 
        $buttons_html
    );
});

/**
 * summary_card - Build a summary card with icon and trend
 * @param string    $title  -> the title of the card
 * @param mixed     $number -> the number to show
 * @param string    $icon   -> the bg icon to show
 * @param string    $color  -> the color of the card
 * @param array     $trend  -> trend line to show expect ["dir" => {up, down}, "change", "text"]
 * @return string => HTML
 */
Components::register("summary_card", function(string $title, mixed $number, string $icon, string $color, mixed $trend = null) {
    $trend = "";
    if (!empty($trend) && is_array($trend)) {
        $trend = BsikStd\Arrays::get_from($trend, ["dir","change","text"], "");
        $trend = <<<HTML
            <p class="mt-3 mb-0 text-muted text-sm">
                <span class="text-danger mr-2"><i class="fas fa-arrow-{$trend['dir']}"></i>{$trend["change"]}</span>
                <span class="text-nowrap">{$trend["text"]}</span>
            </p>
        HTML;
    }
    return <<<HTML
        <div class="bsik-summary-card bsik-summary-card-color-{$color}"> 
            <div class="bsik-summary-card-body">
                <div class="bsik-summary-card-title">{$title}</div>
                <div class="bsik-summary-card-number">{$number}</div>
                <div class="bsik-summary-card-trend">
                    {$trend}
                </div>
            </div>
            <div class="bsik-summary-card-icon-bg">
                <i class="{$icon}"></i>
            </div>
        </div>
        <!-- <div class="card card-stats mb-4 mb-xl-0">
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title text-uppercase text-muted mb-0">{$title}</h5>
                        <span class="h2 font-weight-bold text-highlight mb-0">{$number}</span>
                    </div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-{$color} text-white rounded-circle shadow">
                            <i class="{$icon}"></i>
                        </div>
                    </div>
                </div>
                {$trend}
            </div>
        </div> -->
    HTML;
});

/**
 * button_card - a simple button card element.
 * @param string    $id         => button id
 * @param string    $label      => button label
 * @param string    $color      => container theme color
 * @param string    $btn_txt    => button text
 * @param string    $btn_color  => button theme color
 * @param string    $size       => button size - sm, md, lg ....
 * @param array     $attrs      => additional attrs
 * @param array     $classes    => additional classes
 * @return string => HTML
 */
Components::register("button_card", function(
    string $id, 
    string $label       = "label",
    string $color       = "primary", 
    string $btn_txt     = "button",
    string $btn_color   = "primary", 
    string $size        = "md", 
    array  $attrs     = [],
    array  $classes     = [],
) {
    $btn = BsikButtons::button($id, $btn_txt, $btn_color, $size, "button", $attrs, $classes);
    $tpl = "
        <div class='container comp-card-btn comp-card-btn-color-%s comp-card-btn-size-%s'>
            <div class='comp-card-btn-label'>%s</div>
            <div class='comp-card-btn-action'>
                %s
            </div>
        </div>
    "; 
    return sprintf($tpl, $color, $size, $label, $btn);
}, true);


Components::register("action_element", function(
    string $type    = "press",
    string $action  = "",
    string $text    = "",
    string $icon    = "",
    string $html    = "",
) {

    return sprintf(
        "<div class='action-element sik-form-init type-%s' data-action='%s'>
            %s
            <span class='action-label'>%s</span>
            %s
        </div>",
        BsikStd\Strings::escape($type),
        BsikStd\Strings::escape($action),
        $icon,
        BsikStd\Strings::escape($text),
        $html
    );

}, true);

Components::register("action_element_input", function(
    string $action      = "",
    string $text        = "",
    string $icon        = "",
    string $name        = "action-input",
    string $placeholder = "",
    string $input       = "text", // text, number, email, password, ...
    string|int|null $default = null,
    string $size        = "sm", // xs, sm, md, lg
) {

    $element = Components::action_element_stack(
        [[
            "type"          => "input",
            "name"          => $name,
            "input"         => $input,
            "default"       => $default,
            "placeholder"   => $placeholder,
            "size"          => $size
        ]]
    );

    return Components::action_element(
        "input",
        $action,
        $text,
        $icon,
        $element
    );

}, true);

Components::register("action_element_select", function(
    string $action      = "",
    string $text        = "",
    string $icon        = "",
    string $name        = "action-select",
    array  $options     = [],
    string|int|null $selected = null,
    string $size        = "sm", // xs, sm, md, lg
) {

    $element = Components::action_element_stack(
        [[
            "type"     => "select",
            "name"     => $name,
            "options"  => $options,
            "selected" => $selected,
            "size"     => $size
        ]]
    );

    return Components::action_element(
        "select",
        $action,
        $text,
        $icon,
        $element
    );

}, true);

Components::register("action_element_stack", function(array $controls = []) {

    $stack = [];
    foreach ($controls as $control) {
        
        // skip empty controls:
        if (is_string($control)) {
            $stack[] = $control;
            continue;
        } elseif (!is_array($control)) {
            continue;
        }
        // A size:
        $size = in_array($control["size"] ?? "", ["xs", "sm", "md", "lg"]) 
            ? $control["size"] 
            : "sm";

        // build control:
        switch ($control["type"]) {

            case "select": {
                $stack[] = Components::html_select(
                    "select.form-select.form-select-{$size}", 
                    $control["options"] ?? [], 
                    ["name" => BsikStd\Strings::escape($control["name"] ?? "action-select")],
                    $control["selected"] ?? null
                );
            } break;

            case "input": {
                $stack[] = implode(Components::html_ele(
                    "input.form-control.form-control-{$size}.input-carret", 
                    [
                        "name"          => BsikStd\Strings::escape($control["name"] ?? "action-input"),
                        "type"          => BsikStd\Strings::escape($control["input"] ?? "text"),
                        "placeholder"   => BsikStd\Strings::escape($control["placeholder"] ?? ""),
                        "value"         => BsikStd\Strings::escape($control["default"] ?? ""),
                    ]
                ));
            } break;
        }
    }
    return implode(PHP_EOL, $stack);

}, true);

Components::register("action_element_multi", function(
    string $action      = "",
    string $text        = "",
    string $icon        = "",
    array  $controls    = []
) {

    $elements = Components::action_element_stack(
        $controls
    );

    return Components::action_element(
        "multi",
        $action,
        $text,
        $icon,
        $elements
    );

}, true);

/**
 * action_bar - a simple flex action bar with data actions.
 * @param array     $actions    => all the actions to apply ["action" => "", "text" => "", "icon" => ""]
 * @param array     $colors     => inline colors to apply ["bg" => "transparent", "text" => "white", "icon" => "white"]
 * @param string    $class      => additional class name to apply
 * @return string => HTML
 */
Components::register("action_bar", function(
    array $actions = [], // ["action" => "", "text" => "", "icon" => ""]
    array $colors = [
        "bg"        => "transparent",
        "text"      => "white",
        "icon"      => "white"
    ],
    string $class = ""
) {
    $elements = [];
    foreach ($actions as $action) {
        
        // skip empty actions:
        if (empty($action)) continue;

        $element = "";
        $span    = 1;
        $state   = "";
        $state = $action["state"] ?? "";
        $state = is_array($state) ? implode(" ", $state) : $state;
        
        // if string action which means it's a separator or html:
        if (is_array($action)) {

            //Process the action element:
            $type = $action["type"] ?? "press";
            $span = intval($action["span"] ?? 1);
            
            switch ($type) {

                case "raw": {
                    $element = $action["html"] ?? "";
                } break;
                
                case "press": {
                    $element = Components::action_element(
                        "press",
                        $action["action"] ?? "none",
                        $action["text"] ?? "",
                        $action["icon"] ?? ""
                    );
                } break;
                case "input": {
                    $element = Components::action_element_input(
                        $action["action"] ?? "none",
                        $action["text"] ?? "",
                        $action["icon"] ?? "",
                        $action["name"] ?? "action-input",
                        $action["placeholder"] ?? "",
                        $action["input"] ?? "text",
                        $action["default"] ?? null,
                        $action["size"] ?? "sm"
                    );
                } break;
                case "select": {
                    $element = Components::action_element_select(
                        $action["action"] ?? "none",
                        $action["text"] ?? "",
                        $action["icon"] ?? "",
                        $action["name"] ?? "action-select",
                        $action["options"] ?? [],
                        $action["selected"] ?? null,
                        $action["size"] ?? "sm"
                    );
                } break;
                case "multi": {
                    $element = Components::action_element_multi(
                        $action["action"] ?? "none",
                        $action["text"] ?? "",
                        $action["icon"] ?? "",
                        $action["controls"] ?? []
                    );
                } break;
            }
        } elseif (is_string($action)) {
            $element = trim($action);
        }

        // add element to the list:
        if (!empty($element)) {
            $elements[] = sprintf(
                "<li class='action-item %s %s'>%s</li>", 
                $span > 1 ? "span-{$span}" : "",
                $state,
                $element
            );
        }
    }
    return sprintf(
        "<ul class='bsik-action-bar %s' style='%s %s'>%s</ul>",
        $class,
        !empty($colors["bg"])   ? "background-color:{$colors["bg"]};"   : "",
        !empty($colors["text"]) ? "color:{$colors["text"]};"            : "",
        implode(PHP_EOL, $elements)
    );

}, true);


/****************************************************************************/
/******** component to generat settings html form *******************/
/****************************************************************************/
/**
 * action_bar - a simple flex action bar with data actions.
 * @param SettingsObject $settings      => the settings object to render
 * @param array         $attrs          => additional attributes to append to the form
 * @param Template      $engine         => an engine to render
 * @param string        $template       => template name to use
 * @return string       => HTML
 */
Components::register("settings_form", function(
    SettingsObject $settings,
    array $attrs, 
    Template $engine, 
    string $template = "settings_form"
) {
    $diff = $settings->diff_summary();
    $parts = $settings->dump_parts(false, "values-merged", "options", "descriptions");
    $context = array_merge($parts, $diff);
    //Add special attributes:
    $context["attrs"] = $attrs;
    return $engine->render(
        name    : $template, 
        context : $context
    );
});
