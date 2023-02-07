<?php

require_once "bootstrap.php";

use \PHPUnit\Framework\TestCase;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\FsTools\BsikFileSystem;
use \Siktec\Bsik\FsTools\BsikZip;

use function PHPUnit\Framework\directoryExists;
use function PHPUnit\Framework\fileExists;
      
class FsToolsTest extends TestCase
{

    private static string $zipfolder;
    private static string $ziptoRemove = "";
    private static string $folderToClear = "";

    public static function setUpBeforeClass() : void {
        //A sample folder we are working on:
        self::$zipfolder = Std::$fs::path(__DIR__, "resources", "zipfolder");
    }

    public static function tearDownAfterClass() : void {
        //Remove temp created zip file:
        if (!empty(self::$ziptoRemove)) @unlink(self::$ziptoRemove);
    }

    public function setUp() : void {
        //Make sure the resources folder exists with the empty folder inside:
        //we do that because empty folders are not carried over by git
        $emptyfolder = Std::$fs::path(__DIR__, "resources", "zipfolder", "emptyfolder");
        if (!file_exists($emptyfolder)) {
            BsikFileSystem::mkdir($emptyfolder);
        }
    }

    public function tearDown() : void {

        
    }

    public function testListFolderRecursiveIterator() : void {
        /** @var \RecursiveIteratorIterator $list */
        $list = BsikFileSystem::list_folder(self::$zipfolder) ?? [];
        $structure = [];
        foreach ($list as $fullname => $file)
        {
            /** @var \SplFileInfo $file */
            if ($file->isFile()) {
                // print $file->getFilename()."\n";
                $structure[] = $file->getFilename();
            } elseif ($file->isDir() && $file->getFilename() === ".") {
                $dir = $file->getPathInfo();
                $structure[] = $dir->getFilename();
                // print $dir->getFilename()."\n";
            }
        }
        // Pass
        $this->assertEqualsCanonicalizing(
            ["zipfolder", "emptyfolder", "fileone.txt"],
            $structure,
            "failed iterating filesystem folder"
        );
    }

    public function testFolderZip() : void {
        
        $out = Std::$fs::path(__DIR__, "resources", "temp_zipfolder.zip");
        BsikZip::zip_folder(self::$zipfolder, $out);
        $this->assertTrue(file_exists($out), "zip file not created.");
        //Register for teardown clean:
        if (file_exists($out)) {
            self::$ziptoRemove = $out;
        }
    }

    public function testFolderUnzip() : void {
        //Only if we have a zip file:
        if (empty(self::$ziptoRemove)) {
            $this->markTestIncomplete("depends on successful zip creation test");
        }
        $to = Std::$fs::path(self::$zipfolder, "emptyfolder");
        $suc = BsikZip::extract_zip(self::$ziptoRemove, $to);
        $this->assertTrue($suc, "zip file not created.");
        if ($suc) {
            self::$folderToClear = $to;
        }
    }

    public function testClearFolder() : void {
        //Only if we have a zip file:
        if (empty(self::$folderToClear)) {
            $this->markTestIncomplete("depends on successful zip extract test");
        }
        //Clear:
        BsikFileSystem::clear_folder(self::$folderToClear);
        $files = scandir(self::$folderToClear);
        $num_files = count($files)-2;
        $this->assertTrue($num_files === 0, "failed to clear folder.");
    }

}