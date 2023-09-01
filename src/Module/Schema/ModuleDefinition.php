<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Module\Schema;

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Module\Schema\ModuleSchema;

class ModuleDefinition {

    public ModuleSchema $schema; // a reference to the schema object

    public bool     $valid  = false; // Holds the validation result
    
    public array    $errors = []; // Holds the validation errors
    
    public array    $struct = []; // Holds the module definition values

    /**
     * get_value
     * returns a value using a system naming for cross module compatibility
     * if the you need to translate the path keys use the $ prefix e.g. "$path.to.value" or "path.$to.value"
     * @param  string $path = "path.to.value" use keys separated by dots see \Siktec\Bsik\StdLib\Arrays::path_get()
     * @return mixed the value of the path or null if not found
     */
    public function get_values(string $path) : mixed {
        $trans = explode(".", $path);

        // translate the naming:
        $trans = array_map(function($v) {
            if ($v[0] === "$") {
                return $this->schema->naming(substr($v, 1)) ?: substr($v, 1);
            }
            return $v;
        }, $trans);

        // get the value:
        return BsikStd\Arrays::path_get(
            implode(".", $trans), 
            $this->struct
        );
    }
    
    /**
     * get_value
     * returns a value using a system naming for cross module compatibility
     * if the you need to translate the path keys use the $ prefix e.g. "$path.to.value" or "path.$to.value"
     * @param  string $path = "path.to.value" use keys separated by dots see \Siktec\Bsik\StdLib\Arrays::path_get()
     * @return mixed the value of the path or null if not found
     */
    public function get_value(string $path) : mixed {
        $data = $this->get_values($path);
        if (is_array($data) && !empty($data)) {
            return $data[0];
        }
        return $data;
    }

}
