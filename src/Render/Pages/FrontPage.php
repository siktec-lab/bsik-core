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

namespace Siktec\Bsik\Render\Pages;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Base;
use \Siktec\Bsik\Render\Template;
use \Siktec\Bsik\Objects\SettingsObject;
use \Siktec\Bsik\Users\User;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Settings\CoreSettings;

class FrontPage extends Base {   

    /******************************************************
    /** Statics will be handled by the controller mostly  *
     ******************************************************/
    
    //The request object:
    public static FrontPageRequest $request;
    
    //Implemented pages:
    public static array $implemented_pages = [];
    
    //The template engine:
    public Template $engine;

    //The requesting user:
    public User $user;

    //The page policy -> normally set whn extending the page:
    static public Priv\RequiredPrivileges $page_policy;

    //Issuer privileges:
    public static Priv\PrivDefinition $issuer_privileges;

    //Paths: 
    public static array $paths = [
        "global-api"        => "/",
        "global-blocks"     => "/",
        "global-templates"  => "/",
        "global-lib"        => "/",
        "page"              => "/",
        "page-api"          => "/",
        "page-blocks"       => "/",
        "page-templates"    => "/",
        "page-lib"          => "/",
    ];

    //Loaded:
    public static $pages = [];
    public static $page;         //will hols an object that defines the loaded page 
    public static $page_type;

    // Components:
    public \Bsik\Builder\Components $components;

    // Page Status
    public FrontPageStatus $status;
    
    // For includes:
    private $static_links_counter = 0;
    public  $lib_toload = ["css" => [], "js" => []];
    public  $includes = array(
        "head"  => array("js" => array(), "css" => array()),
        "body"   => array("js" => array(), "css" => array())
    );
    
    public FrontPageMeta $meta;

    public string $custom_body_tag = "";
    
    // Menu:
    public $menu = [];

    // page merged settings:
    public static SettingsObject $settings;

    
    /**
     * __construct
     *
     * @param  string $user_str         => the usr string identifier for logging.
     * @param  string $logger_channel   => Logger channel to use.
     * @param  string $logger_stream    => The Logger directory path.
     * @return FPage
     */
    public function __construct(
        bool $enable_logger = true,
        ?User $user = null,
        ?Priv\RequiredPrivileges $policy = null
    ) {

        //Platform base root:
        self::$index_page_url  = CoreSettings::$url["full"];
        
        //Set policy:
        self::$page_policy = $policy ?? new Priv\RequiredPrivileges();
        
        self::$issuer_privileges = !is_null($user) ? $user->priv : new Priv\GrantedPrivileges();

        //Initialize meta object:
        $this->meta = new FrontPageMeta();
        
        //Logger flag:
        self::$logger_enabled = $enable_logger;
        
        //Set user:
        $this->user = $user;

        //Initiate settings object:
        self::$settings = new SettingsObject();
        self::load_settings("front-pages");

    }

    /**********************************************************************************************************
    /** FINALS:
     **********************************************************************************************************/
    /**
     * register_page
     * register a loaded page for dynamic execution:
     * 
     * @param  mixed $name
     * @param  mixed $class_name
     * @return void
     */
    final public static function register_page(string $name, string $class_name) {
        if (isset(self::$implemented_pages[$name])) {
            trigger_error("Tried to register an allready defined page", \E_PLAT_ERROR);
        }
        self::$implemented_pages[$name] = $class_name;
    }

    final public static function error_page($code = 0, bool $exit = true)
    {
        //Load platform error pages:
        $code = in_array(intval($code), [404, 401, 403, 500]) ? $code : 404;
        self::log("notice", "error page [".$code."] load", [
            "request"   => $_SERVER['REQUEST_URI'],
            "method"    => $_SERVER['REQUEST_METHOD'],
            "remote"    => $_SERVER['REMOTE_ADDR']
        ]);

        //Sets the response http code:
        FrontPageHttpHeaders::send_response_code($code);
        include sprintf("front/errors/%s.php", $code);
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
        self::$request = new FrontPageRequest(empty($request_data) ? [] : $request_data);
        self::$request->type("page");
        self::$request->page(CoreSettings::get("front-default-page", ""));
        self::$request->which("default");
        self::$request->when();
    }

    /**
     * fill_pages
     * - get all active pages from db: 
     * @return int
     */
    final public static function load_defined_pages() : int {
        // Get basic info needed:
        self::$pages = self::$db->where("status", FrontPageStatus::state_translate("active"))
                                ->map("name")
                                ->arrayBuilder()
                                ->join("page_types as b", "a.type = b.id", "LEFT")
                                ->get("page_all as a", null, [
                                    "a.*",
                                    "b.name as page_type"
                                ]);

        // Return count of loaded:                        
        return count(self::$pages);
    }  
    
    /**
     * 
     * isset_page
     *
     * @param  string|null $name
     * @return bool
     */
    final public static function isset_page(string|null $name = null) : bool {
        return isset(self::$pages[empty($name) ? self::$request->page : $name]);
    } 

    /**
     * load_page_record
     * load the page related definition and structure
     * 
     * @param  string|null $name => empty for request based page loading.
     * @return bool
     */
    final public static function load_page_record(string|null $name = null) : bool {

        //Override request?
        $name = empty($name) ? self::$request->page : $name;
        
        //Check if set:
        if (self::isset_page($name)) {

            //page record:
            self::$page      = self::$pages[$name];
            self::$page_type = self::$page["page_type"];

            return true;
        }        
        return false;
    }
    
    /**
     * load_paths
     * loads required paths of front pages folder structure
     * @param  array $global_dir
     * @param  array $page_dir
     * @return void
     */
    final public static function load_paths(array $global_dir = [], array $page_dir = []) {

        
        $global_folder = !empty( $global_dir ) ? Std::$fs::file_exists("root", $global_dir) : [];
        $page_folder   = !empty( $page_dir ) ? Std::$fs::file_exists("root", $page_dir) : [];

        if (!empty($global_folder)) {

            self::$paths["global-api"]              = $global_folder["path"].DS."api".DS."global-api.php";
            self::$paths["global-api-url"]          = $global_folder["url"]."/api/global-api.php";
            self::$paths["global-blocks"]           = $global_folder["path"].DS."blocks";
            self::$paths["global-blocks-url"]       = $global_folder["url"]."/blocks";
            self::$paths["global-templates"]        = $global_folder["path"].DS."templates";
            self::$paths["global-templates-url"]    = $global_folder["url"]."/templates";
            self::$paths["global-lib"]              = $global_folder["path"].DS."lib";
            self::$paths["global-lib-url"]          = $global_folder["url"]."/lib";

        } elseif ($global_folder === false) {
            trigger_error("front-pages global folder does not exists", \E_PLAT_WARNING);
        }

        if (!empty($page_folder)) {
            $dyn_folder_path = trim(self::$page["page_folder"], '/\\');
            $dyn_folder_url = Std::$url::normalize_slashes($dyn_folder_path);
            self::$paths["page"]                = $page_folder["path"].DS.$dyn_folder_path;
            self::$paths["page-url"]            = $page_folder["url"].'/'.$dyn_folder_url;
            self::$paths["page-api"]            = $page_folder["path"].DS.$dyn_folder_path.DS."page-api.php";
            self::$paths["page-api-url"]        = $page_folder["url"].'/'.$dyn_folder_url.'/'."page-api.php";
            self::$paths["page-blocks"]         = $page_folder["path"].DS.$dyn_folder_path.DS."blocks";
            self::$paths["page-blocks-url"]     = $page_folder["url"].'/'.$dyn_folder_url.'/'."blocks";
            self::$paths["page-templates"]      = $page_folder["path"].DS.$dyn_folder_path.DS."templates";
            self::$paths["page-templates-url"]  = $page_folder["url"].'/'.$dyn_folder_url.'/'."templates";
            self::$paths["page-lib"]            = $page_folder["path"].DS.$dyn_folder_path.DS."lib";
            self::$paths["page-lib-url"]        = $page_folder["url"].'/'.$dyn_folder_url.'/'."lib";
        } elseif ($page_folder === false) {
            trigger_error("front-pages page folder does not exists", \E_PLAT_WARNING);
        }
    }

    /**
     * get_page_priv
     * get an object with all required priv of the signed User
     * @param  string $name
     * @return mixed - Object with privileges attributes, NULL if not set
     */
    final public static function get_page_priv(string $name = null) {
        $name = empty($name) ? self::$request->page : $name;
        return isset(self::$pages[$name]) ? self::$pages[$name]["priv"] : null;
    }

    /**********************************************************************************************************
    /** PAGE BUILDING:
     **********************************************************************************************************/
    final public static function load_settings(string $which = "pages") {

        //Load platform globals:
        $global = self::$db->where("name", $which)->getOne("bsik_settings", ["object"]);
        $global = Std::$str::parse_json($global["object"] ?? "", onerror: []);
        Std::$arr::rename_key("values", "defaults", $global);
        
        //Load local if any:
        $local =  Std::$fs::get_json_file(
            Std::$fs::path_to("front-pages", [self::$page["page_folder"], "settings.jsonc"])["path"]
        ) ?? [];
        Std::$arr::rename_key("values", "defaults", $local);

        //Extend with page:
        self::$settings->import($global);
        self::$settings->import($local);
        self::$settings->extend(self::$page["settings"] ?? "");

    }

    /**
     * include - used by system and also by user for loading libs after parsed:
     *
     * @param  string $pos - the position -> head, body
     * @param  string $type - the lib type -> css, js
     * @param  string $name - the lib name
     * @param  array  $set - lib definition -> ["name", "version"]
     * @param  string $add - optional append to link
     * @return void
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
        if (Std::$str::starts_with($name,"link") || Std::$str::starts_with($name,"path")) {
            $path = $path;
            $name = $name[0] == 'l' ? "link" : "path"; 
        } else {
            $name = $name;
            $path = $set["version"] ?? "";
        }
        $this->includes[$pos][$type][] = ["name" => $name ,"path" => $path, "add" => $add];
        //return $this;
    }
    public function include_asset($pos, $type, $root, $path) {
        switch ($root) {
            case "core": {
                foreach ($path as $what) {
                    switch ($what) {
                        case "bsik": {
                            $this->include($pos, "js", "link", [
                                "name" => CoreSettings::$url["manage-lib"]."/js/front.module.js"
                            ]);
                        } break;
                        case "jquery": {
                            $this->include($pos, "js", "link", [
                                "name" => CoreSettings::$url["manage-lib"]."/required/jquery/jquery.min.js"
                            ]);
                        } break;
                        default: {
                            $this->include($pos, $type, "link", [
                                "name" => CoreSettings::$url["manage-lib"]."/".$what
                            ]);
                        }
                    }
                }
            } break;
            case "page":
            case "global": {
                $this->include($pos, $type, "link", ["name" => Std::$fs::path_url(self::$paths["$root-lib-url"], $type, ...$path)]);
            } break;
        }
    }
    /* Set and Gets a custom body tag <body *******>.
     *  @param $set => MIXED - String | False
     *  @Default-params: false
     *  @return MIXED - String | Object(this)
    */
    public function body_tag($set = false) {
        if (!$set) return $this->custom_body_tag;
        $this->custom_body_tag = $set;
        return $this;
    }    
        
    /******************************  RENDER METHODS  *****************************/

    public function render_libs(string $type, string $pos, $print = false) : string {
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

    public function render_block(string $placement, string $name, string $class, array $args = []) {
        $path = self::$paths[$placement."-blocks"].DS.$name.".block.php";
        if (file_exists($path)) {
            include $path;
            $ref = new \ReflectionClass($class);
            $Block = $ref->newInstanceArgs([$this, $this->engine, $args]);
            return $Block->render();
        } else {
            trigger_error("Tried to rended and undefined / reachable block [".$path."]", \E_PLAT_WARNING);
        }
    }

    public function render_template($name, array $args = []) {
        return $this->engine->render($name, $args);
    }
    
    public static function is_allowed(array &$messages = []) {
        if (!is_null(self::$page_policy)) {
            return self::$page_policy->has_privileges(self::$issuer_privileges, $messages);
        }
        return true;
    }
    public static function load_page($name, User $User) {
        if (!isset(self::$implemented_pages[$name])) {
            trigger_error("Tried to render an unknown implemented page", \E_PLAT_ERROR);
        } else {
            $ref = new \ReflectionClass(self::$implemented_pages[$name]);
            $Page = $ref->newInstanceArgs([true, $User]);
            return $Page;
        }
    }
}
