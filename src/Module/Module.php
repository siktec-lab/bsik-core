<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Module;

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Storage\MysqliDb;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Objects\SettingsObject;
use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Render\Pages\AdminPage;
use \Siktec\Bsik\Users\User;

/**
 * Module
 * A module is a collection of views, settings, privileges and events that are
 * Installed and loaded dynamically to the system.
 * A module is a standalone component that can be installed and uninstalled and plugged into
 * the Bsik system.
 * 
 */
class Module {

    
    /**
     * @var string this module name
     */
    public string $module_name = ""; 

    /**
     * @var ModuleView|null the current view to be rendered
     */
    public ?ModuleView $current_view = null;

    /**
     * @var string the default view to be rendered if none is requested
     */
    private string $default_view = "";

    /**
     * @var array<string, ModuleView> all the defined views of this module
     */
    private array $views = [];

    /**
     * @var SettingsObject the module settings object
     */
    public SettingsObject $settings;

    /**
     * @var Priv\RequiredPrivileges|null holds all the module privileges definition
     */
    public ?Priv\RequiredPrivileges $priv = null;
    
    /**
     * @var array<ModuleEvent> all the module events that can be triggered
     */
    public array $module_events = [];

    /**
     * @var string the installed module version
     */
    public string $version = "";

    /**
     * @var string the path to the module folder
     */
    public string $path = "";

    /**
     * @var array<string, string> all the module paths of different parts
     */
    public array  $paths     = [
        "module"     => "",
        "main"       => "",
        "views"      => "",
        "api"        => "",
        "blocks"     => "",
        "templates"  => "",
        "lib"        => "",
        "includes"   => ""
    ];

    /**
     * @var string the url to the module folder
     */
    public string $url = "";

    /**
     * @var array<string, string> all the module urls of different parts
     */
    public array $urls = [ 
        "module"     => "",
        "main"       => "",
        "views"      => "",
        "api"        => "",
        "blocks"     => "",
        "templates"  => "",
        "lib"        => "",
        "includes"   => ""
    ];

    /**
     * @var string the requested view name to be loaded
     */
    public string $which = "";

    /**
     * @var array the menu entry of this module
     */
    public array $menu = [];

    /**
     * @var array holds the header data of this module
     */
    public array $header = []; 

    /**
     * @var AdminApi|null a reference to the top api object
     */
    public ?AdminApi $api = null;

    /**
     * @var AdminPage|null a reference Bsik AdminPage object which is the main page
     */
    public ?AdminPage $page = null;

    /**
     * @var MysqliDb|null a reference to the database connections object
     */
    public ?MysqliDb $db = null;

    /**
     * @var User|null a reference to the current user object
     */
    public ?User $user = null;

    /**
     * __construct
     * @param string $name the module name
     * @param Priv\RequiredPrivileges $privileges the module level required privileges
     * @param array $views expected views names that are allowed in this module
     * @param string $default default view name to be loaded if none is requested
     * @param SettingsObject $settings the module settings object
     */
    public function __construct(
        string $name, 
        ?Priv\RequiredPrivileges $privileges = null,
        array $views = [],
        string $default_view = "",
        ?SettingsObject $settings = null
    ) {
        $this->module_name = $name;
        $this->priv        = $privileges ?? new Priv\RequiredPrivileges();
        $this->define_views($views, $default_view);
        $this->settings    = $settings ?? new SettingsObject();
    }
    
    
    /**
     * path_part
     * returns a path part by name or the name itself if not found
     * @static
     * @param string $part
     * @return string
     */
    public static function path_part(string $part) : string {
        // Prebuilt paths:
        switch ($part) {
            case 'main':
                return "module.php";
            case 'views':
                return "views";
            case 'api':
                return "module-api.php";
            case 'blocks':
                return "blocks";
            case 'templates':
                return "templates";
            case 'lib':
                return "lib";
            case 'includes':
                return "includes";
        }
        return $part;
    }
    
    /**
     * build_paths
     * returns an array of paths and urls to the module parts given a base path
     * @static
     * @param  string $base
     * @return array
     */
    public static function build_paths(string $base) : array {
        
        // Prebuilt paths:
        $module     = BsikStd\FileSystem::path_to("modules", [$base]);
        $main       = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("main")]);
        $views      = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("views")]);
        $api        = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("api")]);
        $blocks     = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("blocks")]);
        $templates  = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("templates")]);
        $lib        = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("lib")]);
        $includes   = BsikStd\FileSystem::path_to("modules", [$base, self::path_part("includes")]);

        return [
            "paths" => [
                "module"    => $module["path"],
                "main"      => $main["path"],
                "views"     => $views["path"],
                "api"       => $api["path"],
                "blocks"    => $blocks["path"],
                "templates" => $templates["path"],
                "lib"       => $lib["path"],
                "includes"  => $includes["path"]
            ],
            "urls"  => [
                "module"    => $module["url"],
                "main"      => $main["url"],
                "views"     => $views["url"],
                "api"       => $api["url"],
                "blocks"    => $blocks["url"],
                "templates" => $templates["url"],
                "lib"       => $lib["url"],
                "includes"  => $includes["url"]
            ]
        ];
    }

    /**
     * load
     * loads the module with all its data
     * @param array $data the module data
     * @param MysqliDb|null $DB database connection object
     * @param AdminApi|null $Api the top api object
     * @param AdminPage|null $Page the top page object
     * @param User|null $User the current user object
     * @return void
     */
    public function load(
        array $data         = [], 
        ?MysqliDb $DB       = null, 
        ?AdminApi $Api      = null, 
        ?AdminPage $Page    = null, 
        ?User $User         = null
    ) : void {

        $this->version = $data["version"] ?? "";
        $this->which   = $data["which"] ?? "";
        $this->menu    = json_decode(($data["menu"] ?? "{}"), true);
        $this->db      = $DB;
        $this->api     = $Api;
        $this->page    = $Page;
        $this->user    = $User;

        //Set paths:
        $structure   = self::build_paths($data["path"] ?? "");
        $this->path  = $structure["paths"]["module"];
        $this->url   = $structure["urls"]["module"];
        $this->paths = $structure["paths"];
        $this->urls  = $structure["urls"];

        //Set requested view:
        $this->set_current_view($this->which);

    }

    /**
     * set_current_view
     * sets the current view to be rendered by name
     * if no name is given it will set the default view to be rendered
     * @param string $view_name the view name to be loaded
     * @return ModuleView
     */
    public function set_current_view(string $view_name = "") : ModuleView {
        $name = !empty($view_name) && $view_name !== "default" ? $view_name : $this->default_view;
        $this->current_view = $this->view($name);
        return $this->current_view;
    }

    /**
     * define_views
     * defines the views of this module by name and sets the default view
     * this method should be called before registering any views to the module
     * @param array $views the views names to be defined
     * @param string $default the default view name to be loaded if none is requested
     * @return void
     */
    public function define_views(array $views, string $default = "") : void {
        $this->views = array_fill_keys($views, null);
        $this->default_view = $default;
    }

    /**
     * register_view
     * registers a view to the module
     * @param ModuleView|null $view the view object to be registered
     * @param callable|null $render the render method of the view
     * @return void
     */
    public function register_view(
        ?ModuleView $view   = null,
        callable $render    = null
    ) : void {

        //Early out?
        if (is_null($view)) return;
        
        //check we can register:
        if (!array_key_exists($view->name, $this->views) || !is_callable($render)) {
            throw new \Exception("Trying to register an undefined / not-callable view [{$view->name}] in module [{$this->module_name}]", \E_PLAT_ERROR);
        }
        
        //Extend parent module privileges if it has specific privileges:
        $view->priv->extends($this->priv);
        
        //Set closure:
        $view->render = \Closure::bind(\Closure::fromCallable($render), $this);
        
        //Register:
        $this->views[$view->name] = $view;
    }

    /**
     * auto_register_views
     * automatically registers all the views in the module views folder
     * this method uses the view-{name}.php naming convention and native php is_file
     * its not slow because we rely on the stat() system call which is very fast and cached
     * by the OS and PHP
     * @param string|null $from the path to the views folder - if null it will use the default
     * @param string $only if not empty it will register only the view with this name this is useful to avoid registering all views
     * @return int the number of views registered
     */
    public function auto_register_views(string|null $from = null, string $only = "") : int {

        $folder = BsikStd\FileSystem::file_exists("modules", [$this->module_name, self::path_part("views")]);
        $registered = 0;
        
        if ($folder !== false) {

            $path = $folder["path"];
            $views = array_keys($this->views);

            // If only is set and its in the views array:
            if ($only && in_array($only, $views)) 
                $views = [$only];

            // Scan the directory for files. starts with view-{name}.php
            foreach ($views as $name) {

                // The view expected file:
                $view_file = $path.DIRECTORY_SEPARATOR."view-{$name}.php";

                // include it:
                if ($this->auto_register_view($view_file)) {
                    $registered++;
                }
            }
        }
        return $registered;
    }

    /**
     * auto_register_view
     * automatically registers a view by path if it exists
     * @param string $path
     * @return bool true if registered false otherwise
     */
    public function auto_register_view(string $path) : bool {
        if (is_file($path)) {
            include $path;
            return true;
        }
        return false;
    }

    /**
     * register_event
     * registers an event to the module
     * @param array $on_events the events names to be registered
     * @param callable|null $event_method the event method to be executed
     * @return void
     */
    public function register_event(array $on_events = [], ?\Closure $event_method = null) : void {
        $this->register_event_object(new ModuleEvent(
            $on_events, 
            \Closure::bind(\Closure::fromCallable($event_method), $this)
        ));
    }

    /**
     * register_event_object
     * registers an event object to the module
     * @param ModuleEvent|null $event the event object to be registered
     * @return void
     */
    public function register_event_object(?ModuleEvent $event = null) : void {
        //Early out?
        if (is_null($event)) return;
        //Save events:
        $this->module_events[] = $event;
    }

    /**
     * get_event
     * returns an event object by name
     * @param string $event_name the event name to be returned
     * @return ModuleEvent|null
     */
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

    /**
     * exec_event
     * executes an event by name
     * @param string $event_name the event name to be executed
     * @param array ...$args packed arguments to be passed to the event method
     * @return bool
     */
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

    /**
     * view
     * returns a view object by name
     * @param string $name the view name to be returned
     * @return ModuleView
     * @throws \Exception if view is not defined
     */
    public function view(string $name) : ModuleView {
        if (!array_key_exists($name, $this->views) || empty($this->views[$name])) {
            throw new \Exception("Trying to render undefined view [{$name}] in module", \E_PLAT_ERROR);
        }
        return $this->views[$name];
    }

    /**
     * render
     * renders a view by name and returns the result
     * @param string $view_name the view name to be rendered
     * @param array $args packed arguments to be passed to the view render method
     * @param Priv\PrivDefinition|null $issuer the issuer of the request
     * @return array
     * @throws \Exception may throw an exception when execution fails
     */
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
