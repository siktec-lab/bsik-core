<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.4
// Creation Date: 2021-03-18
// Copyright 2021, SIKTEC.
/******************************************************************************/

namespace Siktec\Bsik\Privileges;

/**
 * GrantedPrivileges
 * a definition used for issuer entities such as users
 */
class GrantedPrivileges extends PrivDefinition {

	/**
	 * has_privileges
	 * return whether this entity can has privileges to use / access the given definition 
	 * @param  PrivDefinition $against
	 * @param  array $messages - will be filled with messages of required privileges if they are missing
	 * @return bool
	 */
	public function has_privileges(PrivDefinition $against, array &$messages = []) : bool {
		return self::check($against, $this, $messages);
	}
}
