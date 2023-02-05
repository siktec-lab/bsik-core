<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.4
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
********************************************************************************/
namespace Siktec\Bsik\Privileges;

/**
 * RegisteredPrivGroup - registers all PrivGroup classes
 * 
 * @package Siktec\Bsik\Privileges
 */
class RegisteredPrivGroup {

	public static array $registered = [];
	
	public static function register(mixed $group) {
		if (
			!empty($group::NAME) &&									// Not empty name which will break everything ?
			class_exists($group) &&  								// Known class ?
			!array_key_exists($group::NAME, self::$registered)     // Not registered ?
		)
			self::$registered[$group::NAME] = $group;
	}

	public static function dump() {
		var_dump(self::$registered);
	}

	public static function get_class(string $name) : string|null {
		return self::$registered[$name] ?? null;
	}

}
