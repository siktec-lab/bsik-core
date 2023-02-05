<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.2
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->initial
********************************************************************************/
namespace Siktec\Bsik\Privileges\Default;

use \Siktec\Bsik\Privileges\PrivGroup;

/**
 * PrivGod
 * a special group that sets god privileges
 */
class PrivGod extends PrivGroup {

	public const NAME = "god";

	//The group meta
	public const ICON  			= "fa-unlock-alt";
	public const DESCRIPTION  	= "Grants all privileges and overwrites any restrictions on the system";
	public array  $privileges = [
		"grant" => null
	];

	public function __construct(?bool $grant = null)
	{
		$this->set_priv("grant", $grant);
	}
	
    public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
