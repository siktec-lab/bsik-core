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

use \Siktec\Bsik\Privileges\RegisteredPrivGroup;

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivGod");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivAccess");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivUsers");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivAdmins");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivRoles");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivContent");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivModules");

RegisteredPrivGroup::register("\Siktec\Bsik\Privileges\Default\PrivCore");
