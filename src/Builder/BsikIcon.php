<?php

namespace Siktec\Bsik\Builder;

class BsikIcon {

    const ICON_TPL = "<i class='%s %s %s' %s></i>";
    const ICON_FAS = "fas";
    const ICON_FAB = "fab";
    const ICON_FAR = "far";
    const ICONS = [
        "cog"           => "fa-cog",
        "cogs"          => "fa-cogs",
        "user-cog"      => "fa-user-cog",
        "tasks"         => "fa-tasks",
        "file"          => "fa-file",
        "page"          => "fa-file",
        "upload"        => "fa-file-upload",
        "binoculars"    => "fa-binoculars",
        "scan"          => "fa-binoculars",
        "shield"        => "fa-shield-alt",
        "star"          => "fa-star",
        "users"         => "fa-user-friends",
    ];

    private static function icon(string $icon) : string {
        return self::ICONS[$icon] ?? $icon;
    }

    public static function fas(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAS, 
            self::icon($icon),
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }

    public static function far(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAR, 
            self::icon($icon),
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }
    
    public static function fab(string $icon, string $color = "", string $class = "") : string {
        return sprintf(self::ICON_TPL, 
            self::ICON_FAB, 
            self::icon($icon), 
            $class,
            !empty($color) ? "style='color:{$color}'" : ""
        );
    }
}