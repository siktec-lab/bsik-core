<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/
namespace Siktec\Bsik;

use \Siktec\Bsik\StdLib;

/**********************************************************************************************************
* BSIK Std:
**********************************************************************************************************/
class Std {
    public static StdLib\Std_String        $str;
    public static StdLib\Std_Object        $obj;
    public static StdLib\Std_Array         $arr;
    public static StdLib\Std_Url           $url;
    public static StdLib\Std_Date          $date;
    public static StdLib\Std_FileSystem    $fs;
    public static StdLib\Std_General       $gen;
}

Std::$str       = new StdLib\Std_String;
Std::$obj       = new StdLib\Std_Object;
Std::$arr       = new StdLib\Std_Array;
Std::$url       = new StdLib\Std_Url;
Std::$date      = new StdLib\Std_Date;
Std::$fs        = new StdLib\Std_FileSystem;
Std::$gen       = new StdLib\Std_General;