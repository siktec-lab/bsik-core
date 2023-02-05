<?php
/**
 * FORCING OVERRIDE FLAGS:
 *  - BSIK_SHOW_ERRORS {Boolean}
 * 
 */
namespace Siktec\Bsik;

if (!defined('DS')) 
    define('DS', DIRECTORY_SEPARATOR);

if (!defined('ROOT_PATH')) 
    die("ROOT_PATH is not defined. Please define ROOT_PATH or include bsik.php first.");

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Storage\MysqliDb;
use \Siktec\Bsik\Objects\SettingsObject;

class CoreSettings {

    const DB_TABLES = [
        "settings"  => "bsik_settings",
        "modules"   => "bsik_modules",
        "users"     => "bsik_users",
        "roles"     => "bsik_users_roles"
    ];
    const SYSTEM_SETTINGS_KEY = "bsik-system";

    public static array $path = [
        "base"              => ROOT_PATH,
        "vendor"            => ROOT_PATH.DS."vendor",
        "autoload"          => ROOT_PATH.DS."vendor".DS."autoload.php",
        "logs"              => ROOT_PATH.DS."logs",
        "manage"            => ROOT_PATH.DS."manage",
        "manage-core"       => ROOT_PATH.DS."vendor".DS."siktec".DS."bsik".DS."src",
        "manage-lib"        => ROOT_PATH.DS."manage".DS."lib",
        "manage-modules"    => ROOT_PATH.DS."manage".DS."modules",
        "manage-trash"      => ROOT_PATH.DS."manage".DS."trash",
        "manage-pages"      => ROOT_PATH.DS."manage".DS."pages",
        "manage-templates"  => ROOT_PATH.DS."manage".DS."pages".DS."templates",
        "manage-cache"      => ROOT_PATH.DS."manage".DS."pages".DS."templates".DS."cache",
        "manage-blocks"     => ROOT_PATH.DS."manage".DS."pages".DS."blocks",
        "front"             => ROOT_PATH.DS."front",
        "front-global"      => ROOT_PATH.DS."front".DS."global",
        "front-errors"      => ROOT_PATH.DS."front".DS."errors",
        "front-pages"       => ROOT_PATH.DS."front".DS."pages"
    ];

    public static array $url = [ //We set those in load constants:
        "domain"     => "",
        "base"       => "", //folder path
        "full"       => "", //domain + base
        "manage"     => "",
        "manage-lib" => "",
    ];

    public static array $default_values = [
        // Paths: ---------------------------------------------------/
        "url-base-domain"                  => "http://localhost",
        "url-root-folder"                  => "bsik1",
        // Module: ---------------------------------------------------/
        "module-default-load"               => "dashboard",
        "module-default-load-view"          => "default",
        // Front: ---------------------------------------------------/
        "front-default-page"                => "home",
        // Apis: ---------------------------------------------------/
        "api-responses-with-debug-info"     => true,
        // Templates: ----------------------------------------------/
        "template-rendering-debug-mode"     => false,
        "template-rendering-auto-reload"    => true,
        // Manage: ---------------------------------------------------/
        "manage-logo"                       => "",
        "manage-logo-alt"                   => "BSIK",
        // Trace: ---------------------------------------------------/
        "trace-debug-expose"                => false,
        // Core: ---------------------------------------------------/
        "core-time-zone"                    => "Asia/Jerusalem",
        "core-session-lifetime"             => 86400,
        "core-expose-php-errors"            => true,
        "core-log-php-errors"               => true,
        "core-log-php-errors-folder"        => "logs",
        "core-log-php-errors-filename"      => "php_errors.log",
    ];
    public static array $settings_options = [
        "url-base-domain"                   => "string:notempty",
        "url-root-folder"                   => "string:notempty",
        "module-default-load"               => "string:notempty",
        "module-default-load-view"          => "string:notempty",
        "front-default-page"                => "string:notempty",
        "api-responses-with-debug-info"     => "boolean",
        "template-rendering-debug-mode"     => "boolean",
        "template-rendering-auto-reload"    => "boolean",
        "manage-logo"                       => "string",
        "manage-logo-alt"                   => "string",
        "trace-debug-expose"                => "boolean",
        "core-time-zone"                    => "string:notempty",
        "core-session-lifetime"             => "integer",
        "core-expose-php-errors"            => "boolean",
        "core-log-php-errors"               => "boolean",
        "core-log-php-errors-folder"        => "string:notempty",
        "core-log-php-errors-filename"      => "string:notempty",
    ];
    public static array $settings_descriptions = [
        "url-base-domain"                   => "The domain to use (include http/s://).",
        "url-root-folder"                   => "Path to folder from root (start with /).",
        "module-default-load"               => "Which module to load by default on manage.",
        "module-default-load-view"          => "Default view name to load by default.",
        "front-default-page"                => "Default frontend page to load.",
        "api-responses-with-debug-info"     => "Enable for api additional debug information.",
        "template-rendering-debug-mode"     => "Enable for twig template __DEBUG__ mode.",
        "template-rendering-auto-reload"    => "Enable for twig auto reload cache when edits are made.",
        "manage-logo"                       => "Manage platform logo (empty for default)",
        "manage-logo-alt"                   => "string",
        "trace-debug-expose"                => "boolean",
        "core-time-zone"                    => "Asia/Jerusalem",
        "core-session-lifetime"             => "86400",
        "core-expose-php-errors"            => "boolean",
        "core-log-php-errors"               => "boolean",
        "core-log-php-errors-folder"        => "path to folder from platform root",
        "core-log-php-errors-filename"      => "php_errors.log",
    ];
    public static ?SettingsObject $settings = null;
    
    public static function init() {
        self::$settings = new SettingsObject(
            self::$default_values,
            self::$settings_options,
            self::$settings_descriptions
        );
    }
    
    public static function settings() : SettingsObject {
        return self::$settings ?? new SettingsObject();
    }

    public static function get(string $key, mixed $default = null) {
        return self::$settings->get($key, $default);
    }

    public static function extend_from_database(MysqliDb $db) : bool {
        $saved = $db->where("name", self::SYSTEM_SETTINGS_KEY)
                    ->getValue(self::DB_TABLES["settings"], "object", 1);  
        return self::$settings->extend($saved);
    }

    public static function load_constants() {

        //Paths Constants:
        if (!defined("BSIK_ROOT")) 
            define("BSIK_ROOT", ROOT_PATH);

        if (!defined("BSIK_AUTOLOAD")) 
            define("BSIK_AUTOLOAD", self::$path["autoload"]);
        
        self::$path["logs"] = Std::$fs::path(ROOT_PATH, self::get("core-log-php-errors-folder", DS));

        //urls:
        self::$url["domain"] = trim(self::get("url-base-domain", "http://localhost"), "\\/ \t\n\r\0\x0B");
        self::$url["base"]   = "/".trim(self::get("url-root-folder", ""), "\\/ \t\n\r\0\x0B");
        self::$url["full"]   = self::$url["domain"].self::$url["base"];
        self::$url["manage"] = self::$url["full"]."/manage";
        self::$url["manage-lib"] = self::$url["manage"]."/lib";

        //Time zone and cookies:
        date_default_timezone_set(self::get("core-time-zone", ""));
        ini_set("session.gc_maxlifetime", self::get("core-session-lifetime", ""));
        ini_set("session.cookie_lifetime", self::get("core-session-lifetime", ""));
        
        //Expose errors:
        if (!defined("BSIK_SHOW_ERRORS")) {
            define("BSIK_SHOW_ERRORS", self::get("core-expose-php-errors", true));
        }
        ini_set('log_errors', self::get("core-log-php-errors", true));
        error_reporting(BSIK_SHOW_ERRORS ? -1 : 0); 
        ini_set('display_errors', BSIK_SHOW_ERRORS ? 'on' : 'off');
        ini_set('error_log', self::$path["logs"].DS.self::get("core-log-php-errors-filenam", "php_errors.log"));

    }
}