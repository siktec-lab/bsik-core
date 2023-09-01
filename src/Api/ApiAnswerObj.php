<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/

namespace Siktec\Bsik\Api;


/** 
 * ApiAnswerObj 
 * 
 * this class is used to store the answer of an api request
 * 
 * @package Bsik\Api
 */
class ApiAnswerObj {

    // The code of the answer
    public int    $code     = 0;
    
    // The message of the answer string
    public string $message  = "";
    
    // Array of errors if any
    public array  $errors   = []; 
    
    // Array of debug data if any
    public array  $debug    = [
        "endpoints-trace"   => []
    ]; 
    
    // Array of data if any
    public array  $data    = []; 
    
    // Constructor
    public function __construct() {

    }
}
