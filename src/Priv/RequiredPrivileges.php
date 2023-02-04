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
 * RequiredPrivileges
 * a definition used for providers entities such as api endpoints
 */
class RequiredPrivileges extends PrivDefinition 
{

	/**
	 * has_privileges
	 * return whether a given definition has privileges to access / use this definition 
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return self::check($this, $against, $messages);
	}

}