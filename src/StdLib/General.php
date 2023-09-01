<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\StdLib;


/**********************************************************************************************************
* General Methods:
**********************************************************************************************************/
class General {

    /**
     * print_pre
     * useful print variables in a pre container
     * @param  mixed $out = packed values
     * @return void
     */
    final public static function print_pre(...$out) {
        print "<pre>";
        foreach ($out as $value) 
            print_r($value);
        print "</pre>";
    }

}