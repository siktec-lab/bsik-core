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

require_once BSIK_AUTOLOAD;

use \Siktec\Bsik\StdLib as BsikStd;

class FrontPageRequest {
    
    //values filter paterns:
    public static $name_pattern  = "A-Za-z0-9_-";
    public static $which_pattern = "/A-Za-z0-9_-";

    //Allowed types:
    public static $types = [
        "page",
        "api",
        "error",
        "logout"
    ];
    //Raw request:
    private array $requested; 
    
    //Values:
    public string $type  = "";
    public string $page  = "";
    public string $which = "";
    public string $when  = "";

    /**
     * __construct
     * @param  array $request -> the request params
     * @return FrontPageRequest
     */
    public function __construct(array $request = []) {
        $this->requested = $request;
    }    

    /**
     * set_type
     * - sets the request type. 
     * @param  string $default
     * @return bool
     */
    public function type(string $default) : bool {
        $this->type = isset($this->requested["type"]) && in_array($this->requested["type"], self::$types) ? $this->requested["type"] : $default;
        return !empty($this->type);
    }    
    
    /**
     * set_page
     * - sets the requested page name
     * @param  string $default
     * @return bool
     */
    public function page(string $default) : bool {
        $this->page = isset($this->requested["page"])
                        ? BsikStd\Strings::filter_string($this->requested["page"], self::$name_pattern)
                        : $default;
        return !empty($this->page);
    }    

    /**
     * set_which
     * - sets the which query string
     * @param  string $default
     * @return bool
     */
    public function which(string $default) : bool {
        $this->which = (isset($this->requested["which"]))
                            ? BsikStd\Strings::filter_string($this->requested["which"], self::$which_pattern)
                            : $default;
        return !empty($this->which);
    }
    
    /**
     * set_when
     * - sets the timestamp of the request
     * @param  string $time_str
     * @return void
     */
    public function when(string $time_str = "") : void {
        $this->when = empty($time_str) ? BsikStd\Dates::time_datetime() : $time_str;
    }
    
    /**
     * get - serialize the request to an array
     *
     * @return array
     */
    public function get() : array {
        return BsikStd\Objects::to_array($this, filter : [
            "name_pattern",
            "which_pattern",
            "types",
        ]);
    }
}
