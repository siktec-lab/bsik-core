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
namespace Siktec\Bsik\Module;

use \Exception;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\Storage\MysqliDb;
use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Render\Pages\AdminPage;
use \Siktec\Bsik\Users\User;

/** 
 * Modules 
 * 
 * This class is a singleton that holds all the modules and their views and events
 * 
 * @package Bsik\Module
 */
class Modules {
    
    private static  $installed  = []; //This holds the installed modules from db as array definitions

    private static $registered  = []; //This holds loaded modules from code. those are objects
    
    private static ?MysqliDb $db; //Reference to a $db connection

    /**
     * init - loads currently installed modules
     *
     * @return instance - the number of installed modules
     */
    public static function init(?MysqliDb $db = null) {

        //Set db connection:
        self::$db = $db;

        //load modules from db and store the info about them:
        if (!is_null($db)) {
            self::$installed = self::$db->where("status", 0, "<>")->map("name")->arrayBuilder()->get("bsik_modules");
        }

        return new self();
    }

    /**
     * register
     * register a module object
     * @param  mixed $module
     * @throws Exception if module is not a Module instance
     * @return void
     */    
    public static function register(mixed $module) : void {
        //Make sure its callable:
        if (!is_object($module) || !$module instanceof Module) {
            throw new Exception("Trying to register a non callable module", E_PLAT_ERROR);
        }

        //Make sure its installed or skip this one:
        // if (self::is_installed($module->module_name)) {
        //     return;
        // }

        //Extend settings:
        $extend_settings_messages = []; 
        $module->settings->extend(
            self::$installed[$module->module_name]["settings"] ?? [], 
            $extend_settings_messages
        );

        //Save reference:
        self::$registered[$module->module_name] = $module;
    }
        
    /**
     * register_module_once
     * register a module object only if its a new name.
     * @param  mixed $module
     * @throws Exception if module is not a Module instance
     * @return void
     */
    public static function register_module_once(mixed $module) : void {

        //Make sure its an object and a module:
        if (!is_object($module) || !$module instanceof Module) {
            throw new Exception("Trying to register a non callable module", E_PLAT_ERROR);
        }

        //Check if its allready registered:
        if (self::is_registered($module->module_name)) {
            return;
        }

        //Register:
        self::register($module);
    }

    /**
     * loads a module code which will register itself.
     * @param  string $module
     * @throws Exception if module has errors or if the path is not reachable
     * @return bool
     */
    //TODO: test this what happens if module throws an error should be reflected in the return value
    public static function load_module(string $module_name) : bool {

        //If its allready registered:
        if (self::is_registered($module_name)) {
            return true;
        }
        //Make sure its an installed module:
        if (self::is_installed($module_name)) {
            $module_installed = self::module_installed($module_name);
            $module_file = Std::$fs::path_to("modules", [$module_installed["path"], "module.php"]);
            //Require module
            if (file_exists($module_file["path"])) {
                try {
                    //Load module & views:
                    require $module_file["path"];
                    return true;
                } catch (\Throwable $e) {
                    throw new Exception("Internal Error captured on module load [{$e->getMessage()}].", E_PLAT_ERROR, $e);
                }
            } else {
                throw new Exception("Could not find module file to load.", E_PLAT_ERROR);
            }
        }
        return false;
    }
    /**
     * initiata a registered module.
     * @param  string $module
     * @param  string $view => the active view to set - empty string for default.
     * @param  ?MysqliDb $db
     * @param  ?AdminApi $Api
     * @param  ?AdminPage $Page
     * @param  ?User $User
     * @throws Exception from self::load_module
     * @return ?Module
     */
    public static function initiate_module(
        string $module_name, 
        string $view, 
        ?MysqliDb $db = null, 
        ?AdminApi $Api = null, 
        ?AdminPage $Page = null, 
        ?User $User = null
    ) : ?Module {

        $load = self::load_module($module_name);
        if ($load) {
            $data = self::module_installed($module_name);
            $data["which"] = $view;
            $module = self::module($module_name);
            if ($module) {
                $module->load(
                    data:   $data, 
                    DB:     $db,
                    Api:    $Api, 
                    Page:   $Page, 
                    User:   $User
                );
            }
            return $module;
        }
        return null;
    }
    
    /**
     * is_installed @Alias of installed
     * check if module name is installed
     * @param  string $name
     * @return bool
     */
    public static function is_installed(string $module_name) : bool {
        return array_key_exists($module_name, self::$installed);
    }

    /**
     * is_registered @Alias of registered
     * check if module name is registered
     * @param  string $name
     * @return bool
     */
    public static function is_registered(string $module_name) : bool {
        return array_key_exists($module_name, self::$registered);
    }

    /**
     * installed
     * check if module name is installed
     * @param  string $name
     * @return bool
     */
    public static function installed(string $name) : bool {
        return self::is_installed($name);
    }
    
    /**
     * registered
     * check if module name is registered
     * @param  string $name
     * @return bool
     */
    public static function registered(string $name) : bool {
            return self::is_registered($name);
    }

    
    /**
     * module_installed
     * returns the array of the installation definition of the modules.
     * holds only active modules.
     * @param  string $name
     * @return array
     */
    public static function module_installed(string $name) : array {
        if (self::installed($name)) {
            return self::$installed[$name];
        }
        return [];
    }    
    
    /**
     * module
     * return the module Object that was registered.
     * @param string $name
     * @return Module
     */
    public static function module(string $name) : Module|null {
        if (self::registered($name)) {
            return self::$registered[$name];
        }
        return null;
    }
    
    /**
     * get_all_installed
     * returns an array of all installed module names.
     * @return array
     */
    public static function get_all_installed() : array {
        return array_keys(self::$installed);
    }

    /**
     * get_all_registered
     * returns an array of all registered module names.
     * @return array
     */
    public static function get_all_registered() : array {
        return array_keys(self::$registered);
    }
}