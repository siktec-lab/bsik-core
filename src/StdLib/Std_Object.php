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
namespace Siktec\Bsik\StdLib;

/**********************************************************************************************************
* Object Methods:
**********************************************************************************************************/
class Std_Object {

    /**
     * objectToArray
     * This method returns the array corresponding to an object, including non public members.
     * If the deep flag is true, is will operate recursively, otherwise (if false) just at the first level.
     *
     * @param object $obj
     * @param bool $deep = true
     * @return array
     * @throws \Exception
     */
    public static function to_array(object $obj, bool $deep = true, array $filter = []) : array {
        $reflectionClass = new \ReflectionClass(get_class($obj));
        $array = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $val = $property->getValue($obj);
            if (true === $deep && is_object($val)) {
                $val = self::to_array($val, $deep, $filter);
            }
            if (!in_array($property->getName(), $filter))
                $array[$property->getName()] = $val;
            $property->setAccessible(false);
        }
        return $array;
    }

}
