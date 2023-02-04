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
 * PrivCore
 * a core operations specific privileges
 */
class PrivCore extends PrivGroup {

	public const NAME = "core";

	//The group meta
	public const ICON  			= "fa-code";
	public const DESCRIPTION  	= "Grants privileges to perform sensible platform core operations.";
	public array  $privileges = [
		"view" 		=> null,
		"install" 	=> null,
		"activate" 	=> null,
		"settings" 	=> null,
		"update" 	=> null
	];
	
	public function __construct(?bool $view = null, ?bool $install = null, ?bool $activate = null, ?bool $settings = null, ?bool $update = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("install", 	$install);
		$this->set_priv("activate", $activate);
		$this->set_priv("settings", $settings);
		$this->set_priv("update", 	$update);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
