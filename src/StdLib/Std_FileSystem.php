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

        // remove empty strings and nulls
        $path = array_filter($path, fn($v) => !is_null($v) && $v !== '');

        return implode(DIRECTORY_SEPARATOR, $path);
    }
        
    /**
     * path_url
     * implodes an array to a url path
     * @param  mixed $path
     * @return string
     */
    final public static function path_url(...$path) : string {

        // remove empty strings and nulls
        $path = array_filter($path, fn($v) => !is_null($v) && $v !== '');
        
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
        // NOTE: rtrim is important for windows and not ltrim to avoid stripping base path in unix e.g. /var/www/...
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path_to_file);
        $url  = rtrim($url, "/")."/".implode("/", Std_Url::normalize_slashes($path_to_file));
        
        return ["path" => $path, "url" => $url]; 
    }
    
    /**
     * get_json_file
     * loads a json file and return its content this method is a wrapper for Std_String::parse_jsonc
     * @param  string $path - the path to the file
     * @param  bool   $remove_bom - remove byte order mark
     * @param  bool   $associative - return an associative array
     * @return array|null - returns an array or null if the file does not exist
     */
    final public static function get_json_file(string $path, bool $remove_bom = true, bool $associative = true) : array|null {
        $json = "";
        if (is_file($path)) {
            $json = @file_get_contents($path) ?: "";
        }
        if (!empty($json)) {
            return Std_String::parse_jsonc($json, $remove_bom, null, $associative);
        }
        return null;
    }    

    /** 
     * put_json_file
     * saves an array to a json file and returns true if successful
     * if the file does not exist, and create is false, returns false
     * if a directory does not exist in the path, it will not be created and the method will return false
     * this method will replace the file content if it exists
     * 
     * @param  string $path - the path to the file
     * @param  array  $data - the data to save
     * @param  bool   $pretty - pretty print the json
     * @param  int    $flags - json flags
     * @param  bool   $create - create the file if it does not exist
     * @return bool
     */
    final public static function put_json_file(string $path, array $data, bool $pretty = true, int $flags = 0,  bool $create = true) : bool {

        //Add to flags if pretty make sure it is not already set:
        if ($pretty && !($flags & JSON_PRETTY_PRINT)) {
            $flags |= JSON_PRETTY_PRINT;
        }

        // Encode data:
        $json = json_encode($data, $flags);

        // Create file if it does not exist:
        return !empty($json) ? self::put_file($path, $json, $create) : false;
    }
    
    /**
     * put_json_file_force
     * saves an array to a json file and returns true if successful
     * if the file does not exist, it will be created including any directories in the path
     * this method will replace the file content if it exists
     * 
     * @param  string $path - the path to the file
     * @param  array $data - the data to save
     * @param  bool $pretty - pretty print the json
     * @param  int $flags - json flags
     * @param  int $permission - the permission to set on the directory if it is created
     * @return bool
     */
    final public static function put_json_file_force(string $path, array $data, bool $pretty = true, int $flags = 0, int $permission = 0777) : bool {
        
        //Add to flags if pretty make sure it is not already set:
        if ($pretty && !($flags & JSON_PRETTY_PRINT)) {
            $flags |= JSON_PRETTY_PRINT;
        }

        // Encode data:
        $json = json_encode($data, $flags);
        
        // Create file if it does not exist with directory Creation:
        return !empty($json) ? self::put_file_force($path, $json, 0, $permission) : false;
    }
        
    /**
     * get_file
     * loads a file and return its content only if it exists
     * @param  string $path - the path to the file
     * @return ?string - returns the file content or null if the file does not exist
     */
    final public static function get_file(string $path) : ?string {
        $file = null;
        if (is_file($path)) {
            $file = @file_get_contents($path) ?: null;
        }
        return $file;
    }

    /**
     * put_file
     * saves a string to a file and returns true if successful
     * if the file does not exist, and create is false, returns false
     * if a directory does not exist in the path, it will not be created and the method will return false
     * by default this method will replace the file content if it exists 
     * 
     * @param  string $path
     * @param  string $data
     * @param  bool $create - create the file if it does not exist default is true
     * @param  int $flags   - flags to pass to file_put_contents default is 0
     * @return bool
     */
    final public static function put_file(string $path, string $data, bool $create = true, int $flags = 0) : bool {
        
        // If file does not exist, and create is false, return false:
        if (!$create && !is_file($path)) {
            return false;
        }
        // Create file if it does not exist:
        if (!empty($data)) {
            return @file_put_contents($path, $data, $flags) !== false;
        }
        return false;
    }
    
    /**
     * put_file_force
     * saves a string to a file and returns true if successful
     * this method will create the file and the directory if it does not exist
     * this method assumes that the file name is the last part of the path and that
     * the separator is a DIRECTORY_SEPARATOR which is platform specific
     * by default this method will replace the file content if it exists
     * 
     * @param  string $path - the path to the file
     * @param  string $data - the data to save
     * @param  int $flags - see file_put_contents flags for more info
     * @param  int $permission - the permission to use when creating the directory default is 0777
     * @return bool
     */
    final public static function put_file_force(string $path, string $data, int $flags = 0, int $permission = 0777) : bool {
        
        // Extract directory from path:
        $parts = explode( DIRECTORY_SEPARATOR, $path );
        array_pop( $parts );
        $dir = implode( DIRECTORY_SEPARATOR, $parts );

        // Create directory if it does not exist and return false if it fails:
        if ( is_dir( $dir ) || @mkdir( $dir, $permission, true ) ) {
            return file_put_contents( $path, $data, $flags );
        }
        // Return false if directory creation fails:
        return false;
    }

    /**
     * file_exists
     * checks wether a file exists
     * if it does, returns the path and url as an array
     * if not, returns false
     * 
     * @param  mixed $in - the base path to use use 'raw' for no base path
     * @param  array|string $path_to_file - the path to the file
     * @return array|bool
     */
    final public static function file_exists(string $in, array|string $path_to_file = []) : array|bool
    {
        $file = self::path_to($in, $path_to_file);
        if (file_exists($file["path"])) {
            return $file;
        }
        return false;
    }
        
    /**
     * delete_files
     * deletes a list of files if they exist
     * @param  array $files - packed list of files to delete e.g. delete_files($file1, $file2, $file3);
     * @return void
     */
    final public static function delete_files(...$files) : void {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
    
    /**
     * path_exists
     * checks wether a file exists with a simple path
     * @param  array ...$path_to_file - the path to the file packed as an array
     * @return string|bool - returns the path if exists or false if not
     */
    final public static function path_exists(...$path_to_file) : string|bool {
        $path = implode(DIRECTORY_SEPARATOR, array_map(
            function($part){
                return trim($part, " \t\n\r\\/");
            },
            $path_to_file
        ));
        return file_exists($path) ? $path : false;
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
        
    /**
     * list_folder
     * list all files in a folder recursively 
     * this method return a RecursiveIteratorIterator unlike list_files_in that return an array
     * @param  string|\SplFileInfo $path
     * @return ?RecursiveIteratorIterator - null if the path is not a directory
     */
    final public static function list_folder(string|\SplFileInfo $path) : ?\RecursiveIteratorIterator {
        $folder = is_string($path) ? new \SplFileInfo($path) : $path;
        if (!$folder->isDir())
            return null;
        /** @var \RecursiveIteratorIterator SplFileInfo[] $files */
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder->getRealPath()), \RecursiveIteratorIterator::LEAVES_ONLY);
    }
    
    /**
     * mkdir
     * create a directory if not exists
     * @param  string   $path_dir - the path to the directory
     * @param  int      $permissions - the permissions default 0777
     * @param  bool     $recursive - create the directory recursively
     * @return bool
     */
    final public static function mkdir(string $path_dir, int $permissions = 0777, bool $recursive = true) : bool {
        if (!is_dir($path_dir)) {
            return mkdir($path_dir, $permissions, $recursive);
        }
        return false;
    }
    
    /**
     * create_folder
     * create a directory if not exists
     * alias of mkdir
     * @param  string   $path_dir - the path to the directory
     * @param  int      $permissions - the permissions default 0777
     * @param  bool     $recursive - create the directory recursively
     * @return bool
     */
    final public static function create_folder(string $path_dir, int $permissions = 0777, $recursive = true) : bool {
        return self::mkdir($path_dir, $permissions, $recursive);
    }
    
    /**
     * clear_folder
     * clear a folder and delete all files and subfolders
     * will remove the folder if $self is true
     * @param  string $path_dir - the path to the directory
     * @param  bool $self - remove the folder if true
     * @return bool - true if the folder is cleared
     */
    final public static function clear_folder(string $path_dir, bool $self = false) : bool {
        if (!file_exists($path_dir)) return false;
        $di = new \RecursiveDirectoryIterator($path_dir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : self::delete_files($file);
        }
        if ($self) {
            rmdir($path_dir);
        }
        return true;
    }
    
    /**
     * hash_directory
     * hash a directory and all its subdirectories
     * creates a hash of all files in a directory and its subdirectories
     * based on all file names, and directory names
     * @param  string $path - the path to the directory
     * @return string|false - the hash or false if the path is not a directory 
     */
    final public static function hash_directory(string $path) : string|false {
        if (!is_dir($path)) { 
            return false; 
        }
        $files = [];
        $dir = dir($path);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $file)) { 
                    $files[] = self::hash_directory($path . DIRECTORY_SEPARATOR . $file); 
                } else { 
                    $files[] = md5_file($path . DIRECTORY_SEPARATOR . $file); 
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }
    
    /**
     * xcopy
     * copy a directory and all its subdirectories to a given destination
     * @param  string $source - the source directory
     * @param  string $dest - the destination directory
     * @param  int    $permissions - the permissions default 0755
     * @return bool
     */
    final public static function xcopy(string $source, string $dest, int $permissions = 0755) : bool {
        $sourceHash = self::hash_directory($source);
        // Check for symlinks
        if (is_link($source))
            return symlink(readlink($source), $dest);
        // Simple copy for a file
        if (is_file($source)) {
            $file = explode(DIRECTORY_SEPARATOR, $source);
            return copy(
                    $source, is_dir($dest) 
                    ? rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.end($file) 
                    : $dest
            );
        }
        // Make destination directory
        if (!is_dir($dest)) 
            mkdir($dest, $permissions, true);
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..')
                continue;
            // Deep copy directories
            if ($sourceHash != self::hash_directory($source.DIRECTORY_SEPARATOR.$entry)) {
                if (!self::xcopy($source.DIRECTORY_SEPARATOR.$entry, $dest.DIRECTORY_SEPARATOR.$entry, $permissions)) {
                    return false;
                }
            }
        }
        // Clean up
        $dir->close();
        return true;
    }
}
