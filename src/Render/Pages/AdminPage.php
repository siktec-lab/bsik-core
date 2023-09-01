<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Render\Pages;

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Base;
use \Siktec\Bsik\Api\BsikApi;
use \Siktec\Bsik\Module\Modules;
use \Siktec\Bsik\Module\Module;
use \Siktec\Bsik\Render\Templates\Template;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\CoreSettings;
use \Siktec\Bsik\Users\User;

/**
 * AdminPage
 * The main admin page class.
 */
class AdminPage extends Base
{   

    public static AdminPage $ref;

    //The request object:
    public static AdminModuleRequest $request;

    //The template engine:
    public Template $engine;

    //Issuer privileges:
    public static Priv\PrivDefinition $issuer_privileges;

    //Loaded:
    public static Modules $modules;
    public static Module  $module;         //will hols an object that defines the loaded module 

    //For includes:
    private $static_links_counter = 0;
    public  $lib_toload = ["css" => [], "js" => []];
    public  $includes = array(
        "head"  => array("js" => array(), "css" => array()),
        "body"  => array("js" => array(), "css" => array())
    );

    public AdminPageMeta $meta;

    public string $custom_body_tag = "";

    //Menu:
    public $menu = [];
    
    //Page loaded values:
    public $platform_settings = [];
    public $platform_libs = [];

    /**
     * __construct
     * 
     * @param  bool $enable_logger - whether to enable logger
     * @param  string $logger_channel - the logger channel
     * @param  Priv\PrivDefinition $issuer_privileges - the issuer privileges
     * @return void
     */
    public function __construct(
        bool $enable_logger         = true,
        string $logger_channel      = "general",
        ?Priv\PrivDefinition $issuer_privileges = null
    ) {

        $this::$index_page_url = CoreSettings::$url["manage"];

        //Set logger:
        self::load_logger(
            path : CoreSettings::$path["logs"],
            channel: $logger_channel
        );

        //Set issuer privileges:
        self::$issuer_privileges = $issuer_privileges ?? new Priv\PrivDefinition();

        //Set admin platform core templates:
        $this->engine = new Template(
            cache : CoreSettings::$path["manage-cache"]
        );

        $this->engine->addFolders([
            CoreSettings::$path["manage-templates"]
        ]);

        //Initialize meta object:
        $this->meta = new AdminPageMeta();
        $this->meta->define([
            "lang"                  => "",
            "charset"               => "",
            "viewport"              => "",
            "author"                => "",
            "description"           => "",
            "title"                 => "",
            "icon"                  => "",
            "api"                   => $this::$index_page_url."/api/".self::$request->module,
            "module"                => self::$request->module,
            "module_sub"            => self::$request->which,
        ]);

        //Logger flag:
        self::$logger_enabled = $enable_logger;

        //Initialize Modules:
        self::$modules = Modules::init(self::$db);

        //Save ref:
        self::$ref = $this;
    }

    /**********************************************************************************************************
    /** FINALS:
     **********************************************************************************************************/

    final public static function error_page($code = 0, bool $exit = true) : void {
        //Load platform error pages:
        $code = in_array(intval($code), [404, 401, 403, 500]) ? $code : 404;
        self::log("notice", "error page [".$code."] load", [
            "request"   => $_SERVER['REQUEST_URI'],
            "method"    => $_SERVER['REQUEST_METHOD'],
            "remote"    => $_SERVER['REMOTE_ADDR']
        ]);

        //Sets the response http code:
        AdminPageHttpHeaders::send_response_code($code);
        include sprintf("pages/errors/%s.php", $code);
        if ($exit) 
            exit();
    }

    /**
     * load_request
     * - parses a request structure into the corresponding object which will be carried around the entire render process
     * @param  array $request_data => the request usually $_REQUEST, $_POST, $_GET
     * @return void
     */
    final public static function load_request(array $request_data = []) : void {
        self::$request = new AdminModuleRequest(empty($request_data) ? [] : $request_data);
        self::$request->type("module");
        self::$request->module(CoreSettings::get("module-default-load", ""));
        self::$request->which(CoreSettings::get("module-default-load-view", ""));
        self::$request->when();
    }


    public function load_settings(string $which = "global", bool $load_libs = true) {
        //TODO: this is old and not used set as new ObjectSettings and change the libs structure
        $set = self::$db->where("name", $which)->getOne("bsik_settings", ["object", "libs"]);
        if (!empty($set) && !empty($set["object"])) {
            $this->platform_settings = json_decode($set["object"], true);
        }
        if (!empty($set) && !empty($set["libs"]) && $load_libs) {
            $this->platform_libs = json_decode($set["libs"], true);
        }
    }
  
    /**
     * is_module_installed
     *
     * @param  string $name
     * @return bool
     */
    public function is_module_installed(string $name = "") : bool {
        return self::$modules::is_installed(
            empty($name) ? self::$request->module : $name
        );
    } 

    public function is_allowed_to_use_module(string $name = "", array &$messages = []) : bool {
        $module_name = empty($name) ? self::$request->module : $name;
        $module = self::$modules::module($module_name);
        if ($module) {
            return $module->priv->has_privileges(self::$issuer_privileges, $messages);
        }
        return false;
    }
    
    /**
     * load_module 
     * 
     * @return bool
     */
    public function load_module(string $module = "", string $which = "", ?BsikApi $Api = null, ?User $User = null) : bool {

        $module_name = empty($module) ? self::$request->module : $module;
        $which = empty($which) ? self::$request->which  : $which;

        try {
            //Load and initiate module:
            self::$module = $this::$modules::initiate_module(
                $module_name,
                $which,
                self::$db,
                $Api,
                $this,
                $User
            );
            if (!is_null(self::$module)) {
                //Set template origin:
                if (file_exists(self::$module->paths["templates"])) {
                    $this->engine->addFolders([
                        self::$module->paths["templates"]
                    ]);
                }
                return true;
            }
        } catch (\Throwable $e) {
            $origin = $e->getPrevious();
            self::log("error", $e->getMessage(), 
                context : [
                    "module" => $module_name,
                    "file"   => is_null($origin) ? $e->getFile() : $origin->getFile(),
                    "line"   => is_null($origin) ? $e->getLine() : $origin->getLine()
                ]
            );
        }
        return false;
    }

    public function load_menu() {
        //Parse definitions:
        foreach (self::$modules::get_all_installed() as &$module_name) {
            $definition = self::$modules::module_installed($module_name);
            $m = json_decode($definition["menu"], true);
            // if no menu definition:
            if (empty($m)) {
                continue;
            }

            // if has sub menu:
            if (!empty($m["sub"] ?? [])) {
                usort($m["sub"], fn($a, $b) => $a['order'] - $b['order']);
            }

            // register menu:
            $this->menu[] = $m;
        }

        //Sort menu:
        usort($this->menu, fn($a, $b) => $a['order'] - $b['order']);
    }
    
    /**
     * include - used by system and also by user for loading libs after parsed:
     *
     * @param  string $pos - the position -> head, body
     * @param  string $type - the lib type -> css, js
     * @param  string $name - the lib name
     * @param  array  $set - lib definition -> ["name", "version"]
     * @param  string $add - optional append to link
     * @return object
     */
    public function include(string $pos, string $type, string $name, array $set, string $add = "") {
        /* SH: added - 2021-03-03 => convert this to db error logging  */
        if (!is_string($pos) || !isset($this->includes[$pos])) {
            trigger_error("'Page->include' first argument ($pos) is unknown pos value", \E_PLAT_WARNING);
            return $this;
        }
        if (!is_string($type) || (strtolower($type) !== "js" && strtolower($type) !== "css")) {
            trigger_error("'Page->include' second argument ($type) must be a valid type argument - js | css.", \E_PLAT_WARNING);
            return $this;
        }
        $path = $set["name"] ?? "";
        if (BsikStd\Strings::starts_with($name,"link") || BsikStd\Strings::starts_with($name,"path")) {
            $path = $path;
            $name = $name[0] == 'l' ? "link" : "path"; 
        } else {
            $name = $name;
            $path = $set["version"] ?? "";
        }
        $this->includes[$pos][$type][] = ["name" => $name ,"path" => $path, "add" => $add];
        return $this;
    }

    public function include_asset($pos, $type, $in, $path) {
        switch ($in) {
            case "me": {
                $url = BsikStd\FileSystem::path_url(self::$module->urls["lib"], ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
            case "global": {
                $url = BsikStd\FileSystem::path_url(CoreSettings::$url["manage-lib"], ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
            case "required": {
                $url = BsikStd\FileSystem::path_url(CoreSettings::$url["manage-lib"], "required", ...$path);
                $this->include($pos, $type, "link", ["name" => $url]);
            } break;
        }
    }
    /**
     * parse_lib_query - parse a lib name to components for version control
     *
     * @param  string $lib_query - the lib name ex. libname:+3.3.0
     * @param  mixed $pos        - where to include -> head, body
     * @return void
     */
    private static function parse_lib_query(string $lib_query, string $pos = "") : array {
        $lib = explode(':', $lib_query);
        $place = in_array($lib[0], ["required", "lib", "install", "ext"]) ? $lib[0] : false;
        if (!$place) return [];
        $path = $lib[1] ?? "";
        return [
            "path" => $path,
            "place" => $place,
            "pos" => $pos
        ];
    }

    /**
     * load_json_libs - loads cms based define libs that are stored as special json object
     * 
     * @param  string $libs_json -> the json representation
     * @return void
     */
    private function load_libs_object(array $libs) {
        //Parse each lib:
        foreach ($libs as $key => $inpos_lib) {
            $pos = $key;
            foreach ($inpos_lib as $type_libs) {
                $type = $type_libs["type"];
                foreach ($type_libs["libs"] as $lib) {
                    if (BsikStd\Strings::starts_with($lib,"//") || BsikStd\Strings::starts_with($lib,"http")) {
                        $this->static_links_counter++;
                        $this->lib_toload[$type]["link".$this->static_links_counter] = ["name" => $lib, "pos" => $pos];
                    } else {
                        $lib_obj = self::parse_lib_query($lib, $pos);
                        if (!empty($lib_obj)) {
                            $this->static_links_counter++;
                            switch ($lib_obj["place"]) {
                                case "required": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => CoreSettings::$url["manage-lib"]."/required/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                                case "lib": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => CoreSettings::$url["manage-lib"]."/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                                case "module": {
                                    $this->lib_toload[$type]["path".$this->static_links_counter] = [
                                        "name" => CoreSettings::$url["manage-lib"]."/".$lib_obj["path"],
                                        "pos"  => $lib_obj["pos"]
                                    ];
                                } break;
                            }
                        }
                    }
                }
            }
        }
    }
        
    /**
     * load_libs - loads predefined libs by the cms
     *
     * @param  bool $template - whether to load template default libs?
     * @param  bool $page - whether to load page specific libs?
     * @return int
     */
    public function load_libs(bool $global) : int {
        
        //Build libs:
        if ($global && !empty($this->platform_libs)) 
            $this->load_libs_object($this->platform_libs);
        // if ($page && isset($this->definition["libs"])) 
        //     $this->load_json_libs($this->definition["libs"]);
    
        //Add via include method:
        foreach($this->lib_toload as $type => $libs)
            foreach($libs as $name => $set)
                $this->include($set["pos"], $type, $name, $set);
    
        return count($this->lib_toload["css"]) + count($this->lib_toload["js"]);
    }
    
    /** 
     * Set and Gets a custom body tag <body *******>.
     * @param mixed $set => if false will return current.
     * @return mixed
    */
    public function body_tag($set = false) {
        if (!$set) return $this->custom_body_tag;
        $this->custom_body_tag = $set;
        return $this;
    }    
    
    /******************************  RENDER METHODS  *****************************/
    
    private function render_inline_error(string $text, string $icon = "fa-exclamation-triangle") : string {
        $styles = [
            "color:lightcoral",
            "padding:15px",
            "clear:both",
            "display: block;"
        ];
        $icon_html = "<i class='fas $icon'></i>";
        return sprintf("<span style='%s'>%s&nbsp;%s</span>", implode(';', $styles), $icon_html, $text);
    }

    public function render_libs(string $type, string $pos, $print = true) : string {
        $tpl = [
            "css"       => '<link rel="stylesheet" href="%s" />'.PHP_EOL,
            "js"        => '<script type="text/javascript" src="%s"></script>'.PHP_EOL,
            "module"    => '<script type="module" src="%s"></script>'.PHP_EOL
        ];
        $use = $tpl[$type];
        $buffer = "";
        if (!$print) ob_start();
        foreach ($this->includes[$pos][$type] ?? [] as $lib) {
            if (is_array($lib["path"])) {
                array_walk($lib["path"], function($p) use($use, $tpl) {
                    if (strpos($p,".module.")) {
                        printf($tpl["module"], $p);
                    } else {
                        printf($use, $p);
                    }
                });
            } else {
                if (strpos($lib["path"],".module.")) {
                    printf($tpl["module"], $lib["path"]);
                } else {
                    printf($use,$lib["path"]);
                }
            }
        }
        if (!$print) {
            $buffer = ob_get_contents(); 
            ob_end_clean();
        }
        return $buffer;
    }
    
    /**
     * render_favicon
     * the system expects 4 files ad the path folder:
     *  - apple-touch-icon.png
     *  - favicon-32x32.png
     *  - favicon-16x16.png
     *  - site.webmanifest
     * @param  string $path - path to the folder with favicons
     * @param  string $name - naming scheme of favicons
     * @return void
     */
    public function render_favicon(string $path, string $name = "favicon") {
        $tpl = '<link rel="apple-touch-icon" sizes="180x180" href="%1$s/apple-touch-icon.png">'.PHP_EOL.
        '<link rel="icon" type="image/png" sizes="32x32" href="%1$s/%2$s-32x32.png">'.PHP_EOL.
        '<link rel="icon" type="image/png" sizes="16x16" href="%1$s/%2$s-16x16.png">'.PHP_EOL.
        '<link rel="manifest" href="%1$s/site.webmanifest">'.PHP_EOL;
        printf($tpl, $path, $name);
    }

    public function render_module(string $view_name = "", array $args = []) {
        //all needed info:
        try {
            //Load module & views:
            if (self::$module) {

                //Render the module view:
                [$status, $content] = self::$module->render(
                    view_name   : $view_name, //Empty view name will render current one.
                    args        : $args,
                    issuer      : self::$issuer_privileges
                );
                //make sure that content is string:
                if (!is_string($content)) 
                    throw new \Exception("error rendering view [".self::$module->module_name."->".self::$module->which."] - view returned non printable content.", \E_PLAT_ERROR);
                //Return the view content:
                if ($status) {
                    return $content;
                } else {
                    return $this->render_inline_error(text : $content);
                }
            } else {
                throw new \Exception("tried to render module from APage - without loading a module.", \E_PLAT_ERROR);
            }
        } catch (\Throwable $e) {
            self::log("error", "Error captured on module render [{$e->getMessage()}].", [
                "module"    => self::$module->module_name, 
                "view"      => self::$module->which, 
                "file"      => $e->getFile(),
                "line"      => $e->getLine()
            ]);
            return $this->render_inline_error(text:"Error in module - check logs.");
        }
    } 
    
    public function render_block(string $name, string $class, array $args = []) {
        $path = CoreSettings::$path["manage-blocks"].DS.$name.".block.php";
        if (file_exists($path)) {
            include $path;
            $ref = new \ReflectionClass($class);
            /** @var \Block $Block */
            $Block = $ref->newInstanceArgs([$this, $this->engine, $args]);
            return $Block->render();
        } else {
            trigger_error("Tried to rended and undefined / reachable block [".$path."]", \E_PLAT_WARNING);
        }
    }

    public function render_template($name, array $args = []) {
        return $this->engine->render($name, $args);
    }
}
