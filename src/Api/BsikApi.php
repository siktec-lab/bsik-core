<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial
*******************************************************************************/

namespace Siktec\Bsik\Api;

use \Exception;
use \Siktec\Bsik\Trace;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\Base;
use \Siktec\Bsik\Privileges as Priv;
use \Monolog\Logger;
use \Siktec\Bsik\Storage\MysqliDb;
use \Siktec\Bsik\Api\Input\Validate;
use \Siktec\Bsik\Api\EndPoint\ApiEndPoint;
use \Siktec\Bsik\Api\EndPoint\RegisteredEndpoints;

/**
 * BsikApi
 * 
 * This class is the main api class. It is used to register endpoints and to process the request.
 * 
 * @package Bsik\Api
 */
class BsikApi {

    //Reused shared stuff:
    public static $user_string;
    public static Logger $logger;
    public static bool $logger_enabled;
    public static MysqliDb $db;

    //Issuer privileges:
    public static Priv\PrivDefinition $issuer_privileges;

    //values & flags:
    public string  $csrf                    = "";               // System token supplied
    public bool    $debug                   = false;            // debug mode adds data to the result
    public static  bool $external           = false;            // A flag that indicates the api request source 
    public static  bool $only_global        = false;            // A flag to force only global allowed apis endpoints to be loaded
    public static  string $base_module      = "#unknown";       // register the current issuer module - used for global safe loading.
    private static array $temp_only_global  = [];               // Used internally to toggle and restore the global flags.
    public static  bool $ignore_visibility  = false;            //This flag when raised is to avoid external / front / global checks when registering endpoints
    //Containers:
    public ApiRequestObj $request; // Implement an object defining the result returned
    public static ?RegisteredEndpoints $endpoints = null; // A class that holds all implemented end points

    public static $codes = [
        200 => 'OK',
        201 => 'Created',                       // POST/PUT resulted in a new resource, MUST include Location header
        202 => 'Accepted',                      // request accepted for processing but not yet completed, might be disallowed later
        204 => 'No Content',                    // DELETE/PUT fulfilled, MUST NOT include message-body
        304 => 'Not Modified',                  // If-Modified-Since, MUST include Date header
        400 => 'Bad Request',                   // malformed syntax
        403 => 'Forbidden',                     // unauthorized
        404 => 'Not Found',                     // request URI does not exist
        405 => 'Method Not Allowed',            // HTTP method unavailable for URI, MUST include Allow header
        415 => 'Unsupported Media Type',        // unacceptable request payload format for resource and/or method
        426 => 'Upgrade Required',
        451 => 'Unavailable For Legal Reasons', // REDACTED
        500 => 'Internal Server Error',         // all other errors
        501 => 'Not Implemented'                // (currently) unsupported request method
    ];

    public function __construct(
        string $csrf,
        bool $debug = false,
        ?Priv\PrivDefinition $issuer_privileges = null
    ) {
        //Initialize:
        $this->request      = new ApiRequestObj();
        $this->csrf         = $csrf;
        $this->debug        = $debug;
        if (self::$endpoints === null) {
            self::$endpoints = new RegisteredEndpoints();
        }
        self::$issuer_privileges = $issuer_privileges ?? new Priv\GrantedPrivileges();
    }
    
    public function set_headers(string $origin = "*", string $methods = "*", string $content = "application/json") {
        //define headers:
        if (!headers_sent()) {
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Methods: {$methods}");
            header("Content-Type: {$content}");
        }
    }

    public function register_debug(string $key, $data) {
        if ($this->debug) {
            $this->request->add_debug_data([$key => $data]);
        }
    }

    public function add_debug(string $key, $data) {
        if ($this->debug) {
            $this->request->append_debug_data($key, $data);
        }
    }

    public function get_user(string $part = "str") {
        switch ($part) {
            case "id": return explode(":", self::$user_string)[0] ?? null;
            case "email": return explode(":", self::$user_string)[1] ?? null;
        }
        return self::$user_string;
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
            //Add module and name:
            self::$logger->{$type}($message, $context);
        }
    }

    public static function force_global(bool $state, string $module) {
        //Set flags
        self::$only_global = $state;
        self::$base_module = $module;
    }
    public static function set_temp_force_global(bool $state, string $module) {
        //Save current state:
        self::$temp_only_global[] = [
            self::$only_global,
            self::$base_module
        ];
        self::force_global($state, $module);
    }

    public static function unset_temp_force_global() {
        //if has stored states:
        if (!empty(self::$temp_only_global)) {
            [$state, $module] = array_pop(self::$temp_only_global);
            self::force_global($state, $module);
        }
    }
    
    /**
     * ::check_endpoint_visibility
     * 
     * check if current flags are matching the visibility of this endpoint
     * will check front / external / global
     * 
     * @param  ApiEndPoint $endpoint
     * @return bool
     */
    public static function check_endpoint_visibility(ApiEndPoint $endpoint) : bool {
        //Check visibility:
        if (self::$ignore_visibility) {
            return true;
        }

        //Avoid front resticted mismatch:
        if (
                property_exists(get_called_class(), "front_exposed")
            &&  get_called_class()::$front_exposed
            &&  !$endpoint->front
        ) {
            return false;
        }

        //Avoid external mismatch:
        if (self::$external && !$endpoint->external) {
            return false;
        }
        
        //only global allowed?
        if (self::$only_global && !$endpoint->global && self::$base_module != $endpoint->module) {
            return false;
        }

        //Its ok return true:
        return true;
    }
    
    /**
     * register_endpoint
     * registers an endpoint object to the endpoints collection
     * @param  ApiEndPoint $end_point
     * @return bool
     */
    public static function override_endpoint(ApiEndPoint $endpoint) : bool {

        //Check visibility:
        if (!self::check_endpoint_visibility($endpoint)) {
            return false;
        }

        //Avoid if trying to override a protected endpoint:
        if (
            self::has_registered_endpoint($endpoint->name) &&
            self::get_registered_endpoint($endpoint->name)->protected
        ) return false;

        //Register:
        self::$endpoints->{$endpoint->name} = $endpoint;
        return true;
    }

    /**
     * register_endpoint_once
     * registers an endpoint object to the endpoints collection only if its new
     * @param  ApiEndPoint $end_point
     * @return bool
     */
    public static function register_endpoint(ApiEndPoint $endpoint) : bool {
        
        //Check visibility:
        if (!self::check_endpoint_visibility($endpoint)) {
            return false;
        }
        
        //Avoid if trying to override a protected endpoint:
        //TODO: this should check for override option:
        if (self::has_registered_endpoint($endpoint->name)) 
            return false;

        //Register:
        self::$endpoints->{$endpoint->name} = $endpoint;
        return true;
    }   

    /**
     * has_registered_endpoint
     * checks if an endpoint is registered
     * @param  string $name- endpoint name
     * @return bool
     */
    public static function has_registered_endpoint(string $name) : bool {
        return isset(self::$endpoints->{$name});
    }
    
    /**
     * get_registered_endpoint
     * get a registered endpoint object
     * @param  string $name     - endpoint name
     * @return ApiEndPoint|null - null if not registered
     */
    public static function get_registered_endpoint(string $name) : ApiEndPoint|null {
        return self::has_registered_endpoint($name) ? self::$endpoints->{$name} : null;
    }
        
    /**
     * get_all_registered_endpoints
     * returns all the names of the registered endpoints
     * @return array - the array of names, empty array if none
     */
    public static function get_all_registered_endpoints() : array {
        return self::$endpoints->get_all_names();
    }
    
    /* SH: added - 2021-04-03 => make this documented that those request entries are reserved */
    public function parse_request(array $input, array $ignore = ["type", "module", "page", "which", "request_type", "request_token"]) {
        $this->request->token = $input["request_token"] ?? "";
        $this->request->type  = $input["request_type"] ?? "";
        $this->request->args  = Std::$arr::filter_out($input, $ignore);
        //Validate origin token:
        if (empty($this->csrf) || empty($this->request->token) || $this->csrf !== $this->request->token) {
            $this->request->update_answer_status(403, "Token is not set or invalid");
            return false;
        }
        return true;
    }

    private function prepare_endpoint_args(array $raw_args, ApiEndPoint $Endpoint) : array {

        $params     = $Endpoint->params;
        $filters    = $Endpoint->filters;

        //Get defined or null:
        $defined_args = Std::$arr::get_from($raw_args, array_keys($params), null);
        
        //Set defaults on null or empty string:
        array_walk($defined_args, 
            fn(&$el, $k) => $el = (is_null($el) || $el == "" ? $params[$k] : $el)
        );
        
        //Register debugging:
        $this->register_debug("endpoint-expected-params",   $params);
        $this->register_debug("request-filters",            $filters);
        $this->register_debug("request-args",               $defined_args);
        
        //Apply normalization procedures:
        foreach ($defined_args as $arg_name => $arg) {
            try {
                $defined_args[$arg_name] = Validate::filter_input($arg, $filters[$arg_name] ?? "none");
            } catch (\Throwable $t) {
                $this->register_debug("error-arg-filtered-".$arg_name,$t->getMessage());
                $Endpoint->log_notice($t->getMessage(), [$filters[$arg_name] ?? "none"]);
            }
        }
        
        $this->register_debug("final-args", $defined_args);
        return $defined_args;
    }
    
    /**
     * load_global
     * it expected that any implementation that want to use live global Endpoints loading should 
     * implement this and register additional endpoints
     * @param  string $endpoints_path
     * @param  bool $only_external
     * @return bool
     */
    public function load_global(string $endpoints_path) : bool {
        return false;
    }

    /**
     * validate
     * this is the base logic on validating EndPoints args 
     * can be override to implement custom validation logic
     * @param  ApiEndPoint $Endpoint
     * @param  array $filtered_args
     * @param  array $messages
     * @return bool
     */
    public function validate(ApiEndPoint $Endpoint, array $filtered_args,  array &$messages) : bool {
        $valid = true;
        foreach($Endpoint->conditions as $param => $rule) {
            $messages[$param] = [];
            try {
                if (!Validate::validate_input($filtered_args[$param], $rule, $messages[$param])) {
                    $valid = false;
                }
            } catch (\Throwable $t) {
                $Endpoint->log_notice($t->getMessage(), ["rule" => $rule]);
                $this->register_debug("error-arg-validate-".$param, $t->getMessage());
                $valid = false;
            }
        }
        return $valid;
    }

    /** Executes an api call. 
     * @param bool  $external       - is this a call from internall or external.
     * @param array $args           - the arguments to be passed into the method
     * @param string  $endpoint     - the requested endpint to call.
     */
    public function execute(bool $external, array $args = [], string $endpoint = "") {

        
        //Request defined:
        $endpoint = empty($endpoint) ?  $this->request->type : $endpoint;
        $raw_args = empty($args)     ? $this->request->args : $args;
        
        //Set external flag:
        self::$external = $external;

        //Debug and trace this execute:
        $this->register_debug("raw-args", $raw_args);
        $this->add_debug("endpoints-trace", $endpoint);
        $this->register_debug("from-external", self::$external);
        Trace::add_trace("execute-endpoint", __CLASS__, [ 
            "endpoint"              => $endpoint,
            "from-external"         => $external,
            "registered-endpoints"  => self::get_all_registered_endpoints(),
        ]);
        
        //If not defined check if its globally available: 
        $loaded_global = false;
        if (!self::has_registered_endpoint($endpoint)) {
            $loaded_global = $this->load_global($endpoint);
            Trace::add_trace("load-global-endpoints", __CLASS__, [ 
                "result"                => $loaded_global,
                "endpoint"              => $endpoint, 
                "from-external"         => $external,
                "registered-endpoints"  => self::get_all_registered_endpoints()
            ]);
        }
        $this->register_debug("loaded-global", $loaded_global);
        $this->register_debug("end-points", self::get_all_registered_endpoints());
        
        //If Registered than execute:
        if (($endpoint_object = self::get_registered_endpoint($endpoint)) !== null) {

            //Check issuer privileges:
            $priv_messages = [];
            if (!$endpoint_object->policy->has_privileges(self::$issuer_privileges, $priv_messages)) {
                $this->register_debug("user-privileges", self::$issuer_privileges);
                $this->register_debug("user-privileges-messages", $priv_messages);
                $this->request->update_answer_status(403, "required privileges not met");
                return false;
            }

            //Check required are defined and valid:
            $filtered_args = $this->prepare_endpoint_args(
                $raw_args, 
                $endpoint_object
            );

            //Validate inputs:
            $this->register_debug("request-validation-rules", $endpoint_object->conditions);
            $messages = [];

            //if not valid update answer object:
            if (!$this->validate($endpoint_object, $filtered_args, $messages)) {
                $this->request->answer_data($messages);
                $this->request->update_answer_status(400, "Request params are not valid");
                return false;
            }

            //Execute:
            return ($endpoint_object->method)($this, $filtered_args, $endpoint_object);
        }

        $this->request->update_answer_status(501, "Requested api method is not supported");
        return false;
    }

    /** executes and get an api call. 
     * @param bool  $print      - print or return?.
     * @param bool  $external   - is this a call from internall or external.
     * @param array $args       - the arguments to be passed into the method
     * @param bool  $endpoint   - the requested endpint to call.
     */

    public function answer(bool $print = true, bool $execute = true, bool $external = false, array $args = [], string $endpoint = "") {
        //Execute first:
        if ($execute) {
            $this->execute($external, $args, $endpoint);
        }

        //Set code if not set:
        if ($this->request->answer_code() === 0)
            $this->request->update_answer_status(200);

        //Set http response code:
        http_response_code($this->request->answer_code());
        $response = json_encode($this->request->answer, JSON_PRETTY_PRINT);
        if ($print) print $response;
        return $response;
    }

    /** call an api endpoint internally and return the response array. 
     * @param bool  $external   - is this a call from internall or external.
     * @param array $args       - the arguments to be passed into the method
     * @param bool  $endpoint   - the requested endpint to call.
     */
    public function call(array $args = [], string $endpoint = "") : ApiRequestObj {
        //Save current state to restore:
        $request = clone $this->request;

        //Execute call:
        $this->execute(self::$external, $args, $endpoint);

        //Set code if not set:
        if ($this->request->answer_code() === 0)
            $this->request->update_answer_status(200);

        //Save results:
        $final = clone $this->request;

        //restore state:
        $this->request = $request;

        //Return the saved object:
        return $final;
    }

    public function file(string $name, string $to, int $max_bytes = -1, array $mime = []) : array {
        // Undefined | Multiple Files | $_FILES Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (
            !isset($_FILES[$name]) ||
            !isset($_FILES[$name]['error']) ||
            is_array($_FILES[$name]['error'])
        ) {
            return [false, 'invalid parameters'];
        }
        // Check $_FILES['file']['error'] value.
        switch ($_FILES[$name]['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return [false, 'no file sent'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return [false, 'form exceeded filesize limit'];
            default:
                return [false, 'unknown errors'];
        }
        // You should also check filesize here.
        if ($max_bytes > -1 && $_FILES[$name]['size'] > $max_bytes) {
            return [false, 'exceeded filesize limit'];
        }
        // DO NOT TRUST $_FILES['file']['mime'] VALUE !!
        // Check MIME Type by yourself.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $got_mime = $finfo->file($_FILES[$name]['tmp_name']);
        $allowed_mime = Std::$fs::get_mimetypes(...$mime);
        $ext = array_search($got_mime, $allowed_mime, true);
        if (!empty($mime) && !is_string($ext) ) {
            return [false, 'invalid file format'];
        }
        // You should name it uniquely.
        // DO NOT USE $_FILES['file']['name'] WITHOUT ANY VALIDATION !!
        $temp_name = sprintf("%s.%s", 
            Std::$str::filter_string(
                pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME), 
                ["A-Z","a-z","0-9","_",".","\\-"]
            ), 
            $ext
        );
        $full_to = Std::$fs::path($to, $temp_name);
        //Move the file:
        try {
            if (!move_uploaded_file($_FILES[$name]['tmp_name'], $full_to))
                return [false, 'failed to move file'];
        } catch (Exception $e) {
            return [false, 'failed to move file'];
        }
        return [true, $full_to];
    }
}

//Binding between Base and Api + initiating static endpoints container:
BsikApi::$user_string       = &Base::$user_string;
BsikApi::$logger            = &Base::$logger;
BsikApi::$logger_enabled    = &Base::$logger_enabled;
BsikApi::$db                = &Base::$db;
// BsikApi::$endpoints         = new RegisteredEndpoints(); //TODO: removed this line maybe? 