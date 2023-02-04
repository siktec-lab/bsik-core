<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-17
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial
*******************************************************************************/

namespace Siktec\Bsik\Api;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Trace;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Api\BsikApi;
use \Siktec\Bsik\Render\Pages\AdminPage;

/** 
 * AdminApi
 * 
 * this class is the Admin api implementation
 * 
 * @package Bsik\Api
 */
class AdminApi extends BsikApi
{

    public static bool $front_exposed = false;
    public function __construct(
        string $csrf, 
        bool $debug = false, 
        ?Priv\PrivDefinition $issuer_privileges = null,
        bool $only_front = false
    ) {
        parent::__construct($csrf, $debug, $issuer_privileges);
        self::$front_exposed = $only_front;
    }

        
    /**
     * load_global
     * this method is implemented to specific api global structure of manage
     * @param  string   $endpoints_path
     * @return bool
     */
    public function load_global(string $endpoints_path) : bool {
        $path           = explode(".", $endpoints_path);
        $module         = array_shift($path) ?? "#unknown";
        $endpoint_name  = implode(".", $path);

        Trace::add_trace("global-api-loader", __CLASS__, [
            "path"          => $endpoints_path, 
            "module"        => $module, 
            "endpoint"      => $endpoint_name
        ]);

        if (Std::$fs::file_exists("modules", [$module, "module-api.php"])) {

            try {
                
                $extend_api_file = Std::$fs::path_to("modules", [$module, "module-api.php"]);
                
                //validate module is activated and installed:
                if (!AdminPage::$modules->is_installed($module)) { 
                    throw new \Exception("tried to use an inactive or uninstalled module", E_PLAT_WARNING);
                }

                //Set global flag mode:
                self::set_temp_force_global(
                    state  : true, 
                    module : !self::$front_exposed ? AdminPage::$module->module_name : "#front#" // This check is here because we might get here from front and in this case no module check is done to bypass global restrictions
                );

                //This will add all registered of this endpoint implementation:
                require $extend_api_file["path"];
                
                //Restor global state:
                self::unset_temp_force_global();

                return true;

            } catch (\Throwable $t) {
                $this->register_debug("error-loading-global-endpoint-".$module, $t->getMessage());
                self::log("warning", $t->getMessage(), [
                    "error-in"          => $t->getFile().":".$t->getLine(),         
                    "api-endpoint"      => $endpoints_path,
                    "search-module"     => $module,
                    "installed-modules" => implode(",", AdminPage::$modules->get_all_installed()),
                    "registered-modules" => implode(",", AdminPage::$modules->get_all_registered()),
                ]);
            }
        }
        return false;
    }
}


