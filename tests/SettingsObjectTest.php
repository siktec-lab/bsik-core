<?php

require_once "a_main.php";

use PHPUnit\Framework\TestCase;
use Bsik\Std;
use Bsik\Objects\SettingsObject;

class SettingsObjectTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
    }

    public static function tearDownAfterClass() : void
    {
    }

    public function setUp() : void
    {
    }

    public function tearDown() : void
    {
    }

    public function testInitialization() : void {
        $set = new SettingsObject(
            defaults        : [ "name" => "noname", "last" => "lastname", "age" => 10 ],
            options         : [ 
                "name" => "string:notempty", 
                "last" => "notempty", 
                "age" => "integer" 
            ],
            descriptions    : [ "name" => "appname" ],
        );

        $valid = $set->extend(["name" => "siktec"]);
        $this->assertTrue($valid, "failed simple init with one extend");
        $this->assertEquals(
            expected : "noname", 
            actual   : $set->get_default("name"), 
            message  : "failed default get value"
        );
        $this->assertEquals(
            expected : "string:notempty", 
            actual   : $set->get_option("name"), 
            message  : "failed option get value"
        );
        $this->assertEquals(
            expected : "appname", 
            actual   : $set->get_description("name"), 
            message  : "failed description get value"
        );
        $this->assertEquals(
            expected : "siktec", 
            actual   : $set->get("name"), 
            message  : "failed get extended value"
        );
        $this->assertEquals(
            expected : "lastname", 
            actual   : $set->get("last"), 
            message  : "failed get extended value fallback default"
        );

        $set->set("last", "app");
        $this->assertEquals(
            expected : "app", 
            actual   : $set->get("last"), 
            message  : "failed set specific key"
        );

        $valid = $set->set("age", "non%numeric_value");
        $this->assertFalse($valid, "failed filter integer on set()");
        $this->assertEquals(
            expected : 10, 
            actual   : $set->get("age"), 
            message  : "failed default get integer"
        );
        $res = $set->set("age", "15");
        $this->assertEquals(
            expected : 15, 
            actual   : $set->get("age"), 
            message  : "failed filter integer on set() numeric value"
        );

    }

    public function testExtendMethods() : void {
        $set = new SettingsObject(
            defaults        : [ "name" => "noname"  ],
            options         : [ "name" => "string"  ],
            descriptions    : [ "name" => "appname" ]
        );
        $set->extend_descriptions(["last" => "lastname"]);
        $this->assertEquals(
            expected : "lastname", 
            actual   : $set->get_description("last"), 
            message  : "failed extending description"
        );
        $set->extend_options(["last" => "string:notempty"]);
        $this->assertEquals(
            expected : "string:notempty", 
            actual   : $set->get_option("last"), 
            message  : "failed extending options"
        );
        $set->extend_defaults(["last" => "nolast"]);
        $this->assertEquals(
            expected : "nolast", 
            actual   : $set->get_default("last"), 
            message  : "failed extending options"
        );
        $set->extend('{"name":"siktec"}');
        $this->assertEquals(
            expected : "siktec", 
            actual   : $set->get("name"), 
            message  : "failed extending from json"
        );
    }

    public function testUnsetMethods() : void {
        $set = new SettingsObject(
            defaults        : [ "name" => "noname"  ],
            options         : [ "name" => "string"  ],
            descriptions    : [ "name" => "appname" ]
        );
        $set->extend([
            "name" => "siktec",
            "last" => "platform"
        ]);
        $this->assertEquals(
            expected : "siktec-platform", 
            actual   : $set->get("name").'-'.$set->get("last"), 
            message  : "failed extending new values not in default"
        );
        $set->extend(["name" => SettingsObject::FLAG_REMOVE]);
        $this->assertEquals(
            expected : "noname-platform", 
            actual   : $set->get("name").'-'.$set->get("last"), 
            message  : "failed unseting with flag value"
        );
    }
    public function testJsonRepresentation() : void {
        $set = new SettingsObject(
            defaults        : [ "name" => "noname"  ],
            options         : [ "name" => "string"  ],
            descriptions    : [ "name" => "appname" ]
        );
        $set->set("name", "siktec");
        $this->assertEquals(
            expected : '{"name":"noname"}', 
            actual   : $set->defaults_json(), 
            message  : "failed defaults_json serialization 1"
        );
        $this->assertEquals(
            expected : '{"name":"siktec"}', 
            actual   : $set->values_json(), 
            message  : "failed defaults_json serialization 2"
        );
        $this->assertEquals(
            expected : '{"name":"string"}', 
            actual   : $set->options_json(), 
            message  : "failed defaults_json serialization 3"
        );
        $this->assertEquals(
            expected : '{"name":"appname"}', 
            actual   : $set->descriptions_json(), 
            message  : "failed defaults_json serialization 4"
        );
    }

    public function testHelperMethods() : void {
        $set = new SettingsObject(
            defaults        : [ "name" => "noname"  ],
            options         : [ "name" => "string"  ],
            descriptions    : [ "name" => "appname" ]
        );
        $set->set_option("allow", "boolean" );
        $set->set_option("decline", "boolean" );
        $set->set("allow", "true" );
        $set->set("decline", "FALSE" );

        $this->assertTrue(
            condition   : $set->is_true("allow"), 
            message     : "failed is_true boolean check"
        );
        $this->assertTrue(
            condition   : $set->is_false("decline"), 
            message     : "failed is_false boolean check"
        );
        $this->assertTrue(
            condition   : $set->is("name", "noname"), 
            message     : "failed is equals value check"
        );
        $this->assertTrue(
            condition   : $set->is_defined(["allow", "name"]) && $set->is_defined("decline"), 
            message     : "failed is is_defined check"
        );
    }
    public function testImportMethod() : void {
        $set = new SettingsObject();
        $set->import([
            "values"        => [ "name" => "siktec" ],
            "options"       => [ "name" => "string" ],
            "descriptions"  => [ "name" => "the name" ]
        ]);

        $this->assertEquals(
            expected : 'siktec', 
            actual   : $set->get("name"), 
            message  : "failed import settings"
        );

        $this->assertEquals(
            expected : 'string', 
            actual   : $set->get_option("name"), 
            message  : "failed import settings"
        );

        $this->assertEquals(
            expected : 'the name', 
            actual   : $set->get_description("name"), 
            message  : "failed import settings"
        );
    }
}
