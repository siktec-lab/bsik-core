<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.1:
    ->creation - initial
*******************************************************************************/
namespace Siktec\Bsik\StdLib;

use \Siktec\Bsik\CoreSettings;

/**********************************************************************************************************
* File System Helper Methods:
**********************************************************************************************************/
class Std_FileSystem {
    
    /**
     * path
     * implodes an array to os based path
     * @param  mixed $path
     * @return string
     */
    final public static function path(...$path) : string {
        return implode(DIRECTORY_SEPARATOR, $path);
    }
        
    /**
     * path_url
     * implodes an array to a url path
     * @param  mixed $path
     * @return string
     */
    final public static function path_url(...$path) : string {
        return implode('/', $path);
    }
        
    /**
     * path_to
     * generates path and url from a given array
     * @param  string $in
     * @param  array|string $path_to_file
     * @return array
     */
    final public static function path_to(string $in, array|string $path_to_file = []) : array {
        $path = $in;
        $url  = CoreSettings::$url["full"];
        switch ($in) {
            case "root":
                $path = CoreSettings::$path["base"].DIRECTORY_SEPARATOR;
                $url  .= "/"; 
                break;
            case "templates":
                $path = CoreSettings::$path["manage-templates"].DIRECTORY_SEPARATOR;
                $url  .= "/manage/pages/templates/"; 
                break;
            case "modules":
                $path = CoreSettings::$path["manage-modules"].DIRECTORY_SEPARATOR;
                $url  .= "/manage/modules/"; 
                break;
            case "trash":
                    $path = CoreSettings::$path["manage-trash"].DIRECTORY_SEPARATOR;
                    $url  .= "/manage/trash/"; 
                    break;
            case "admin-lib-required":
                $path = CoreSettings::$path["manage-lib"].DIRECTORY_SEPARATOR."required".DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/required/"; 
                break;
            case "admin-lib":
                $path = CoreSettings::$path["manage-lib"].DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/"; 
                break;
            case "themes":
                $path = CoreSettings::$path["manage-lib"].DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR;
                $url  .= "/manage/lib/themes/"; 
                break;
            case "core":
                $path = CoreSettings::$path["manage-core"].DIRECTORY_SEPARATOR;
                $url  .= "/manage/core/"; 
                break;
            case "schema":
                $path = CoreSettings::$path["manage-core"].DIRECTORY_SEPARATOR."schema".DIRECTORY_SEPARATOR;
                $url  .= "/manage/core/schema/"; 
                break;
            case "front-pages":
                $path = CoreSettings::$path["front-pages"].DIRECTORY_SEPARATOR;
                $url  .= "/front/pages/"; 
                break;
            case "raw":
                $path = "";
                break;
        }
        //Normalize parts:
        if (!is_array($path_to_file)) {
            $path_to_file = [$path_to_file];
        }
        //Trim parts:
        array_walk($path_to_file, function(&$part){
            $part = trim($part, "\\/ ");
        });
        //Build:
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path_to_file);
        $url  = rtrim($url, "/")."/".implode("/", Std_Url::normalize_slashes($path_to_file));
        //Return:
        return ["path" => trim($path, DIRECTORY_SEPARATOR), "url" => $url];
    }
    
    /**
     * get_json_file
     * loads a json file and return its content
     * @param  mixed $path
     * @param  mixed $remove_bom
     * @param  mixed $associative
     * @return array
     */
    final public static function get_json_file($path, bool $remove_bom = true, bool $associative = true) : array|null {
        $json = trim(
            Std_String::strip_comments(@file_get_contents($path) ?: ""), 
            $remove_bom ? "\xEF\xBB\xBF \t\n\r\0\x0B" : " \t\n\r\0\x0B"
        );
        if (!empty($json)) {
            return Std_String::parse_json($json, null, $associative);
        }
        return null;
    }    

    /**
     * file_exists
     * checks wether a file exists
     * 
     * @param  mixed $in
     * @param  array|string $path_to_file
     * @return void
     */
    final public static function file_exists(string $in, array|string $path_to_file = []) 
    {
        $file = self::path_to($in, $path_to_file);
        if (file_exists($file["path"])) {
            return $file;
        }
        return false;
    }
    
    /**
     * path_exists
     * checks wether a file exists with a simple path
     * 
     * @param  array $path_to_file
     * @return void
     */
    final public static function path_exists(...$path_to_file) 
    {
        $path = implode(DIRECTORY_SEPARATOR, array_map(
            function($part){
                return trim($part, " \t\n\r\\/");
            },
            $path_to_file
    )   );
        if (file_exists($path)) {
            return $path;
        }
        return false;
    }

    /**
     * format_size_to_readable
     * coverts a size in bytes to readable format
     * @param  float $size
     * @param  int   $precision = 2
     * @return string
     */
    final public static function format_size_to_readable(float $size, int $precision = 2) : string {
        $unit = ['Byte','KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
        for($i = 0; $size >= 1024 && $i < count($unit)-1; $i++){
            $size /= 1024;
        }
        return round($size, $precision).' '.$unit[$i];
    }

        
    /**
     * format_size_to
     * converts between sizes
     * @param  int|float $size
     * @param  string $from
     * @param  string $to
     * @param  int $percision
     * @return int|float
     */
    final public static function format_size_to(int|float $size, string $from = "B", string $to = "KB", int $percision = 2) {
        $from = strtoupper($from);
        $to   = strtoupper($to);
        switch ($from) {
            case "KB": $size = $size * 1024; break;
            case "MB": $size = $size * 1048576; break;
            case "GB": $size = $size * 1073741824; break;
        }
        switch ($to) {
            case "B": return intval(number_format($size, 0, ".", ''));
            case "KB": return floatval(number_format($size / 1024, $percision, ".", ''));
            case "MB": return floatval(number_format($size / 1048576, $percision, ".", ''));
            case "GB": return floatval(number_format($size / 1073741824, $percision, ".", ''));
        }
        return $size;
    }

    private static array $mime_types = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
    ];

        
    /**
     * get_mimetypes
     * return the full mimetype name
     * @param  mixed $types
     * @return array
     */
    final public static function get_mimetypes(...$types) : array {
        if (in_array("*", $types)) return self::$mime_types;
        $ret = [];
        foreach ($types as $type) {
            if (self::$mime_types[$type] ?? false) {
                $ret[$type] = self::$mime_types[$type];
            }
        }
        return $ret;
    }

    /** 
     *  list_files_in
     *  Map all files in a folder:
     *  @param string $path => String : the path to the dynamic pages folder.
     *  @param string $ext  => String : the extension.
     *  @return array
    */
    final public static function list_files_in(string $path, string $ext = ".php") : array {
        return array_filter(
            scandir($path), function($k) use($ext) { 
                return is_string($k) && Std_String::ends_with($k, $ext); 
            }
        );
    }
    
    /**
     * list_folders_in
     * Map all folders in a folder:
     * @param  string $path
     * @return array
     */
    final public static function list_folders_in(string $path) : array {
        return array_values(array_filter(
            scandir($path), function($k) use($path) { 
                return is_string($k) && $k !== "." &&  $k !== ".." && is_dir($path.DIRECTORY_SEPARATOR.$k); 
            }
        ));
    }

}
