<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Render\Pages;

use \Siktec\Bsik\StdLib as BsikStd;

class FrontPageMeta {

    public array $defined_metas;
    public array $additional_meta;

    public function __construct()
    {
        $this->defined_metas    = [];
        $this->additional_meta  = [];
    }
        
    /**
     * define
     * - defines required page meta tags.
     * @param  array $_metas
     * @return void
     */
    public function define(array $_metas = []) : void {
        $this->defined_metas = BsikStd\Arrays::is_assoc($_metas) ? $_metas : array_fill_keys($_metas, "");
    }
    
    /**
     * meta
     * sets a defined meta value that will be rendered
     * 
     * @param  string $name     => meta name
     * @param  string|bool $set => if false will return value otherwise will set the meta
     * @return object|string
     */
    public function set(string $name, string|bool $set = false) : object|string {
        if (!isset($this->defined_metas[$name]))
            trigger_error("'Page->meta()' you must use a valid meta type. [unknown entry '$name']", \E_PLAT_WARNING);
        if ($set === false) 
            return $this->defined_metas[$name];
        $this->defined_metas[$name] = $set;
        return $this;
    }  

    /**
     * op_meta - declare a custom optional meta tag:
     * op_meta(["name" => "text", "content" => "text"])
     *
     * @param array $define - associative array that defines the attributes
     * @return object
     */
    public function add(array $define) : object {
        $attrs = "";
        foreach ($define as $attr => $value) {
            $attrs .= $attr.'="'.htmlspecialchars($value).'" '; 
        }
        $this->additional_meta[] = sprintf("<meta %s />", $attrs);
        return $this;
    }

    public function data_object(array $data, string $name = "page-data") : void {
        $this->add([
            "name"      => $name, 
            "content"   => base64_encode(json_encode($data))
        ]);
    }

    /**
     * get - serialize the metas object to an array
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
