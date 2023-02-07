<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\Objects\Password;
      
class PasswordObjTest extends TestCase
{
    public static function setUpBeforeClass() : void {

    }

    public static function tearDownAfterClass() : void {
        
    }

    public function setUp() : void {

    }
    public function tearDown() : void {

    }

    public function testValidatePassword() : void {

        $Pass = new Password(min_length : 5);
        $test1 = $Pass->validate_password("test");
        $test2 = $Pass->validate_password("testTE23$#");
        $this->assertTrue($test2, "legal password failed");
        $this->assertFalse($test1, "illegal password failed");
    }

}