<?php

namespace Siktec\Bsik\App\Ext;

use \Siktec\Bsik\StdLib as BsikStd;
use \Composer\Semver\VersionParser;

/**
 * Composer wrapper class
 *
 */
trait ComposerCommandsTrait
{

    public function run_installed_names(array $exclude = []) {
        return array_values(array_filter(\Composer\InstalledVersions::getInstalledPackages(), function($v) use ($exclude) {
            foreach ($exclude as $ex) {
                if (str_starts_with($v, $ex)) return false;
            }
            return true;
        }));
    }

    /** 
     * installed
     * get the installed packages
     * @param Composer $composer
     * @return array [result:bool, code:int, data:string, packages:array]
     */
    public function run_installed(array $exclude = ["composer/", "phar-io/", "psr/"]) : array {

        $installed = \Composer\InstalledVersions::getAllRawData();
        $packages = [];
        $total_versions = 0;
        foreach ($installed as $instance) {
            if (!is_array($instance) || empty($instance) || !isset($instance['versions']) || !is_array($instance['versions'])) {
                continue;
            }

            foreach ($instance['versions'] as $name => $data) {

                // Make sure we have a valid package:
                if (!is_array($data) || empty($data) || !isset($data['pretty_version']) || !isset($data['type']) || !isset($data['install_path'])) {
                    continue;
                }
                
                // Skip excluded packages:
                foreach ($exclude as $ex) {
                    if (str_starts_with($name, $ex)) continue 2;
                }

                // Add the package:
                $package = [
                    'name'      => $name,
                    'version'   => $data['pretty_version'],
                    'type'      => $data['type'],
                    'path'      => $data['install_path'],
                    'reference' => $data['reference']
                ];

                // Add the package to the list:
                if (!array_key_exists($name, $packages)) {
                    $packages[$name] = [];
                }
                if (empty($packages[$name])) {
                    $packages[$name][] = $package;
                    $total_versions++;
                } else {
                    // Check if the package is already in the list add it only if it is a new version:
                    foreach ($packages[$name] as $i => $p) {
                        if ($p['version'] === $package['version'] && $p['reference'] === $package['reference'] && $p['path'] === $package['path']) {
                            $packages[$name][$i] = $package;
                            continue 2;
                        }
                    }
                    $packages[$name][] = $package;
                    $total_versions++;
                }
            }
        }
        // return all packages:
        return [
            'result'    => true,
            'code'      => 0,
            'packages'  => $packages,
            'total'     => [
                'versions'  => $total_versions,
                'packages'  => count($packages)
            ]
        ];
    }

    /** 
     * package_exists
     * check if a package exists
     * @param string $package package name to check e.g. "siktec/bsik"
     * @param string $version version to check e.g. "^1.0.0" if empty the package is only checked if it exists
     * @return bool
     * @see https://getcomposer.org/doc/07-runtime.md#knowing-whether-package-x-or-virtual-package-is-present
     */
    public function run_has_package(string $package, string $version = ''): bool {
        if (!empty($version)) {
            return \Composer\InstalledVersions::satisfies( new VersionParser, $package, $version);
        }
        return \Composer\InstalledVersions::isInstalled($package);
    }
    
    /**
     * run_package_version
     * get the version of a package e.g. "1.0.0"
     * @param  string $package package name e.g. "siktec/bsik"
     * @return ?string version or null if the package does not exist
     */
    public function run_package_version(string $package): ?string {
        return \Composer\InstalledVersions::getPrettyVersion($package);
    }

    /**
     * run_package_version_ranges
     * get the version ranges of a package e.g. "^1.0.0"
     * @param  string $package package name e.g. "siktec/bsik"
     * @return ?string version ranges or null if the package does not exist
     */
    public function run_package_version_ranges(string $package): ?string {
        return \Composer\InstalledVersions::getVersionRanges($package);
    }

    /**
     * run_package_path
     * get the path of a package e.g. "C:\Users\user\Documents\projects\bsik\vendor\siktec\bsik"
     * @param  string $package package name e.g. "siktec/bsik"
     * @return ?string path or null if the package does not exist
     */
    public function run_package_path(string $package): ?string {
        $the_package = \Composer\InstalledVersions::getInstallPath($package);
        return $the_package;
    }

    /** 
     * run_package_reference
     * get the reference of a package e.g. "c3f0c1a0c8a9e3e1a3797ea638b0d18c56f4be85"
     * @param  string $package package name e.g. "siktec/bsik"
     * @return ?string reference or null if the package does not exist
     */
    public function run_package_reference(string $package): ?string {
        return \Composer\InstalledVersions::getReference($package);
    }
    
    /**
     * run_require
     * require a package e.g. "siktec/bsik"
     * @param  string $package package name e.g. "siktec/bsik"
     * @param  string $version version to require e.g. "^1.0.0" if empty the latest version is required
     * @param  bool $dev if true the package is required as dev dependency default false
     * @param  string $extra extra options e.g. "--no-update" default ""
     * @return array
     */
    public function run_require(string $package, string $version = '', bool $dev = false, string $extra = ''): array {
        $code = $this->run(
            sprintf("require %s %s %s", 
                $dev ? "--dev" : "", 
                $extra, 
                $package . (!empty($version) ? ":" . $version : "")
            ), 
            true
        );
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_update
     * update all packages or a specific package
     * @param  string $package package name e.g. "siktec/bsik" if empty all packages are updated
     * @param  string $extra extra options e.g. "--no-scripts" default ""
     * @return array
     */
    public function run_update(string $package = '', string $extra = ''): array {
        $code = $this->run(
            sprintf("update %s %s", 
                $extra, 
                !empty($package) ? $package : ""
            ),
            "update " . (!empty($package) ? $package : ""), true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
        
    /**
     * run_install
     * install all packages from composer.json
     * @return array
     */
    public function run_install() : array {
        $code = $this->run("install", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_remove
     * remove a package e.g. "siktec/bsik" from composer.json and vendor
     * @param  string $package package name e.g. "siktec/bsik"
     * @param  bool $dev if true the package is removed as dev dependency default false
     * @return array
     */
    public function run_remove(string $package, bool $dev = false): array {
        $code = $this->run("remove " . ($dev ? "--dev " : "") . $package, true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload() : array {
        $code = $this->run("dump-autoload", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload_classmap
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload_classmap() : array {
        $code = $this->run("dump-autoload --classmap-authoritative", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload_optimize
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload_optimize() : array {
        $code = $this->run("dump-autoload --optimize", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload_static
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload_static() : array {
        $code = $this->run("dump-autoload --no-dev --classmap-authoritative", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload_static_dev
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload_static_dev() : array {
        $code = $this->run("dump-autoload --classmap-authoritative", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_dump_autoload_static_optimize
     * dump the autoload files for all packages will regenerate the vendor/autoload.php file
     * @return array
     */
    public function run_dump_autoload_static_optimize() : array {
        $code = $this->run("dump-autoload --no-dev --classmap-authoritative --optimize", true);
        $data = $this->stream_contents() ?? "";
        return [
            'result'    => $code === 0,
            'code'      => $code,
            'data'      => $data
        ];
    }
    
    /**
     * run_add_classmap
     * add a classmap to the composer.json file
     * @param  array $paths array of paths to add e.g. ["src/", "lib/"]
     * @param  string $base_path base path to add to the paths e.g. "vendor/siktec/bsik/"
     * @return bool
     */
    public function run_add_classmap(array $paths, string $base_path = "") : bool {
        // the classmap is an array of paths:
        $config = $this->get_composer_config();
        $current = BsikStd\Arrays::path_get_one('autoload.classmap', $config, []);

        foreach ($paths as $path) {
            $path = ltrim(trim($base_path, "\\/ ") . '/' . $path, '/');
            if (!in_array($path, $current)) {
                $current[] = $path;
            }
        }

        // the classmap is an array of paths:
        return $this->update_composer_config([
            'autoload' => [
                'classmap' => $current
            ]
        ]);
    }
    
    /**
     * run_remove_classmap
     * remove a classmap from the composer.json file
     * @param  array $paths array of paths to remove e.g. ["src/", "lib/"]
     * @param  string $base_path base path to add to the paths e.g. "vendor/siktec/bsik/"
     * @return bool
     */
    public function run_remove_classmap(array $paths, string $base_path = "") : bool {

        // the classmap is an array of paths:
        $save = [];
        $config = $this->get_composer_config();
        $current = BsikStd\Arrays::path_get_one('autoload.classmap', $config, []);

        // add base path to paths:
        foreach ($paths as $i => $path) {
            $paths[$i] = ltrim(trim($base_path, "\\/ ") . '/' . $path, '/');
        }
        // remove from current:
        foreach ($current as $path) {
            if (!in_array($path, $paths)) {
                $save[] = $path;
            }
        }
        
        // the classmap is an array of paths:
        return $this->set_composer_config_property('autoload.classmap', $save);

    }
    
    /**
     * run_add_psr4
     * add a psr4 map to the composer.json file
     * @param  array $maps array of paths to add e.g. ["App\\MyApp\\" => "app/src/", ...]
     * @param  string $base_path base path to add to the paths e.g. "vendor/siktec/bsik/"
     * @return bool
     */
    public function run_add_psr4(array $maps, string $base_path = "") : bool {
        // the classmap is an array of paths:
        $config = $this->get_composer_config();
        $current = BsikStd\Arrays::path_get_one('autoload.psr-4', $config, []);
        $count = count($current);
        foreach ($maps as $map => $path) {
            $path = ltrim(trim($base_path, "\\/ ") . '/' . $path, '/');
            if (($current[$map] ?? null) === $path) {
                continue;
            }
            $current[$map] = $path;
        }
        
        // if current is empty, and nothing to add, then we're done:
        if ($count === count($current)) {
            return false;
        }

        return $this->update_composer_config([
            'autoload' => [
                'psr-4' => $current
            ]
        ]);
        
    }
    
    /**
     * run_remove_psr4
     * will remove the psr-4 maps from the composer.json file
     * we use only the path to determine if we should remove it
     * 
     * @param  array $paths the paths to remove e.g. ["app/src/", "lib/"]
     * @param  string $base_path base path to add to the paths e.g. "vendor/siktec/bsik/"
     * @return bool  true if we removed something
     */
    public function run_remove_psr4(array $paths, string $base_path = "") : bool {

        $save       = [];
        $config     = $this->get_composer_config();
        $autoload   = BsikStd\Arrays::path_get_one('autoload', $config, []);
        $current    = BsikStd\Arrays::path_get_one('psr-4',     $autoload, []);

        // Current is empty, nothing to remove:
        if (empty($current)) {
            return false;
        }

        // add base path to paths:
        foreach ($paths as $i => $path) {
            $paths[$i] = ltrim(trim($base_path, "\\/ ") . '/' . $path, '/');
        }

        // What should we Save:
        foreach ($current as $map => $path) {
            if (in_array($path, $paths)) {
                continue;
            }
            $save[$map] = $path;
        }
        
        // if current is empty, and nothing to add, then remove the psr-4 key:
        // we do this because we don't want to leave an empty psr-4 key in the composer.json file
        // if we do, the json file will be invalid as it will be [] instead of {}
        if (empty($save)) {
            BsikStd\Arrays::path_unset('psr-4', $autoload);
            return $this->set_composer_config_property('autoload', $autoload);
        }

        // Save the new psr-4 key:
        return $this->set_composer_config_property('autoload.psr-4', $save);

    }

}