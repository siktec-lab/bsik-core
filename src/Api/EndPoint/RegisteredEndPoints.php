<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
 1.0.0:
 ->initial
 *******************************************************************************/

namespace Siktec\Bsik\Api\EndPoint;

/**
 * RegisteredEndPoints
 * 
 * This class is used to store all registered endpoints.
 * 
 * @package Siktec\Bsik\Api\Endpoint
 */
class RegisteredEndPoints {

    private array $endpoints = [];

    // Implement __get() method.
    public function __get(string $name) {
        if (isset($this->endpoints[$name])) {
            return $this->endpoints[$name];
        }
        return null;
    }

    // Implement __set() method.
    public function __set(string $name, $value) {
        $this->endpoints[$name] = $value;
    }

    // Implement __isset() method.
    public function __isset(string $name) {
        return array_key_exists($name, $this->endpoints);
    }

    // Implement __unset() method.
    public function __unset(string $name) {
        unset($this->endpoints[$name]);
    }

    // get all endpoints names
    public function get_all_names() : array {
        return array_keys($this->endpoints);
    }
}