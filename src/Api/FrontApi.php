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
use \Siktec\Bsik\CoreSettings;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Api\BsikApi;
use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Module\Modules;
use \Siktec\Bsik\Render\Pages\FrontPage;
use \Siktec\Bsik\Render\Pages\AdminPage;

/**
 * FrontApi
 * 
 * this class is the Front api implementation
 * front api is a subset of the admin api, it is used to expose api to the front end
 * 
 * @package Bsik\Api
 */
class FrontApi extends BsikApi
{

    const MANAGE_ENDPOINT_START = "manage.";

    public function __construct(
        string $csrf, 
        bool $debug = false, 
        ?Priv\PrivDefinition $issuer_privileges = null
    ) {
        parent::__construct($csrf, $debug, $issuer_privileges);
    }

    
    /**
     * load_global
     * this method is implemented to specific api global structure of manage
     * @param  string   $endpoints_path
     * @return bool
     */
    public function load_global(string $endpoints_path) : bool {
        //TODO: $endpoints_path may be a weakness as its used to load resources - make sure nothing can be injected. 
        $path           = explode(".", $endpoints_path);
        $page           = array_shift($path) ?? "#unknown";
        $endpoint_name  = implode(".", $path);

        Trace::add_trace("global-api-loader", __CLASS__, [
            "path"          => $endpoints_path, 
            "page"          => $page, 
            "endpoint"      => $endpoint_name
        ]);
        //TODO: here also we need to make sure we are loading only registered pages:
        if (Std::$fs::file_exists("front-pages", [$page, "page-api.php"])) {
            try {

                $extend_api_file = Std::$fs::path_to("front-pages", [$page, "page-api.php"]);
                
                //validate page is activated and user has the required permissions:
                if (!isset(FrontPage::$pages[$page])) {
                    throw new \Exception("tried to use an inactive or permission restricted api/page", E_PLAT_WARNING);
                }

                //Set global flag mode:
                self::set_temp_force_global(
                    state  : true, 
                    module : FrontPage::$page["name"]
                );

                //This will add all registered of this endpoint implementation:
                require $extend_api_file["path"];
                
                //Restor global state:
                self::unset_temp_force_global();

                return true;

            } catch (\Throwable $t) {
                $this->register_debug("error-loading-global-endpoint-".$page, $t->getMessage());
                self::log("warning", $t->getMessage(), [
                    "api-endpoint"      => $endpoints_path,
                    "search-page"       => $page,
                    "allowed-pages"   => is_array(FrontPage::$pages) ? implode(',', array_keys(FrontPage::$pages)) : "unknown"
                ]);
            }
        }
        return false;
    }

    /**
     * 
     */
    public function prepare_endpoint_for_manage(string $endpoint = "") : string {
        $endpoint = empty($endpoint) ?  $this->request->type : $endpoint;
        $parts = explode(".", $endpoint);
        array_shift($parts);
        return implode(".", $parts);
    }
    
    public function is_manage_call(string $endpoint = "") : bool{
        $endpoint = empty($endpoint) ?  $this->request->type : $endpoint;
        return str_starts_with($endpoint, self::MANAGE_ENDPOINT_START);
    }

    public function answer_from_manage(bool $print = true) : string {

        //Save for later current loaded:
        $save_current_endpoints = clone self::$endpoints;
        self::$endpoints = new Class {};

        //Initialize Manage controller and Api:
        AdminPage::$issuer_privileges = self::$issuer_privileges;
        AdminPage::$modules = Modules::init(self::$db);
        
        $AApi = new AdminApi(
            csrf                : $this->csrf,  // CSRF TOKEN
            debug               : CoreSettings::get("api-responses-with-debug-info", false),
            issuer_privileges   : self::$issuer_privileges,
            only_front          : true
        );

        //Set the current request:
        $AApi->request = clone $this->request;
        
        //Change endpoint:
        $AApi->request->type = $this->prepare_endpoint_for_manage();
        
        //Load api:
        require_once CoreSettings::$path["manage-api"];

        //Execute call:
        $AApi->execute(self::$external);

        //Set code if not set:
        if ($AApi->request->answer_code() === 0)
            $AApi->request->update_answer_status(200);

        //Set http response code:
        http_response_code($AApi->request->answer_code());
        
        $response = json_encode($AApi->request->answer, JSON_PRETTY_PRINT);

        //restore:
        self::$endpoints = clone $save_current_endpoints;

        //output & return:
        if ($print) print $response;
        return $response;

    }
}


