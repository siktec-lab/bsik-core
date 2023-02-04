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
 * PrivContent
 * a content specific privileges
 */
class PrivContent extends PrivGroup {

	public const NAME = "content";

	//The group meta
	public const ICON  			= "fa-book";
	public const DESCRIPTION  	= "Grants privileges to perform operations related to content published and managed on the platform.";
	public array  $privileges = [
		"view" 		=> null,
		"edit" 		=> null,
		"create" 	=> null,
		"delete" 	=> null,
		"upload" 	=> null,
		"download" 	=> null,
		"cache" 	=> null
	];
	
	public function __construct(?bool $view = null, ?bool $edit = null, ?bool $create = null, ?bool $delete = null, ?bool $upload = null, ?bool $download = null, ?bool $cache = null)
	{
		$this->set_priv("view", 	$view);
		$this->set_priv("edit", 	$edit);
		$this->set_priv("create", 	$create);
		$this->set_priv("delete", 	$delete);
		$this->set_priv("upload", 	$upload);
		$this->set_priv("download",	$download);
		$this->set_priv("cache", 	$cache);
	}
    
	public static function meta() {
		return [
			"name" 			=> self::NAME,
			"icon" 			=> self::ICON,
			"description" 	=> self::DESCRIPTION
		];
	} 
}