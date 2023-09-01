<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Module;

use \Siktec\Bsik\StdLib as BsikStd;
use \Siktec\Bsik\Privileges as Priv;
use \Siktec\Bsik\Objects\SettingsObject;
use \Siktec\Bsik\Render\Pages\AdminModuleRequest;

/** 
 * ModuleView
 * 
 * This defines a module view which is used to render the module in a specific way.
 * 
 * @package Bsik\Module
 * 
 */
class ModuleView {

    public string $name;
    public Priv\RequiredPrivileges $priv;
    public SettingsObject $settings;
    public ?\Closure $render = null;
    
    public function __construct(
        string $name,
        ?Priv\RequiredPrivileges $privileges = null,
        ?SettingsObject $settings = null,
    ) {
        
        //Set legal view name:
        $this->name = BsikStd\Strings::filter_string($name, AdminModuleRequest::$which_pattern);

        //Privileges:
        $this->priv = $privileges ?? new Priv\RequiredPrivileges();

        //Settings:
        $this->settings = $settings ?? new SettingsObject();

    }

}



