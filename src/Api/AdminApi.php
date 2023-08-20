<?php
/**
 * AdminApi.php
 * @author SIKTEC
 * @version 1.2.0
 * @since 1.0.0
 */

namespace Siktec\Bsik\Api;

use \Siktec\Bsik\Trace;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Api\BsikApi;
use \Siktec\Bsik\Render\Pages\AdminPage;

/**
 * AdminApi
 * This class is the main class for all admin api endpoints
 * @package Siktec\Bsik\Api
 * @since 1.0.0
 * @version 1.2.0
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

        $module_api = AdminPage::$modules::module_part_path($module, "api");
        if (!empty($module_api)) {

            try {

                //Set global flag mode:
                self::set_temp_force_global(
                    state  : true, 
                    module : !self::$front_exposed ? AdminPage::$module->module_name : "#front#" // This check is here because we might get here from front and in this case no module check is done to bypass global restrictions
                );

                //This will add all registered of this endpoint implementation:
                require $module_api;
                
                //Restor global state:
                self::unset_temp_force_global();

                return true;

            } catch (\Throwable $t) {
                $this->register_debug("error-loading-global-endpoint-".$module, $t->getMessage());
                self::log("warning", $t->getMessage(), [
                    "error-in"          => $t->getFile().":".$t->getLine(),         
                    "api-endpoint"      => $endpoints_path,
                    "search-module"     => $module
                ]);
            }
        }
        return false;
    }
}