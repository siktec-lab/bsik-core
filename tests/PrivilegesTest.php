<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\Privileges as Priv;

class PrivilegesTest extends TestCase
{
    
    public static function setUpBeforeClass() : void {

    }

    public static function tearDownAfterClass() : void {
        
    }

    public function setUp() : void {

        // Register all default privileges:
        \Siktec\Bsik\Impl\CoreLoader::load_core_privileges_groups();

    }
    public function tearDown() : void {

    }

    //Check serialization of definitions:
    public function testSerializationOfPrivileges() : void {
        $required   = new Priv\RequiredPrivileges();
        $my_priv     = new Priv\GrantedPrivileges();
        $required->define(
            new Priv\Default\PrivUsers(
                edit : true, 
                create : false, 
                delete: true
            ),
            new Priv\Default\PrivGod(grant : false),
            new Priv\Default\PrivAccess(
                manage : true,
                front : false,
                product : false
            )
        );
        $my_priv->define(
            new Priv\Default\PrivUsers(
                edit    : false, 
                create  : true, 
                delete  : true
            ),
            new Priv\Default\PrivGod(grant : false),
            new Priv\Default\PrivCore(view:true)
        );
        $gate = serialize($required);
        $ask  = serialize($my_priv);
        unset($my_priv);
        unset($required);
        $gate = Priv\RequiredPrivileges::safe_unserialize($gate);
        $ask  = Priv\GrantedPrivileges::safe_unserialize($ask);
        
        $this->assertEquals(false, is_null($gate));
        $this->assertEquals(false, is_null($ask));
    }
    
    //Check for simple privileges met check:
    public function testAccessCheck() : void {
        $required   = new Priv\RequiredPrivileges();
        $required->define(
            new Priv\Default\PrivAccess(manage : true)
        );
        $my_priv_ok = new Priv\GrantedPrivileges();
        $my_priv_ok->define(
            new Priv\Default\PrivAccess(manage : true)
        );
        $my_priv_no = new Priv\GrantedPrivileges();
        $my_priv_no->define(
            new Priv\Default\PrivAccess(manage : false)
        );

        $this->assertTrue($required->has_privileges($my_priv_ok), "Access granted expected but was rejected");
        $this->assertFalse($required->has_privileges($my_priv_no), "Access should have been declined. Instead was granted.");
    }

    //Check for simple privileges met check:
    public function testIndividualTagCheck() : void {
        $policy   = new Priv\GrantedPrivileges();
        $policy->define(
            new Priv\Default\PrivUsers(
                view : true,
                edit : false
            )
        );
        $this->assertTrue($policy->group(Priv\Default\PrivUsers::NAME)->is_allowed("view"), "Expected view to be allowed but was rejected.");
        $this->assertFalse($policy->group(Priv\Default\PrivUsers::NAME)->is_allowed("edit"), "Expected edit to be declined but was granted.");
        $this->expectExceptionCode(\E_PLAT_WARNING);
        $policy->group(Priv\Default\PrivUsers::NAME)->is_allowed("nodefinedtag");
    }

    //Check Definition Simple Update from Objects:
    public function testDefinitionUpdatingFromObjects() : void {
        $required = new Priv\RequiredPrivileges();
        $my_priv = new Priv\GrantedPrivileges();
        $required->define(
            new Priv\Default\PrivUsers(
                edit : true, 
                create : false, 
                delete: true
            ),
            new Priv\Default\PrivGod(grant : false),
            new Priv\Default\PrivAccess(
                manage : true,
                front : false,
                product : false
            )
        );
        $my_priv->define(
            new Priv\Default\PrivUsers(
                edit    : false, 
                create  : true, 
                delete  : true
            ),
            new Priv\Default\PrivGod(grant : true),
            new Priv\Default\PrivCore(view:true)
        );
        $required->update($my_priv);
        unset($my_priv);
        $granted = trim(Priv\PrivDefinition::str_tag_list($required, "", " "));
        $this->assertEquals(
            "users > create,delete god > grant access > manage core > view", 
            $granted, 
            "It seems privileges are not updating as required!"
        );
    }

    //Check updating definitions with array and json:
    public function testDefinitionUpdatingFromArraysAndObjects() : void {
        $required = new Priv\RequiredPrivileges();
        
        $required->update_from_arr([
            "users"     => [],
            "core"      => [],
            "content"   => [],
        ]);

        // defined but none allowed:
        $defined_but_empty = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " "));
        $this->assertEmpty($defined_but_empty, "should not have any granted tags.");
        
        $required->update_from_arr([
            "users"     => [ "view"     => true   ],
            "core"      => [ "install"  => true   ],
            "content"   => [ "notdefined" => true ], // This should be defined but none granted
            "access"    => [ "manage" => true     ]
        ]);

        // added permissions some ignored:
        $granted_some_ignored = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "users > view core > install access > manage",
            $granted_some_ignored,
            "update from array failed."
        );

        $required->update_from_json(json_encode([
            "users"     => [ "view"     => true   ],
            "core"      => [ "install"  => false  ],
            "access"    => [ "manage" => true     ]
        ]));
        // removed permissions:
        $some_removed = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "users > view access > manage",
            $some_removed,
            "update from json failed - maybe not removing privileges."
        );
        
        $new_from_other = new Priv\RequiredPrivileges();
        $new_from_other->define(new Priv\Default\PrivModules(
            view:true
        ));
        $new_from_other->define(new Priv\Default\PrivUsers(
            view : false
        ));
        $required->update($new_from_other);
        // removed permissions:
        $more_removed_from_obj = trim(Priv\RequiredPrivileges::str_tag_list($required, "", " ")); 
        $this->assertEquals(
            "access > manage modules > view",
            $more_removed_from_obj,
            "update from object failed - tested remove and add privileges by update."
        );
    }

    //test get all methods of definitions:
    public function testGetAllMethodsOfDefinitions() : void {
        $new_from_other = new Priv\RequiredPrivileges();
        $new_from_other->define(new Priv\Default\PrivModules(
            view:true
        ));
        $new_from_other->define(new Priv\Default\PrivUsers(
            view : false
        ));

        $all = $new_from_other->all_privileges();
        $this->assertEquals(
            [
                "modules" => [
                    "view"      => true,
                    "install"   => null,
                    "activate"  => null,
                    "settings"  => null,
                    "endpoints" => null,
                ],
                "users" => [
                    "view"      => false,
                    "edit"      => null,
                    "create"    => null,
                    "delete"    => null,
                    "interact"  => null
                ]
            ],
            $all,
            "failed get all privileges from definition"
        );
        $granted = $new_from_other->all_granted();
        $this->assertEqualsCanonicalizing(
            ["modules" => ["view"]],
            $granted,
            "failed get all granted privileges from definition"
        );
        $defined = $new_from_other->all_defined();
        $this->assertEqualsCanonicalizing(
            [
                "modules" => [
                    "view" => true,
                ],
                "users" => [
                    "view" => false,
                ]
            ],
            $defined,
            "failed get all defined and set privileges from definition"
        );
    }

    //test if then conditions:
    public function testIfThenPrivilegesConditions() : void {
        $policy = new Priv\GrantedPrivileges();
        $policy->define(    
            new Priv\Default\PrivModules(
                view        : true,
                install     : true,
                activate    : false
            ),
            new Priv\Default\PrivUsers(
                view        : true,
                interact    : true
            )
        );
        //Simple return value example:
        $simple_return = $policy->if("modules.view", "users.view")->then("granted", "declined");
        $this->assertEquals("granted", $simple_return, "failed IF->THEN simple string return value");

        //Callback return granted example:
        $callback_return = $policy->if("modules.view", "users.view")->then(function($name) { return "granted for {$name}"; }, "declined", ["name" => "siktec"]);
        $this->assertEquals("granted for siktec", $callback_return,  "failed IF->THEN DO callback return");
        
        //Callback return decline example:
        $callback_return = $policy->if("modules.activate", "users.view")->then("granted", function($name) { return "declined for {$name}"; }, ["name" => "siktec"]);
        $this->assertEquals("declined for siktec", $callback_return, "failed IF->THEN ELSE callback return");
    }

    //test extending privileges:
    public function testExtendingPrivilegesMethod() : void {
        $module = new Priv\RequiredPrivileges();
        $module->define(    
            new Priv\Default\PrivModules(
                view        : true,
                settings    : false
            ),
            new Priv\Default\PrivUsers(
                view        : true
            )
        );
        $view = new Priv\RequiredPrivileges();
        $view->define(    
            new Priv\Default\PrivGod(
                grant       : true
            ),
            new Priv\Default\PrivModules(
                settings        : true
            ),
            new Priv\Default\PrivUsers(
                interact    : true
            )
        );
        $view->extends($module);
        $result = trim(Priv\RequiredPrivileges::str_tag_list($view, "", " "));
        $this->assertEquals(
            "god > grant modules > view,settings users > view,interact",
            $result,
            "failed extending policy method"
        );
    }
}