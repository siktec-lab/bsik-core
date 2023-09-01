<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Render\Pages;

class FrontPageHttpHeaders {

    public static $str = [
        'OK'                        => 200,
        'Created'                   => 201,
        'Accepted'                  => 202,
        'No Content'                => 204,
        'Not Modified'              => 304,
        'Bad Request'               => 400,
        'Forbidden'                 => 403,
        'Not Found'                 => 404,
        'Method Not Allowed'        => 405,
        'Unsupported Media Type'    => 415,
        'Upgrade Required'          => 426,
        'Internal Server Error'     => 500,
        'Not Implemented'           => 501
    ];

    final public static function getCodeOf(string $mes) {
        return self::$str[$mes] ?? 0;
    }

    final public static function getMessageOf(int $code) {
        return array_search($code, self::$str);
    }

    final public static function send_response_code(int $code) : bool {
        return http_response_code($code);
    }
    
}
