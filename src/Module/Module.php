<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/

namespace Siktec\Bsik\Module;

use \Exception;
use \Siktec\Bsik\Storage\MysqliDb;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Objects\SettingsObject;
use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Render\Pages\AdminPage;
use \Siktec\Bsik\Users\User;

/** 
 * Module
 * 
 * This is the main module class, it holds all the module data and methods.
 * 
 * @package Bsik\Module
 * 
 */
class Module {

    public  string $module_name         = "";
    public  ?ModuleView $current_view   = null;
    private string $default_view        = "";
    private array  $views               = [];
    public  SettingsObject $settings;

    public ?Priv\RequiredPrivileges $priv = null;
    
    //Holds defined module events:
    public array $module_events = [];

    //Additional data from installation data - dynamically loaded:
    public string $version   = "";   // The installed module version:
    public string $path      = "";   // The path to the module folder
    public array  $paths     = [     // Some usefull paths of parts in the module: 
        "module"            => "",
        "module-api"        => "",
        "module-blocks"     => "",
        "module-templates"  => "",
        "module-lib"        => "",
        "module-includes"   => ""
    ];
    public string $url      = "";   // The url to the module folder
    public array  $urls     = [     // Some usefull paths of parts in the module: 
        "module"            => "",
        "module-api"        => "",
        "module-lib"        => ""
    ];
    public string $which    = "";   // Requested view to load
    public array  $menu     = [];   // the installed menu entry
    public array  $header   = [];   // dynamic header data
    public ?AdminApi $api   = null; // a reference to the use api object
    public ?AdminPage $page = null; // a reference to the use api object
    public ?MysqliDb $db    = null; //Reference to a $db connection dynamically assigned
    public ?User $user      = null;

    /**
     * __construct
     * @param string $name                      - the module name
     * @param Priv\RequiredPrivileges $privileges    - the module level required privileges
     * @param array $views                      - expected views names that are allowed in this module
     * @param string $default                   - default view name to be loaded if none is requested
     */
    public function __construct(
        string $name, 
        ?Priv\RequiredPrivileges $privileges = null,
        array $views         = [],
        string $default_view = "",
        ?SettingsObject $settings = null
    ) {

        $this->module_name = $name;
        $this->priv        = $privileges ?? new Priv\RequiredPrivileges();
        $this->define_views($views, $default_view);
        $this->settings    = $settings ?? new SettingsObject();
    
    }

    public function load(
        array $data         = [], 
        ?MysqliDb $DB       = null, 
        ?AdminApi $Api      = null, 
        ?AdminPage $Page    = null, 
        ?User $User         = null
    ) {

        $this->version = $data["version"] ?? "";
        $this->which   = $data["which"] ?? "";
        $this->menu    = json_decode(($data["menu"] ?? "{}"), true);
        $this->db      = $DB;
        $this->api     = $Api;
        $this->page    = $Page;
        $this->user    = $User;

        //Set paths:
        $raw_path = $data["path"] ?? "";
        $folder             = Std::$fs::path_to("modules", $raw_path);
        $module_api         = Std::$fs::path_to("modules", [$raw_path, "module-api.php"]);
        $module_blocks      = Std::$fs::path_to("modules", [$raw_path, "blocks"]);
        $module_templates   = Std::$fs::path_to("modules", [$raw_path, "templates"]);
        $module_lib         = Std::$fs::path_to("modules", [$raw_path, "lib"]);
        $module_includes    = Std::$fs::path_to("modules", [$raw_path, "includes"]);
        $this->path = $folder["path"];
        $this->paths["module"]              = $this->path;
        $this->paths["module-api"]          = $module_api["path"];
        $this->paths["module-blocks"]       = $module_blocks["path"];
        $this->paths["module-templates"]    = $module_templates["path"];
        $this->paths["module-lib"]          = $module_lib["path"];
        $this->paths["module-includes"]     = $module_includes["path"];
        $this->url = $folder["url"];
        $this->urls["module"]              = $this->url;
        $this->urls["module-api"]          = $module_api["url"];
        $this->urls["module-lib"]          = $module_lib["url"];

        //Set requested view:
        $this->set_current_view($this->which);

    }

    public function set_current_view(string $view_name = "") : ModuleView {
        $name = !empty($view_name) && $view_name !== "default" ? $view_name : $this->default_view;
        $this->current_view = $this->view($name);
        return $this->current_view;
    }

    public function define_views(array $views, string $default = "") : void {
        $this->views = array_fill_keys($views, null);
        $this->default_view = $default;
    }

    public function register_view(
        ?ModuleView $view   = null,
        callable $render    = null
    ) : void {
        //Early out?
        if (is_null($view)) return;
        //check we can register:
        if (!array_key_exists($view->name, $this->views) || !is_callable($render)) {
            throw new Exception("Trying to register an undefined / not-callable view [{$view->name}] in module [{$this->module_name}]", \E_PLAT_ERROR);
        }
        //Extend parent module privileges if it has specific privileges:
        $view->priv->extends($this->priv);
        //Set closure:
        $view->render = \Closure::bind(\Closure::fromCallable($render), $this);
        //Register:
        $this->views[$view->name] = $view;
    }

    public function register_event(array $on_events = [], ?\Closure $event_method = null) : void {
        $this->register_event_object(new ModuleEvent(
            $on_events, 
            \Closure::bind(\Closure::fromCallable($event_method), $this)
        ));
    }

    public function register_event_object(?ModuleEvent $event = null) : void {
        //Early out?
        if (is_null($event)) return;
        //Save events:
        $this->module_events[] = $event;
    }

    public function get_event(string $event_name) : ?ModuleEvent {
        //Early out?
        if (empty($this->module_events)) return null;
        //Loop and find event:
        foreach ($this->module_events as $module_event) {
            /** @var ModuleEvent $module_event */
            if (in_array($event_name, $module_event->on) && is_callable($module_event->method)) {
                return $module_event;
            }
        }
        return null;
    }

    public function exec_event(string $event_name, ...$args) : bool {
        $event = $this->get_event($event_name);
        if (!is_null($event)) {
            //We are trying to suppress all errors to make sure we are not killing the process:
            $try_exec = null;
            $try_mes  = "unknown";
            try {
                $try_exec = @call_user_func_array($event->method, [ $event_name, ...$args]);
            } catch(\Error $e) {
                $try_exec = false;
                $try_mes  = $e->getMessage();
            }
            if ($try_exec === false) {
                //This means we have a problem executing the method event log it:
                $this->page::log(
                    "error", 
                    "module event ['{$event_name}'] execution failed",
                    ["module" => $this->module_name, "error" => $try_mes]
                );
            }
            return true;
        }
        return false;
    }

    public function view(string $name) : ModuleView {
        if (!array_key_exists($name, $this->views) || empty($this->views[$name])) {
            throw new Exception("Trying to render undefined view [{$name}] in module", \E_PLAT_ERROR);
        }
        return $this->views[$name];
    }

    public function render(string $view_name = "", array $args = [], ?Priv\PrivDefinition $issuer = null) : array {

        //Get and Set current view only if needed get loaded one:
        if (!empty($view_name)) {
            $this->set_current_view($view_name);
        }

        //Check privileges: note that those view privileges are extended from module:
        $priv_messages = [];
        if (!$this->current_view->priv->has_privileges($issuer, $priv_messages)) {
            return [
                false, 
                sprintf("More privileges are required : %s", 
                        "<br />&emsp;->&nbsp;".implode("<br />&emsp;->&nbsp;", $priv_messages)
                )
            ];
        }
        //execute:
        return [true, call_user_func_array($this->current_view->render, $args)];
    }

}
