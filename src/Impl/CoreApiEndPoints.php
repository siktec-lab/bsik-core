<?php

use \Siktec\Bsik\Api\AdminApi;
use \Siktec\Bsik\Api\Endpoint\ApiEndPoint;
use \Siktec\Bsik\Api\Input\Validate;

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
        "table_name"    => null,
        "order"         => null, //null indicates no default.
        "search"        => "",
        "searchable"    => [],
        "sort"          => null,
        "fields"        => ["*"],
        "limit"         => 10,
        "offset"        => 0,
    ],
    filter : [ // Defines filters to apply -> this will modify the params.
        "table_name"    => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_")::create_filter(),
        "search"        => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9", "_", " ")::create_filter(),
        "fields"        => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_")::create_filter(),
        "order"         => Validate::filter("type", "string")::filter("trim")::create_filter(),
        "sort"          => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_")::create_filter(),
        "limit"         => Validate::filter("type", "number")::create_filter(),
        "offset"        => Validate::filter("type", "number")::create_filter(),
        "searchable"    => Validate::filter("trim")::filter("strchars","A-Z","a-z","0-9","_")::create_filter()
    ],
    validation : [ // Defines Validation rules of this endpoint.
        "table_name"    => Validate::condition("required")::condition("type","string")::create_rule(),
        "search"        => Validate::condition("type","string")::create_rule(),
        "fields"        => Validate::condition("type","array")::create_rule(),
        "searchable"    => Validate::condition("type","array")::create_rule()
    ],
    //The method to execute -> has Access to BsikApi
    method : function(AdminApi $Api, array $args, ApiEndPoint $Endpoint) {
        $data = [];
        $table  = $args["table_name"];
        $search = $args["search"];
        $searchable = $args["searchable"];
        $fields = $args["fields"];
        $sort   = $args["sort"];
        $order  = $args["order"];
        $limit  = $args["limit"];
        $offset = intval($args["offset"] ?? 0);

        //Fix offset: 
        $offset = $offset !== 0 ? ($offset / $limit) + 1 : 1;
        
        //Set search term:
        //$search = !empty($search) && !empty($fields) ? ["term" => "%".$search."%", "in-columns" => $fields] : [];
        $search = !empty($search) && !empty($searchable) ? 
            [   
                "term" => "%".$search."%", 
                "in-columns" => array_filter(array_intersect($searchable, $fields), fn($col) => $col !== "NULL" && $col !== "null" && !empty($col))
            ] : [];
        if (!empty($search)) {
            $where = [];
            $params = [];
            foreach ($search["in-columns"] as $i => $col) {
                $where[] = "$col LIKE ?";
                $params[] = $search["term"];
            }
            if (!empty($where)) {
                $Api::$db->where(" ( ".implode(" OR ", $where)." ) ", $params);
            }
        }
        //Sort results:
        if (!empty($sort)) {
            $Api::$db->orderBy($sort, $order);
        }
        //Limit page results:
        if (!empty($limit)) {
            $Api::$db->pageLimit = $limit;
        }
        try {
            $data = $Api::$db->paginate($table, $offset, $fields);
            $Api->request->update_answer_status(200);
        } catch (Exception $e) {
            $Endpoint->log_error(
                message : "error while executing sql query for dynamic table",
                context : ["table" => $table, "mysql" => $e->getMessage()]
            );
            $Api->request->update_answer_status(500, $e->getMessage());
        }
        $Api->request->answer_data([
            "rows"      => $data,
            "total"     => $Api::$db->totalPages * $limit,
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