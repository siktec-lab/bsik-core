<?php

namespace Siktec\Bsik\Builder;

class BsikIcon {

    const ICON_TPL = "<i class='%s %s bsik-icon %s' %s></i>";
    const ICON_FAS = "fas";
    const ICON_FAB = "fab";
    const ICON_FAR = "far";
    const ICON_MAT = [
        "normal"    => "material-icons",
        "outlined"  => "material-icons-outlined",
        "rounded"   => "material-icons-rounded",
        "two-tone"  => "material-icons-two-tone",
        "sharp"     => "material-icons-sharp"
    ];
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

    /**
     * material
     * a material icon generator
     * for more info: https://material.io/resources/icons/?style=baseline
     *
     * @param string $type normal, outlined, rounded, two-tone, sharp
     * @param string $icon icon name
     * @param string $color #fff, rgb(255,255,255), rgba(255,255,255,0.5) or color name
     * @param string $size sm, md, lg, xlg
     * @param string $class additional class
     * @return string html
     */
    public static function material(
        string $type = "normal", //normal, outlined, rounded, two-tone, sharp
        string $icon = "info",
        string $color = "", // #fff, rgb(255,255,255), rgba(255,255,255,0.5) or color name
        string $size  = "", //sm, md, lg, xlg
        string $class = ""
    ) : string {
        return sprintf(
            "<span class='%s %s bsik-icon %s' %s>%s</span>", 
            self::ICON_MAT[$type] ?? self::ICON_MAT["normal"], 
            $size, 
            $class,
            !empty($color) ? "style='color:{$color}'" : "", 
            $icon
        );
    }
}