<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\StdLib;

/**********************************************************************************************************
* Dates Helper Methods:
**********************************************************************************************************/
class Dates {

    /**
     * time_datetime
     * return a time stamp in a pre defined format
     * @param  string $w - the format to use
     * @return string|bool -> string or false when error
     */
    final public static function time_datetime(string $w = "now-str") : string|bool {
        switch ($w) {
            case "now-str" :
                return date('Y-m-d H:i:s');
            case "now-mysql" :
                return date('Y-m-d H:i:s');
            default:
                return date($w);
        }
    }

}
