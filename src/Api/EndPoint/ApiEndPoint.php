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

namespace Siktec\Bsik\Api\EndPoint;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Api\BsikApi;

/**
 * ApiEndPoint
 * 
 * This class is used to define a single endpoint.
 * 
 * @package Siktec\Bsik\Api\Endpoint
 */
class ApiEndPoint {

    public bool   $front     = false;
    public bool   $global    = false;
    public bool   $external  = false;
    public bool   $protected = false;
    public string $module    = "";
    public string $name      = "";
    public string $describe  = "";
    public array  $params;
    public array  $filters;
    public array  $conditions;
    public string $working_dir;
    public Priv\RequiredPrivileges|null $policy;
    public $method;    
    /**
     * __construct
     *
     * @param  string $_name    - the unique endpoint name.
     * @param  mixed $_required - an Array with expected $args defined.
     * @param  mixed $_method   - The closure to execute Arguments must be (AdminApi $Api, array $args)
     * @return void
     */
    public function __construct(
        string  $module,             //The Api scope -> which module / page path holds it
        string  $name,               //The Api endpoint name / the method name
        array   $params,             //Expected params with there defaults
        array   $filter,             //Filter procedures to apply
        array   $validation,         //Validation conditions to apply       
                $method,
        string  $working_dir,
        bool    $allow_global   = false,
        bool    $allow_external = false,
        bool    $allow_override = false,
        bool    $allow_front    = false,
        Priv\RequiredPrivileges|null $policy = null,
        string  $describe       = ""
    ) {

        $this->module       = Std::$str::filter_string($module, ["A-Z", "a-z", "0-9", " ", "_"]);
        $this->name         = $this->module.'.'.Std::$str::filter_string($name, ["A-Z", "a-z", "0-9", " ", "_", "."]);
        $this->params       = $params;
        $this->filters      = $filter;
        $this->conditions   = $validation;
        $this->method       = $method;          // The operation closure
        $this->global       = $allow_global;    // expose as a global callable endpoint
        $this->front        = $allow_front;     // expose as a global callable endpoint
        $this->external     = $allow_external;  // allow to be called from external api called.
        $this->protected    = $allow_override;  // allow to be edited and replaced
        $this->working_dir  = $working_dir;
        $this->policy       = $policy ?? new Priv\RequiredPrivileges();    
        $this->describe     = $describe;
    }

    public function log(string $type, string $message, array $context) : void {
        //Add to logger end point data:
        if (!array_key_exists("api-module", $context)) {
            $context["api-module"] = $this->module;
        }
        if (!array_key_exists("api-endpoint", $context)) {
            $context["api-endpoint"] = $this->name;
        }
        //Log
        BsikApi::log($type, $message, $context);
    }
    public function log_error(string $message = "API Endpoint ERROR", array $context = []) : void {
        $this->log("error", $message, $context);
    }
    public function log_notice(string $message = "API Endpoint NOTICE", array $context = []) : void {
        $this->log("notice", $message, $context);
    }
    public function log_info(string $message = "API Endpoint INFO", array $context = []) : void {
    $this->log("info", $message, $context);
    }
    public function log_warning(string $message = "API Endpoint WARNING", array $context = []) : void {
        $this->log("warning", $message, $context);
    }
}
