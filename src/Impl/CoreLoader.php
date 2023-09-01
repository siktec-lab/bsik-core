<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/

namespace Siktec\Bsik\Impl;

use \Siktec\Bsik\Api\Input\Validate;
use \Siktec\Bsik\Impl\FiltersCorePack;
use \Siktec\Bsik\Impl\ValidationCorePack;

class CoreLoader {

    public static function load_all() {

        //Load core packs:
        self::load_packs();

        //Load core endpoints:
        self::load_core_endpoints();

        //Load core components:
        self::load_core_components();

        //Load core privileges groups:
        self::load_core_privileges_groups();

    }

    public static function load_packs() {

        //Register validator extenssion pack:
        Validate::add_class_validator(new ValidationCorePack);

        //Register validator extenssion pack:
        Validate::add_class_filter(new FiltersCorePack);
        
    }

    public static function load_core_endpoints() {

        //Load core endpoints:
        require_once __DIR__ . "/CoreApiEndpoints.php";
    
    }

    public static function load_core_components() {

        //Load core components:
        require_once __DIR__ . "/CoreComponents.php";
    
    }

    public static function load_core_privileges_groups() {

        //Load core privileges groups:
        require_once __DIR__ . "/CorePrivGroups.php";
    
    }

}