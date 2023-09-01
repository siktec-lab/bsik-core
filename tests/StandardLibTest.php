<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\StdLib as BsikStd;

class StandardLibTest extends TestCase
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

    public function testArrayExtendHelper() : void {
        $def1 = [
            "one"  => false,
            "two"  => [ "two1" => "two" ],
            "jump" => "no" 
        ];
        $val1 = [
            "one" => [ "test" => "yes" ],
            "two" => [ "two2" => "twoo"],
            "jump" => "yes",
            "\$avoid" => "no"
        ];
        $expected1 = '{"one":{"test":"yes"},"two":{"two1":"two","two2":"twoo"},"jump":"yes"}';
        $this->assertEquals(
            $expected1, 
            json_encode(BsikStd\Arrays::extend($def1, $val1)),
            "Failed extending array - test1"
        );

        $def2 = [
            "one",
            "two",
            "three",
            "more" => [1, 2],
            "test" => [ 
                "lvl1" => [
                    "me" => "no",
                    "lvl2" => [
                        "me" => "no",    
                    ]    
                ]
            ]
        ];
        $val2 = [
            "one1",
            3 => "four",
            "more" => [3,4,5],
            "test" => [
                "lvl11" => "added",
                "lvl1" => ["lvl2" => ["me" => "yes"]]
            ]
        ];
        $expected2 = '{"0":"one1","1":"two","2":"three","more":[3,4,5],"test":{"lvl1":{"me":"no","lvl2":{"me":"yes"}},"lvl11":"added"},"3":"four"}';
        $this->assertEquals(
            $expected2, 
            json_encode(BsikStd\Arrays::extend($def2, $val2)),
            "Failed extending array - test2"
        );
    }
    // arr_validate() - test:
    public function testArrayValidateHelper() : void {
        $usecase = [
            "name"      => "siktec",
            "usable"    => true,
            "child"     => "",
            "data"      => [
                "one" => 1,
                "two" => 2,
                "three" => null
            ]
        ];
        $simple = BsikStd\Arrays::validate([
            "name"   => "string", 
            "usable" => "boolean",
            "child"  => "any"
        ], $usecase);
        $this->assertTrue($simple, "failed simple array validate helper std");

        $empty = BsikStd\Arrays::validate([
            "child" => "string:empty"
        ], $usecase);
        $this->assertFalse($empty, "failed empty check array validate helper std");

        $traversal = BsikStd\Arrays::validate([
            "data.two"   => "integer",
            "data.three" => "NULL",
        ], $usecase);
        $this->assertTrue($traversal, "failed nested check array validate helper std");

        $fn = [
            "onlystringortrue" => function($v) { return $v ? true : false; }
        ];
        $withcustom = BsikStd\Arrays::validate([
            "usable"   => "integer|boolean:onlystringortrue"
        ], $usecase, $fn);
        $this->assertTrue($withcustom, "failed check array with custom functions and mixed datatypes");

    }

    public function testArrayAdvancedValidateHelper() : void {
        $fn = [
            "minlen"   => function($v, $p, $min) { return strlen($v) >= $min; },
            "maxlen"   => function($v, $p, $max) { return strlen($v) <= $max; },
            "minmax"   => function($v, $p, $min, $max) { return strlen($v) >= $min && strlen($v) <= $max; },
            "oneof"    => function($v, $p, $opt) { return in_array($v, $opt,true); },
            "notempty" => function($v, $p) { return !empty($v); }
        ];
        $rules = [
            "modules" 	    => "array",
            "author.about"  => "string:maxlen['25']",
            "this.type" 	=> "string:oneof[['one','two']]",
            "this.title"	=> "string:notempty:minmax[10,30]",
            "this.desc" 	=> "string:maxlen[25]:minlen[5]",
            "*.num"         => "integer|string|array"
        ];
        $data = [
            "modules" => [],
            "num"     => 2,
            "author"  => [
                "about" => "bla bla", 
                "num"   => "test1",
                "deep"  => [
                    "level1" => 763,
                    "some"   => true,
                    "deeper" => "hii",
                    "num"   => [
                        "one" => 2,
                        "two" => 3
                    ]
                ]
            ],
            "this"    => [
                "num"   => [2,3],
                "type"  => "one",
                "title" => "my title my title",
                "desc"  => "my desc",
                "more"  => [
                    "num" => "test2"
                ]
            ]
        ];

        $errors = [];
        $test_good = BsikStd\Arrays::validate($rules, $data, $fn, $errors);
        $this->assertTrue($test_good, "failed check array with custom functions and mixed datatypes");
        
        // Test optional:
        $fn = [
            "optional" => function($v) { return !is_null($v) && $v !== []; },
            "equal"    => function($v, $t) { return $v === $t; }
        ];
        $optional = BsikStd\Arrays::validate(
            rules : [
                "name"   => "string:optional:equal['myname']",
                "opt"    => "string:optional:equal['test']",
            ], 
            array : [
                "name" => "myname",
            ], 
            fn : $fn,
            add_path_to_cb : false
        );
        $this->assertTrue($optional, "failed check array with optional and equal missing optional value 1");

        $optional = BsikStd\Arrays::validate(
            rules : [
                "name"   => "string:optional:equal['myname']",
                "opt"    => "string:optional:equal['test']",
            ], 
            array : [
                "opt" => "test",
            ], 
            fn : $fn,
            add_path_to_cb : false
        );
        $this->assertTrue($optional, "failed check array with optional and equal missing optional value 2");
        $optional = BsikStd\Arrays::validate(
            rules : [
                "name"   => "string:optional:equal['myname']",
                "opt"    => "string:optional:equal['test']",
            ], 
            array : [
                "opt" => "wrong",
            ], 
            fn : $fn,
            add_path_to_cb : false
        );
        $this->assertFalse($optional, "failed check array with optional and equal - wrong optional value");
    }

    //arr_path_get()
    public function testArrayPathGetterHelper() : void {

        $usecase = [
            "go"    => ["there" => "siktec1"],
            "num"   => [
                ["test" => "siktec2"],
                ["siktec3"]
            ],
            "colors" => [
                ["color" => "blue"],
                ["color" => "red"]
            ]
        ];

        $test1 = BsikStd\Arrays::path_get("go.there", $usecase);
        $this->assertEqualsCanonicalizing(["siktec1"], $test1, "failed simple path traversal get");

        $test2 = BsikStd\Arrays::path_get("num.0.test", $usecase);
        $this->assertEqualsCanonicalizing(["siktec2"], $test2, "failed path traversal get with numeric indexes");

        $test3 = BsikStd\Arrays::path_get("num.1.0", $usecase);
        $this->assertEqualsCanonicalizing(["siktec3"], $test3, "failed path traversal get with numeric indexes combined");

        $test4 = BsikStd\Arrays::path_get("colors.*.color", $usecase);
        $this->assertEqualsCanonicalizing(["blue", "red"], $test4, "failed path traversal get with wildcard extract");

        $test5 = BsikStd\Arrays::path_get("colors.name", $usecase, false);
        $this->assertFalse($test5, "failed default return value in path traversal array helper");
    }

    public function testArrayPathGetComplexQueries() {
        $complex = [
            "modules" => [],
            "num"     => 2,
            "author"  => [
                "about" => "bla bla", 
                "num"   => "test1",
                "deep"  => [
                    "level1" => 763,
                    "some"   => true,
                    "deeper" => "hii",
                    "num"   => [
                        "one" => 2,
                        "two" => 3
                    ]
                ]
            ],
            "this"    => [
                "num"   => [2,3],
                "type"  => "one",
                "title" => "my title",
                "desc"  => "my desc",
                "more"  => [
                    "num" => "test2"
                ]
            ]
        ];

        $get = BsikStd\Arrays::path_get("author.deep.deeper", $complex);
        $this->assertEqualsCanonicalizing(["hii"], $get, "failed path traversal get with deep walk");

        $get = BsikStd\Arrays::path_get("this.~.num", $complex);
        $this->assertEqualsCanonicalizing(["test2"], $get, "failed path traversal get with skip operator");
        
        $get = BsikStd\Arrays::path_get("this.*.num", $complex);
        $this->assertEqualsCanonicalizing([[2,3],"test2"], $get, "failed path traversal get with wild operator");
        
        $get = BsikStd\Arrays::path_get("*.num", $complex);
        $this->assertEqualsCanonicalizing([2,"test1",["one"=>2,"two"=>3],[2,3],"test2"], $get, "failed path traversal get with wild search from root");

        $get = BsikStd\Arrays::path_get("~.num", $complex);
        $this->assertEqualsCanonicalizing(["test1",[2,3]], $get, "failed path traversal get with skip root");

        $get = BsikStd\Arrays::path_get("*.num.two", $complex);
        $this->assertEqualsCanonicalizing([3], $get, "failed path traversal get with wild and then traverse");

        $get = BsikStd\Arrays::path_get("*.num.0", $complex);
        $this->assertEqualsCanonicalizing([2], $get, "failed path traversal get with wild and then traverse with numeric index");

        $get = BsikStd\Arrays::path_get("*.deep.~.one", $complex);
        $this->assertEqualsCanonicalizing([2], $get, "failed path traversal get with combination wild + skip + traverse");

        $get = BsikStd\Arrays::path_get("*.author.*.num", $complex);
        $this->assertEqualsCanonicalizing(["test1",["one"=>2,"two"=>3]], $get, "failed path traversal get with combination of two wilds");

    }
    //str_strip_comments()
    public function testStripCommentsFromString() : void {
        $input = <<<'EOD'
            /* comment */
            {
                //Set Name:
                "name" : "siktec" //testing
            }
            EOD;
        $expected = <<<'EOD'
            
            {
                
                "name" : "siktec" 
            }
            EOD;
        $this->assertEquals($expected, BsikStd\Strings::strip_comments($input), "failed striping comments - crucial for jsonc parsing");
    }

    //fs_get_json_file() 
    public function testGetLocalJsonFile() : void {
        $test1 = BsikStd\FileSystem::get_json_file(__DIR__.DS."resources".DS."test.jsonc") ?? [];
        $this->assertEquals("siktec", $test1["name"] ?? "fail", "failed loading local jsonc");

        $test2 = BsikStd\FileSystem::get_json_file("resources\\test.jsonc") ?? "not-found";
        $this->assertEquals("not-found", $test2, "failed gracefully failing loading local jsonc");
    }
}
