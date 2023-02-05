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
 * PrivRoles
 * a special group that sets privileges for roles and role management
 */
class PrivRoles extends PrivGroup {

	public const NAME = "roles";

	//The group meta
	public const ICON  			= "fa-shield-alt";
	public const DESCRIPTION  	= "Grants privileges perform core roles related tasks";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"grant" 	=> null
	];
	
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $grant = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("grant", 	$grant);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION,
		];
	} 
}
