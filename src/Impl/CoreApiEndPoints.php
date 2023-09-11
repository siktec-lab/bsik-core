<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Api\Endpoint\ApiEndPoint;
use \Siktec\Bsik\Api\Input\Validate;
use \Siktec\Bsik\Builder\Components;


/****************************************************************************/
/**********************  CORE ADMIN API METHODS  ****************************/
/****************************************************************************/

/******************************  Get from tabels  ***************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module  : "core",
    name    : "get_from_table", 
    params : [ // Defines the expected params with there defaults.
        "table_name" => null, //null indicates no default.
        "fields"     => [],
        "limit"      => '10'
    ],
    filter : [ // Defines filters to apply -> this will modify the params.
        "table_name" => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_")::create_filter(),
        "fields"     => Validate::filter("trim")::create_filter(),
        "limit"      => Validate::filter("type", "number")::create_filter()
    ],
    validation : [ // Defines Validation rules of this endpoint.
        "table_name" => Validate::condition("required")::condition("type","string")::create_rule(),
        "fields"     => Validate::condition("type","array")::create_rule(),
        "limit"      => Validate::condition("type","integer")::create_rule()
    ],
    //The method to execute -> has Access to BsikApi
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        try {
            $data = $Api::$db->get($args["table_name"], $args["limit"], $args["fields"]);
            $Api->request->update_answer_status(200);
        } catch (Exception $e) {
            $data = ["message" => $e->getMessage()];
            $Api->request->update_answer_status(500);
        }
        $Api->request->answer_data($data);
        return true;
    },
    working_dir     : dirname(__FILE__),
    allow_global    : true,
    allow_external  : true,
    allow_override  : false
));

/****************************************************************************/
/**********************  LOAD MODULE DEFINED API  ***************************/
/****************************************************************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module  : "core",
    name    : "get_for_datatable", 
    params  : [ // Defines the expected params with there defaults.
        "table_name"        => null,
        "order"             => null, //null indicates no default.
        "search"            => "",
        "searchable"        => [],
        "sort"              => null,
        "fields"            => ["*"],
        "limit"             => 10,
        "offset"            => 0,
        "component_mutator" => ""
    ],
    filter : [ // Defines filters to apply -> this will modify the params.
        "table_name"    => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_", ">", ":")::create_filter(),
        "search"        => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_", " ", "-", "@", ".", ",")::create_filter(),
        "fields"        => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_")::create_filter(),
        "order"         => Validate::filter("type", "string")::filter("trim")::create_filter(),
        "sort"          => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_")::create_filter(),
        "limit"         => Validate::filter("type", "number")::create_filter(),
        "offset"        => Validate::filter("type", "number")::create_filter(),
        "searchable"    => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_")::create_filter(),
        "component_mutator" => Validate::filter("trim")::filter("strchars", "A-Z", "a-z", "0-9", "_", ":", "-")::create_filter()
    ],
    validation : [ // Defines Validation rules of this endpoint.
        "table_name"        => Validate::condition("required")::condition("type", "string")::create_rule(),
        "search"            => Validate::condition("type", "string")::create_rule(),
        "fields"            => Validate::condition("type", "array")::create_rule(),
        "searchable"        => Validate::condition("type", "array")::create_rule(),
        "component_mutator" => Validate::condition("optional")::condition("type", "string")::create_rule()
    ],
    //The method to execute -> has Access to BsikApi
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {

        $data       = [];
        $tables     = explode(">", $args["table_name"]);
        $table      = $tables[0];
        $count      = $tables[1] ?? null;
        $db_conn_table  = explode(":", $table);
        $connection = count($db_conn_table) > 1 ? $db_conn_table[0] : "default";
        $table      = count($db_conn_table) > 1 ? $db_conn_table[1] : $db_conn_table[0];
        $count      = is_null($count) ? $table : $count;
        $search     = $args["search"];
        $where      = []; // currently used only by component mutator
        $searchable = $args["searchable"];
        $fields     = $args["fields"];
        $sort       = $args["sort"];
        $order      = $args["order"];
        $limit      = $args["limit"];
        $offset     = intval($args["offset"] ?? 0);
        $mutator    = $args["component_mutator"];

        //Fix offset: 
        $offset = $offset !== 0 ? ($offset / $limit) + 1 : 1;

        // Filter fields which are null or empty:
        $fields = array_filter($fields, fn($col) => $col !== "NULL" && $col !== "null" && !empty($col));

        //Set search term:
        $search = !empty($search) && !empty($searchable) ? 
            [   
                "term" => "%".$search."%", 
                "in-columns" => array_intersect($searchable, $fields)
            ] : [];
        
        //Load to db query builder:
        $build_where = function(&$Api, $conn, $wheres) {
            if (is_array($wheres)) {
                foreach ($wheres as $_where) {
                    if (is_array($_where))
                        $Api::$db->connection($conn)->where(...$_where);
                }
            }
        };

        //Load to db query builder:
        $build_search = function(&$Api, $conn, $_search) {
            if (!empty($_search)) {
                $wheres = [];
                $params = [];
                foreach ($_search["in-columns"] as $i => $col) {
                    $wheres[] = "$col LIKE ?";
                    $params[] = $_search["term"];
                }
                if (!empty($wheres)) {
                    $Api::$db->connection($conn)->where(" ( ".implode(" OR ", $wheres)." ) ", $params);
                }
            }
        };

        // Build order by:
        $build_order = function(&$Api, $conn, $_sort, $_order) {
            if (!empty($_sort)) {
                $Api::$db->connection($conn)->orderBy($_sort, $_order);
            }
        };

        // Build limit:
        $build_limit = function(&$Api, $conn, $_limit) {
            if (!empty($_limit)) {
                $Api::$db->connection($conn)->pageLimit = $_limit;
            }
        };

        // Mutate query:
        $component = "";
        if (!empty($mutator)) {
            $mutator = explode(":", $mutator);
            $module = $mutator[0];
            $component = $mutator[1] ?? "";
            if (!empty($module)) {
                try {
                    Components::import_from($module);
                } catch (Exception $e) {
                    $Endpoint->log_error(
                        message : "error while importing module mutator components for dynamic table",
                        context : ["error" => $e->getMessage(), "table" => $table, "module" => $module, "mutator" => $mutator]
                    );
                    $Api->request->update_answer_status(500, $e->getMessage());
                    return false;
                }
            }
        }

        $query_control = [
            "where"  => true,
            "search" => true,
            "order"  => true,
            "limit"  => true
        ];
        if ($component && Components::is_registered($component)) {
            try {
                // All are passed by reference
                // We expect the component to return a query control array
                $com = Components::get($component);
                if (is_callable($com)) {
                    $control = $com(
                        $Api::$db->connection($connection), 
                        $table, 
                        $where,
                        $search, 
                        $sort, 
                        $order, 
                        $limit
                    );
                } else {
                    throw new Exception("Component mutator must be callable", \E_PLAT_ERROR);
                }
                if (!is_array($control)) {
                    throw new Exception("Component mutator must return an array of query control", \E_PLAT_ERROR);
                }

                
                //Extend:
                $query_control = BsikStd\Arrays::extend($query_control, $control);

            } catch (Exception $e) {
                $Endpoint->log_error(
                    message : "error while executing component mutator for dynamic table",
                    context : ["error" => $e->getMessage(), "table" => $table, "component" => $component, "mutator" => $mutator]
                );
                $Api->request->update_answer_status(500, $e->getMessage());
                return false;
            }
        }

        try {

            // Prepare query:
            if ($query_control["where"]) {
                $build_where($Api, $connection, $where);
            }
            if ($query_control["search"]) {
                $build_search($Api, $connection, $search);
            }
            if ($query_control["order"]) {
                $build_order($Api, $connection, $sort, $order);
            }
            if ($query_control["limit"]) {
                $build_limit($Api, $connection, $limit);
            }

            //Get data:
            $data = $Api::$db->connection($connection)->get($table, [
                $limit * ($offset - 1),
                $limit
            ], $fields);

            $Api->request->add_debug_data([
                "queries" => [ "get" => $Api::$db->getLastQuery() ]
            ]);

            //Get total:
            if ($query_control["where"]) {
                $build_where($Api, $connection, $where);
            }
            if ($query_control["search"]) {
                $build_search($Api, $connection, $search);
            }
            

            $total = $Api::$db->connection($connection)->getValue($count, "count(*)");

            $Api->request->append_debug_data("queries", $Api::$db->getLastQuery(), "total");


            $Api->request->update_answer_status(200);

        } catch (Exception $e) {
            $Endpoint->log_error(
                message : "error while executing sql query for dynamic table",
                context : [
                    "table" => $table, 
                    "mysql" => $e->getMessage(),
                    "query" => $Api::$db->getLastQuery()
                ]
            );
            $total = 0;
            $Api->request->update_answer_status(500, $e->getMessage());
        }
        $Api->request->answer_data([
            "rows"      => $data,
            "total"     => $total,
            "search"    => $search
        ]);
        return true;
    },
    working_dir     : dirname(__FILE__),
    allow_global    : true,
    allow_external  : true,
    allow_override  : false
));

/****************************************************************************/
/**********************  FRONT-END API GATE   ***************************/
/****************************************************************************/
AdminApi::register_endpoint(new ApiEndPoint(
    module  : "core",
    name    : "front", 
    params : [ // Defines the expected params with there defaults.
        "endpoint" => null, //null indicates no default.
        "request"  => []
    ],
    filter : [ // Defines filters to apply -> this will modify the params.
        "endpoint" => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_", ".", "-")::create_filter(),
        "request"  => Validate::filter("type", "array")::create_filter(),
    ],
    validation : [ // Defines Validation rules of this endpoint.
        "endpoint" => Validate::condition("required")::condition("type","string")::create_rule(),
        "request"  => Validate::condition("optional")::condition("type","array")::create_rule()
    ],
    //The method to execute -> has Access to BsikApi
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        $Api->request->answer_data([
            "say" => "hello from api"
        ]);
        return true;
    },
    working_dir     : dirname(__FILE__),
    allow_global    : true,
    allow_external  : true,
    allow_override  : false
));