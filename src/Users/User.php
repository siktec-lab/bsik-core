<?php
/******************************************************************************/
// Created by: shlomo hassid.
// Release Version : 1.0.2
// Creation Date: 06/04/2020
// Copyright 2020, shlomo hassid.
/******************************************************************************/
/*****************************      DEPENDENCE      ***************************/

/******************************************************************************/
/*****************************      Changelog       ****************************
 1.0: initial
 1.0.2 :
    -> users now has merged privileges -> they take a role and user specific privileges extend them
*******************************************************************************/

namespace Siktec\Bsik\Users;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Base;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Objects\Password;

class User extends Base {
    
    const USER_TABLE        = "bsik_users";
    const SESSION_TOKEN_KEY = "usertoken";
    const SESSION_ID_KEY    = "userid";

    public $is_signed       = false;
    public Priv\GrantedPrivileges $priv;
    public $user_data       = false;
    public $errors          = [];

    /** 
     * Constructor
     */
    public function __construct() {
        $this->priv = new Priv\GrantedPrivileges();
    }  
    
    public static function get_user_session() : array {
        return Std::$arr::get_from($_SESSION ?? [], [
            self::SESSION_TOKEN_KEY, 
            self::SESSION_ID_KEY
        ]);
    }
    /**
     * user_logout
     *
     * @return void
     */
    public function user_logout() {
        //update db table:
        self::$db->where("id", $this->user_data["id"])
                 ->update(self::USER_TABLE,["e_token" => ""], 1);
        //delete stored session:
        self::delete_session([
            self::SESSION_TOKEN_KEY, 
            self::SESSION_TOKEN_KEY
        ]);
    }    

    /**
     * user_login
     * @param array $request - the array with the credentials -> defaults to the content of POST 
     * @return bool
     */
    public function user_login(array $request = []) : bool {
        //Default:
        $request = empty($request) ? $_POST : $request;
        
        //Get required credentials from the request:
        $credentials = Std::$arr::get_from($request, ["username", "password", "csrftoken"], null);

        if (Std::$arr::values_are_not($credentials)) {

            //Make sure same session call:
            if ($credentials["csrftoken"] !== self::get_session("csrftoken")) {
                $this->errors["login"] = "session";
                return false;
            }
            //Validate inputs:
            if (
                !strlen($credentials["username"]) || 
                !strlen($credentials["password"]) || 
                !filter_var($credentials["username"], FILTER_VALIDATE_EMAIL)
            ) {
                $this->errors["login"] = "error";
                return false;
            }
            //Prepare Values - we assume its an email address:
            $credentials['username'] = strtolower($credentials['username']);

            //System password:
            $Pass = new Password();
            $Pass->set_password($credentials['password'], false);

            //$hashed_password = openssl_digest(PLAT_HASH_SALT.$credentials['password'].PLAT_HASH_SALT, "sha512");

            //Check in users table:
            $user = self::$db->where("email", $credentials['username'])
                              ->where("password", $Pass->get_hash())
                              ->getOne(self::USER_TABLE);

            //Is Valid?
            if (!empty($user) && isset($user["id"])) {
                //TODO: check if user account is active:
                //Create new login token:
                $token = self::generate_user_token(
                    $credentials['password'], 
                    $user['email']
                );
                
                //Save it:
                self::$db->where("id", $user["id"])->update(self::USER_TABLE, ["e_token" => $token], 1);
                
                //Create new session:
                $this::create_session([
                    self::SESSION_TOKEN_KEY => $token, 
                    self::SESSION_ID_KEY    => $user["id"]
                ]);

                return true;

            } else {
                $this->errors["login"] = "error";
            }
        }
        return false;
    }
        
    /**
     * initial_user_login_status
     * checks whats the status of the user - if he is signed then update stuff load the user and extend the session
     * @return void
     */
    public function initial_user_login_status() {
        //First check if already signed:
        $current_seesion =self::get_user_session();

        if (Std::$arr::values_are_not($current_seesion)) {
            //User has access token
            $this->user_data = self::$db->where("a.id", $current_seesion[self::SESSION_ID_KEY])
                                         ->where("a.e_token", $current_seesion[self::SESSION_TOKEN_KEY])
                                         ->join("bsik_users_roles as b", "a.role = b.id", "LEFT")
                                         ->getOne("bsik_users as a", [
                                             "a.*", 
                                             "b.role as role_name", 
                                             "b.priv as role_priv",
                                             "b.color as role_color",
                                        ]);

            //Load privileges:
            if (!empty($this->user_data) && array_key_exists("priv", $this->user_data)) {
                $this->user_data["priv"]       = Priv\GrantedPrivileges::safe_unserialize($this->user_data["priv"]);
                $this->user_data["role_priv"]  = Priv\GrantedPrivileges::safe_unserialize($this->user_data["role_priv"]);
                $this->priv = self::merge_user_priv($this->user_data["role_priv"], $this->user_data["priv"]);
            } else {
                $this->delete_session(array_keys($current_seesion));
            }
        }
        //TODO: log user is active into DB.
        //TODO: Update user Last Seen:
        // Check if this user exists and is active
        $this->is_signed = (isset($this->user_data["account_status"]) && $this->user_data["account_status"] === 0);
        return $this->is_signed;
    }
        
    /**
     * merge_user_priv
     * a helper method to extend user inherited privileges with it own granted privileges
     * @param  GrantedPrivileges|null $from
     * @param  GrantedPrivileges|null $with
     * @return Priv\GrantedPrivileges
     */
    public static function merge_user_priv(?Priv\GrantedPrivileges $from, ?Priv\GrantedPrivileges $with) : Priv\GrantedPrivileges {
        //If nothing:
        if (is_null($from) && is_null($with)) {
            return new Priv\GrantedPrivileges(); // Return just an empty container.
        }
        //If only one of them:
        if (is_null($from) || is_null($with)) {
            return is_null($from) ? $with : $from;
        }
        //merge them:
        $from->update($with);
        return $from;
    }    
    /**
     * user_identifier
     * get a string that represents the current signed user
     * @return string
     */
    public function user_identifier() : string {
        if ($this->is_signed) {
            return ($this->user_data["id"] ?? "*").":".($this->user_data["email"] ?? "*");
        }
        return "";
    }

    
    /**
     * generate_user_token
     * return a random user token for the current session token.
     * @param  string $hashed_pass
     * @param  string $user_name
     * @return string
     */
    private static function generate_user_token(string $hashed_pass, string $user_name) : string {
        return openssl_digest(Std::$date::time_datetime().$hashed_pass.$user_name, "sha512");
    }

    /* Get the location of user .
     *  @param $ip => String Ip or Visitor -> will detect the IP
     *  @param $purpose => String ->"country", "countrycode", "state", "region", "city", "location", "address"
     *  @param $deep_detect => boolean -> whether to follow HTTP_X_FORWARDED_FOR
     *  @Default-params:
     *      - NULL,
     *      - "location",
     *      - true
     *  @return return
     *  @Examples:
     *      echo ip_info("173.252.110.27", "Country"); // United States
     *      echo ip_info("173.252.110.27", "Country Code"); // US
     *      echo ip_info("173.252.110.27", "State"); // California
     *      echo ip_info("173.252.110.27", "City"); // Menlo Park
     *      echo ip_info("173.252.110.27", "Address"); // Menlo Park, California, United States
     *      print_r(ip_info("173.252.110.27", "Location")); // Array ( [city] => Menlo Park [state] => California [country] => United States [country_code] => US [continent] => North America [continent_code] => NA )
     *
    */
    //TODO: what do we want to do with that? is that required? maybe a simple Ip save....
    public function ip_info($ip = null, $purpose = "location", $deep_detect = true) {
        $output = null;
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (($_SERVER['HTTP_X_FORWARDED_FOR'] ?? false) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (($_SERVER['HTTP_CLIENT_IP'] ?? false) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        /* SH: added - 2021-03-03 => Check if its an error - why? not equal */
        if ($ip === "127.0.0.1") 
            $ip = @file_get_contents("http://ipecho.net/plain");
        $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), "", strtolower(trim($purpose)));
        $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
        $continents = array(
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        );
        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $json_result = @file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip);
            $ipdat = @json_decode($json_result);
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
                            "city"           => @$ipdat->geoplugin_city,
                            "state"          => @$ipdat->geoplugin_regionName,
                            "country"        => @$ipdat->geoplugin_countryName,
                            "country_code"   => @$ipdat->geoplugin_countryCode,
                            "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode,
                            "timezone"       => @$ipdat->geoplugin_timezone,
                            "full"           => $json_result
                        );
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1)
                            $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1)
                            $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }
        return $output;
    }
}