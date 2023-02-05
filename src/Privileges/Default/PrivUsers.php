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
 * PrivUsers
 * a user specific privileges
 */
class PrivUsers extends PrivGroup {

	public const NAME = "users";

	//The group meta
	public const ICON  			= "fa-user-lock";
	public const DESCRIPTION  	= "Grants privileges to perform operations related to users (not admins) management across the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"interact" 	=> null
	];
	
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $interact = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("interact", $interact);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}
