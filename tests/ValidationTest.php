<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\Api\Input\Validate;

class ValidationTest extends TestCase
{
    public static function setUpBeforeClass() : void {

    }

    public static function tearDownAfterClass() : void {
        
    }

    public function setUp() : void {

        \Siktec\Bsik\Impl\CoreLoader::load_packs();
    
    }

    public function tearDown() : void {

    }

    public function testFilterSanitize() : void {

        $filter = Validate::filter("sanitize", FILTER_SANITIZE_FULL_SPECIAL_CHARS)
                          ::create_filter();

        $input_string = "sik<div>tec";                   
        $input_array  = ["sik<div>tec", "siktec <b></b>", 234253];   
        
        $filtered_str = Validate::filter_input($input_string , $filter);
        $this->assertEquals("sik&lt;div&gt;tec", $filtered_str, "failed filtering simple string");

        $filtered_arr = Validate::filter_input($input_array , $filter);
        $this->assertEqualsCanonicalizing(
            ["sik&lt;div&gt;tec", "siktec &lt;b&gt;&lt;/b&gt;", ""], 
            $filtered_arr, 
            "failed filtering array"
        );
    }

    public function testFilterLengthAndPadding() : void {
        $filter = Validate::filter("max_length", 3)
                          ::filter("pad_both", 5, "-")
                          ::filter("pad_end", 6, "+")
                          ::filter("pad_start", 7, "+")
                          ::create_filter();
        $input_string = "siktec";                   
        $input_array  = ["siktec", 12345];  
        //Test over string:
        $filtered_str = Validate::filter_input($input_string , $filter);
        $this->assertEquals("+-sik-+", $filtered_str, "failed filtering simple string with length and padding");
        //Test over array:
        $filtered_arr = Validate::filter_input($input_array , $filter);
        $this->assertEqualsCanonicalizing(
            ["+-sik-+", "+-123-+"], 
            $filtered_arr, 
            "failed filtering array with length and padding"
        );
    }

    public function testFilterSanitizeEmailAndValidate() : void {

        $filter = Validate::filter("sanitize", FILTER_SANITIZE_EMAIL)
                          ::create_filter();
        $validate = Validate::condition("email")::create_rule();
                        
        $filtered_str1 = Validate::filter_input("test@gmail.com" , $filter);
        $filtered_str2 = Validate::filter_input("test2gmail.com" , $filter);
        $messages = [];
        $this->assertTrue(Validate::validate_input($filtered_str1, $validate, $messages), "failed validating true email");
        $this->assertFalse(Validate::validate_input($filtered_str2, $validate, $messages), "failed validating false email");
    }

    public function testFilterUtfStringsWithStrchars() : void {
        $filter = Validate::filter("trim")
                          ::filter("utf_names")
                          ::filter("transform_spaces", " ")
                          ::create_filter();
        $strs = [
            "siktec-platform" => "sik##tec-plat#f@o*(rm",
            "12345678" => "12345678",
            "my name"  => "my       name",
            "हिन्दी" => "हिन्दी",
            "Русский" => "Русский",
            "שלומי חסיד" => "שלומי חסיד"
        ];
        foreach ($strs as $expect => $str) {
            $this->assertEquals(
                $expect,
                Validate::filter_input($str, $filter)
            );
        }
                        
    }

}