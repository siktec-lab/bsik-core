<?php

namespace Siktec\Bsik\FsTools;

use \Exception;
use \SplFileInfo;
use \ZipArchive;

class BsikZip {


    final public static function zip_status_message($status) : string {
        switch( (int) $status )
        {
            case ZipArchive::ER_OK           : return 'N No error';
            case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
            case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
            case ZipArchive::ER_SEEK         : return 'S Seek error';
            case ZipArchive::ER_READ         : return 'S Read error';
            case ZipArchive::ER_WRITE        : return 'S Write error';
            case ZipArchive::ER_CRC          : return 'N CRC error';
            case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
            case ZipArchive::ER_NOENT        : return 'N No such file';
            case ZipArchive::ER_EXISTS       : return 'N File already exists';
            case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
            case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
            case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
            case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
            case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
            case ZipArchive::ER_EOF          : return 'N Premature EOF';
            case ZipArchive::ER_INVAL        : return 'N Invalid argument';
            case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
            case ZipArchive::ER_INTERNAL     : return 'N Internal error';
            case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
            case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
            case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
            default: return sprintf('Unknown status %s', $status );
        }
    }   

    /** 
     * list_files - list all files with name and index - keys are unique full path.
     * @param ZipArchive $zip
     * @return array => empty on zip not loaded
     */
    final public static function list_files(ZipArchive $zip) : array {
        $list = [];
        if ($zip->filename && $zip->status === ZipArchive::ER_OK) { //small workaround hack to check its intialized 
            //List the files in zip:
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $list[$stat['name']] = [
                    "name" => basename($stat['name']),
                    "index" => $i
                ];
            }
        }
        return $list;
    }

    /** 
     * zip_folder - loads the archive into memory
     * 
     * @param string $path  => the path to the folder to zip
     * @param string $out   => the zip full name (path + name)
     * @throws Exception    => E_PLAT_ERROR on zip cant be opened from 'open_zip'.
     * @return bool   => true on saved
     */
    final public static function zip_folder(string $path, string $out) : bool {
        $zip = self::open_zip($out, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        /** @var \RecursiveIteratorIterator $files */
        $origin_path = new SplFileInfo($path);
        $files = BsikFileSystem::list_folder($origin_path) ?? [];
        foreach ($files as $name => $file) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($origin_path->getRealPath()) + 1);
            if (!$file->isDir()) {
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            } elseif ($relativePath !== false) {
                //Create empty folder:
                $zip->addEmptyDir($relativePath);
            }
        }
        // Zip archive will be created only after closing object
        return $zip->close();
    }

    /** 
     * open_zip - loads the archive into memory
     * 
     * @param string $path => the zip full name (path + name)
     * @param ?int $flags => ZipArchive FLAGS
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return ZipArchive => the zip object
     */
    final public static function open_zip(string $path, int $flags = 0) : ZipArchive {
        $zip = new ZipArchive();
        $result = $zip->open($path, $flags);
        if ($result !== true) {
            $error = self::zip_status_message($result);
            throw new Exception("Module zip file cant be opened [{$error}]", E_PLAT_ERROR);
        }
        return $zip;
    }

    /** 
     * extract - extract the loaded zip archive to a folder
     * 
     * @param ZipArchive|string $zip
     * @param string $to
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return bool      => the extracted path
     */
    final public static function extract_zip(ZipArchive|string $zip, string $to, int $flags = 0) : bool {
        $close = false;
        if (is_string($zip)) {
            $zip = self::open_zip($zip, $flags);
            $close = true;
        }
        $success = $zip->extractTo($to);
        if ($close) $zip->close();
        return $success;
    }
}
