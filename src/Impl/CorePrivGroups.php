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

RegisteredPrivGroup::register("\Bsik\Privileges\PrivGod");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivAccess");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivUsers");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivAdmins");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivRoles");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivContent");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivModules");

RegisteredPrivGroup::register("\Bsik\Privileges\PrivCore");
