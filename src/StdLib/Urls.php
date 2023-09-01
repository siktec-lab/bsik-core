<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\StdLib;

/**********************************************************************************************************
* Urls Methods:
**********************************************************************************************************/
class Urls {
    
    /**
     * normalize_slashes
     * replaces backslashes in url string
     * @param  string|array $url
     * @return string|array
     */
    public static function normalize_slashes(string|array $url) : string|array {
        return str_replace('\\', '/', $url);
    }

}
