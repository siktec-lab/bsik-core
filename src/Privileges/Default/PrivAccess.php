<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.2
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/

namespace Siktec\Bsik\Privileges\Default;

use \Siktec\Bsik\Privileges\PrivGroup;

/**
 * PrivAccess
 * a special group that sets god privileges
 */
class PrivAccess extends PrivGroup {

	public const NAME = "access";

	//The group meta
	public const ICON  			= "fa-door-open";
	public const DESCRIPTION  	= "Grants access to the 3 core places in the system.";
	public array  $privileges = [
		"manage" 	=> null,
		"front"  	=> null,
		"product"  	=> null
	];
	
	public function __construct(?bool $manage = null, ?bool $front = null, ?bool $product = null)
	{
		$this->set_priv("manage", 	$manage);
		$this->set_priv("front", 	$front);
		$this->set_priv("product", 	$product);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	}
	 
}