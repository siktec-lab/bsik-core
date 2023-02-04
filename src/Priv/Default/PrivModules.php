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
 * PrivModules
 * a module specific privileges
 */
class PrivModules extends PrivGroup {

	public const NAME = "modules";

	//The group meta
	public const ICON  			= "fa-puzzle-piece";
	public const DESCRIPTION  	= "Grants privileges to manage modules on the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"install" 	=> null,
		"activate" 	=> null,
		"settings" 	=> null,
		"endpoints" => null
	];
	
	public function __construct(?bool $view = null, ?bool $install = null, ?bool $activate = null, ?bool $settings = null, ?bool $endpoints = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("install", 	$install);
		$this->set_priv("activate", $activate);
		$this->set_priv("settings", $settings);
		$this->set_priv("endpoints", $endpoints);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}