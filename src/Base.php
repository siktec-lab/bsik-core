<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik;


use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Storage\MysqliDb;

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class Base {

    /**********************************************************************************************************
    /** GENERIC PAGE:
     **********************************************************************************************************/

    //page url reference:
    static $index_page_url = "";

    //User String:
    public static string $user_string;

    //Storage container:
    public array $storage = [];

    /* Storage used to save data and handle it safely.
     *  @param $name => String
     *  @param $data => Mixed
     *  @param $protect => Boolean
     *  @Default-params: protect - true
     *  @return Boolean
     *  @Examples:
     *      > $Page->store("test value", "dom.png");
    */
    public function store($name, $data, $protect = true) {
        if ($protect && isset($this->storage[$name])) {
            trigger_error("'Page->store' you are trying to override a protected storage member", \E_PLAT_WARNING);
            return false;
        }
        $this->storage[$name] = $data;
        if ($data === false || $data === null) return false;
        return true;
    }

    /* get method is used to retrieve stored data.
     *  @param $name => Boolean|String // if True return the entire storage array, otherwise return by name.
     *  @Default-params: None
     *  @return Mixed
     *  @Examples:
     *      > $Page->get(true);
     *      > $Page->get("key-name");
    */
    public function get($name = true, $default = "") {
        return $name === true ? $this->storage : $this->storage[$name] ?? $default;
    }

    //Additional globally assigned html:
    public $html_container = [];
    
    /**
     * additional_html
     * - saves html strings for appending at the end of the page
     * @param  string $html
     * @return void
     */
    public function additional_html(string $html) : void {
        $this->html_container[] = $html;
    }

     //The token:
    public static array $token = [
        "csrf" => "",
        "meta" => ""
    ];
    
    /**
     * set_user_string
     * - sets the user identifier string for logging.
     * @param  string $str
     * @return void
     */
    final public static function set_user_string(string $str) : void {
        self::$user_string = empty(trim($str)) ? "unknown" : trim($str);
    }

    /**
     * tokenize
     * - Get and Set the page token If not set create a new one.
     * @return void
     */
    final public static function tokenize() : void {
        if (empty(self::get_session("csrftoken")))
            self::create_session(["csrftoken" => bin2hex(random_bytes(32))]);
        self::$token["csrf"] = self::get_session("csrftoken");
        self::$token["meta"] = "<meta name='csrf-token' content=".self::$token["csrf"].">";
    }

    /**
     * csrf
     * - convenient method to get the csrf token
     * @return string
     */
    final public static function csrf() : string {
        return self::$token["csrf"] ?? "";
    }


    /**********************************************************************************************************
    /** LOGGER:
     **********************************************************************************************************/
    //Logger:
    public static Logger $logger;
    public static bool   $logger_enabled = true;
    
    /**
     * load_logger
     * - initialize a logger channel
     * @param  string $path
     * @param  string $channel
     * @return void
     */
    final public static function load_logger(string $path, string $channel = "page-general") : void {
        //Logger:
        self::$logger = new Logger($channel);
        self::$logger->pushHandler(new StreamHandler(
            BsikStd\FileSystem::path($path,$channel.".log")
        ));
        self::$logger->pushProcessor(function ($record) {
            $record['extra']['user'] = self::$user_string;
            return $record;
        });
    }
    
    /**
     * log
     * - safely logs to platform logs - affected by the enable log flag.
     * @param  string $type     => one of those types : "notice", "info", "error", "warning"
     * @param  string $message  => main message to log
     * @param  array $context   => context array for additional data
     * @return void
     */
    final public static function log(string $type, string $message, array $context) : void {
        if (
            in_array($type, ["notice", "info", "error", "warning"]) &&
            self::$logger_enabled
        ) {
            self::$logger->{$type}($message, $context);
        }
    }

    /**********************************************************************************************************
    /** GLOBAL CONFIGURATION:
     **********************************************************************************************************/
    public static array $conf;

    //Set configuration object:
    public static function configure( array $_conf) : void {
        self::$conf = $_conf;
    }


    /**********************************************************************************************************
    /** DATABASE:
     **********************************************************************************************************/    
    private const DB_DEFAULT_CREDENTIALS = [
        "host"      => "localhost", 
        "port"      => "3306", 
        "db"        => null, 
        "username"  => null, 
        "password"  => null,
        "charset"   => "utf8mb4",
        "socket"    => null
    ];
    public static ?MysqliDb $db = null;
    
    /**
     * db_credentials
     * prepare db credentials array 
     * expected keys: host, port, name, user, pass, charset, socket
     * @param  array $credentials
     * @return array
     */
    private static function db_credentials(array $credentials = []) : array {
        return BsikStd\Arrays::extend(self::DB_DEFAULT_CREDENTIALS, $credentials);
    }
    /**
     * connect_db
     * establish a db connection based on global conf set.
     * @return void
     * @throws Exception if no default db connection found
     */
    public static function connect_db() : MysqliDb {

        $connections = self::$conf["db"] ?? [];

        //Default connection:
        if (array_key_exists("default", $connections)) {
            $default = self::db_credentials($connections["default"]);
            self::$db = new MysqliDb(
                $default['host'], 
                $default['username'], 
                $default['password'], 
                $default['db'], 
                $default['port'],
                $default['charset'],
                $default['socket']
            );
        } else {
            throw new \Exception("No default db connection found", \E_PLAT_ERROR);
        }

        //Additional connections:
        foreach ($connections as $name => $credentials) {
            if ($name === "default") continue;
            $credentials = self::db_credentials($credentials);
            self::$db->addConnection($name, $credentials);
        }

        return self::$db;
    }    
    
    /**
     * add_db_connection
     * add a new db connection to the db object
     * @return void
     */
    public static function add_db_connection(string $name, array $credentials = []) : bool {
        $credentials = self::db_credentials($credentials);
        if (!isset(self::$db)) {
            return false;
        }
        self::$db->addConnection($name, $credentials);
        return true;
    }


    /**
     * disconnect_db
     * safely disconnect from db
     * @return void
     */
    public static function disconnect_db(string $connection = "default") : void {
        self::$db->disconnect($connection);
    }
    
    /**********************************************************************************************************
    /** SESSIONS:
     **********************************************************************************************************/
    /**
     * create_session - sets a session value
     *
     * @param  array $sessions
     * @return void
     */
    public static function create_session(array $sessions) {
        foreach ($sessions as $key => $session) {
            $_SESSION[$key] = $session;
        }
    }
    /**
     * create_session - deletes a session value
     *
     * @param  array $sessions
     * @return void
     */
    public static function delete_session(array $sessions) {
        foreach ($sessions as $session) {
            if (isset($_SESSION[$session]))
                unset($_SESSION[$session]);
        }
    }
    /**
     * get_session - get from session
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public static function get_session(string $key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**********************************************************************************************************
    /** NAVIGATION:
     **********************************************************************************************************/
    /**
     * error_page
     * A Ui based to handle errors that occurred
     * @param  mixed $code
     * @return void
     */
    public static function error_page($code = 0) {
        Base::jump_to_page(
            "error",
                [
                    "page"      => $code,
                    "code"      => $code,
                    "request"   => $_SERVER['REQUEST_URI'],
                    "method"    => $_SERVER['REQUEST_METHOD'],
                    "remote"    => $_SERVER['REMOTE_ADDR']
                ], 
            true
        );
    }
    /**
     * jump_to_page
     * Jump to page by redirect if headers were sent will use a javascript method.
     * @param  mixed $page
     * @param  mixed $Qparams
     * @param  mixed $exit
     * @return void
     * @usage jump_to_page("about", ["v" => 10]) => redirects to the about page with v = 10
     */
    public static function jump_to_page($page = "/", $Qparams = [], $exit = true) {
        $url = self::$index_page_url."/".
                ($page !== "/" ? urlencode($page)."/" : "").
                (!empty($Qparams) ? "?" : "");
        foreach ($Qparams as $p => $v)
            $url .= "&".urlencode($p)."=".urlencode($v);
        if (headers_sent()) 
            echo '<script type="text/javascript">window.location = "'.$url.'"</script>';
        else
            header("Location: ".$url);
        if ($exit) exit();
    } 

}