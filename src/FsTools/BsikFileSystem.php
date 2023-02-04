<?php

namespace Siktec\Bsik\FsTools;

class BsikFileSystem {

    final public static function list_folder(string|\SplFileInfo $path) : ?\RecursiveIteratorIterator {
        $folder = is_string($path) ? new \SplFileInfo($path) : $path;
        if (!$folder->isDir())
            return null;
        /** @var \RecursiveIteratorIterator SplFileInfo[] $files */
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder->getRealPath()), \RecursiveIteratorIterator::LEAVES_ONLY);
    }

    final public static function delete_files(...$files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

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

    final public static function hash_directory(string $directory) {
        if (!is_dir($directory)) { 
            return false; 
        }
        $files = [];
        $dir = dir($directory);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) { 
                    $files[] = self::hash_directory($directory . DIRECTORY_SEPARATOR . $file); 
                } else { 
                    $files[] = md5_file($directory . DIRECTORY_SEPARATOR . $file); 
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }

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

