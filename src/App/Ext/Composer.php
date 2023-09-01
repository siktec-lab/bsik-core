<?php

namespace Siktec\Bsik\App\Ext;

use \Siktec\Bsik\StdLib as BsikStd;
use \Composer\Console\Application as ComposerApplication;
use \Symfony\Component\Console\Output\StreamOutput as ComposerStreamOutput;


/**
 * Composer wrapper class
 */
final class Composer
{
    
    use ComposerCommandsTrait;

    /**
     * composer.phar file name
     */
    const PHAR_FILE = 'composer.phar';

    /**
     * url from where to get the composer phar archive
     */
    const PHAR_URL = 'https://getcomposer.org/composer.phar';

    const MIN_MEMORY_LIMIT = '512M';

    const CWD_OPTION = '--working-dir';

    const NO_INTERACTION_OPTION = '--no-interaction';

    const QUIET_OPTION = '--quiet';

    const COMPOSER_JSON_FILE = 'composer.json';

    /**
     * composer executable path
     */
    private string $composer;

    /**
     * Command-line application, created on demand and kept around for future
     * calls.
     */
    private ?ComposerApplication $application = null;

    /**
     * Keeps track of whether we've already taken care of downloading
     * composer.phar.
     */
    private bool $phar_loaded = false;

    /** 
     * StreamOutput object
     */
    private $stream_resource    = null; 
    private ?ComposerStreamOutput $output = null;

    public string $sapi = "";

    /**
     * current working directory
     * null to use the default current working directory
     */
    private ?string $cwd = null; 

    private bool $quiet = false;

    /**
     * constructor
     *
     * @param ?string $phar_directory target directory where to copy composer.phar from or download it to
     * @param mixed $stream output stream
     * @param string $cwd current working directory to use null to use the default current working directory
     * @throws \Exception if the composer.phar file is not found or is not downloadable
     */
    private function __construct(?string $phar_directory = null, mixed $stream = null, ?string $cwd = null) {
        // check if directory is valid or use the default temp directory
        if (empty($phar_directory) || !file_exists($phar_directory)) {
            $phar_directory = sys_get_temp_dir();
        }

        // set the current SAPI
        $this->sapi = php_sapi_name();
        
        // set the composer.phar file path:
        $this->composer     = $phar_directory . '/' . self::PHAR_FILE;

        // set the current working directory
        $this->set_cwd($cwd);

        // Load composer.phar if it exists, otherwise download it.
        $this->load_composer(force : false);

        // check memory limit and try to increase it if needed
        $this->check_mem_limit();

        // check if composer autoload is loaded
        if (!class_exists('\Composer\Console\Application')) {
            require_once 'phar://' . $this->composer . '/vendor/autoload.php';
        }

        // create output stream if not already created
        $this->load_stream($stream);

    }

    /**
     * create
     * Factory method.
     * will create a new instance of this class with the given directory
     * 
     * @param mixed $stream output stream to use
     * @param ?string $cwd current working directory to use null to use the default current working directory
     * @param ?string $phar_directory - directory where to load composer.phar to or null to use the 
     *                            default `sys_get_temp_dir()`
     * @return Composer
     */
    public static function create(mixed $stream = null, ?string $cwd = null, ?string $phar_directory = null) : Composer {
        return new Composer($phar_directory, $stream, $cwd);
    }

    /**
     * run
     * Run this Composer wrapper as a command-line application.
     *
     * @param  string  $input  command line arguments
     * @param  bool $clear  whether to clean up the output stream
     * @return int 0 if everything went fine, or an error code
     * @see http://api.symfony.com/2.2/Symfony/Component/Console/Application.html#method_run
     */
    public function run(string $input = '', bool $clear = false) : int {

        // Initialize the Composer application
        if (!$this->application) {
            $this->application = new ComposerApplication();
            $this->application->setAutoExit(false);
        }

        // add cwd option if not already added
        if (strpos($input, self::CWD_OPTION) === false) {
            // escape the cwd path to avoid problems with spaces and add backslashes to backslashes
            $cwd = str_replace('\\', '\\\\', $this->cwd);
            $input .= ' ' .  self::CWD_OPTION . '=' . escapeshellarg($cwd);
        }

        // add no interaction option if not already added
        if (strpos($input, self::NO_INTERACTION_OPTION) === false) {
            $input .= ' ' .  self::NO_INTERACTION_OPTION;
        }

        // add quiet option if not already added
        if (strpos($input, self::QUIET_OPTION) === false) {
            $input .= ' ' .  self::QUIET_OPTION;
        }

        // Parse the command line arguments
        $cli_args = is_string($input) && !empty($input) ?
                new \Symfony\Component\Console\Input\StringInput($input) :
                null;

        // Cache the original argv[0] value, if it exists, and set it to a dummy
        if (array_key_exists('argv', $_SERVER) && !empty($_SERVER['argv'])) {
            $argv0 = $_SERVER['argv'][0];
        }

        // self update
        $this->fix_self_update($cli_args);

        // Clear the output stream if requested
        if ($clear && is_resource($this->stream_resource)) {
            ftruncate($this->stream_resource, 0);
            rewind($this->stream_resource);
        }
        
        // Run the Composer application
        $exitcode = $this->application->run(
            $cli_args,
            $this->output // May be null
        );
        
        // Restore the original argv[0] value, if it exists
        if (isset($argv0))
            $_SERVER['argv'][0] = $argv0;

        return $exitcode;
    }
    
    /** 
     * set_cwd
     * set the current working directory
     * @param ?string $cwd current working directory to use null to use the default current working directory
     * @return void
     */
    public function set_cwd(?string $cwd) : void {

        // set the current working directory
        $this->cwd = trim($cwd ?? getcwd());
    }
        
    /**
     * be_quiet
     * set quiet mode on or off 
     * if quiet mode all --quiet options will be added to the composer command
     * 
     * @param  mixed $enable
     * @return void
     */
    public function be_quiet(bool $enable = true) : void {
        $this->quiet = $enable;
    }
    /**
     * get_composer_config
     * get the composer config file if it exists
     * @return ?array composer config file as an array or null if the file does not exist or is not readable.
     */
    public function get_composer_config() : ?array {
        
        // check if composer config is in the current working directory:
        $composer_config_file = BsikStd\FileSystem::path($this->cwd, 'composer.json');

        // Will return null if the file does not exist or is not readable:
        return BsikStd\FileSystem::get_json_file($composer_config_file);

    }
    
    /**
     * update_composer_config
     * update the composer config file with the given config
     * it will merge the given config with the current config
     * user is responsible for escaping slashes if needed
     * 
     * @param  array $composer_config - composer config to update
     * @return bool true if the file was updated successfully, false otherwise
     */
    public function update_composer_config(array $composer_config) : bool {
        
        // check if composer config is in the current working directory:
        $composer_config_file = BsikStd\FileSystem::path($this->cwd, 'composer.json');
        $current = $this->get_composer_config();
        if (empty($current)) {
            return false;
        }
        $composer_config = BsikStd\Arrays::merge_recursive_distinct(
            $current, 
            $composer_config
        );
        // write the file to disk clearing the contents first:
        // NOTE: We are not escaping slashes here because we want to keep the slashes in the paths
        //       Its the responsibility of the user to escape the slashes if needed
        return BsikStd\FileSystem::put_json_file($composer_config_file, $composer_config, true, JSON_UNESCAPED_SLASHES, false);
    }
    
    /**
     * set_composer_config_property
     * set the given property in the composer config file
     * this will overwrite the current value of the property
     * user is responsible for escaping slashes if needed
     * 
     * @param  string $path - path to the property to set
     * @param  mixed $value - value to set
     * @return bool true if the file was updated successfully, false otherwise
     */
    public function set_composer_config_property(string $path, mixed $value) : bool {
        // check if composer config is in the current working directory:
        $composer_config_file = BsikStd\FileSystem::path($this->cwd, 'composer.json');
        $composer_config = $this->get_composer_config();
        if (!is_array($composer_config)) {
            return false;
        }
        BsikStd\Arrays::path_set($path, $composer_config, $value);

        // write the file to disk clearing the contents first:
        // NOTE: We are not escaping slashes here because we want to keep the slashes in the paths
        //       Its the responsibility of the user to escape the slashes if needed
        return BsikStd\FileSystem::put_json_file($composer_config_file, $composer_config, true, JSON_UNESCAPED_SLASHES, false);
    }

    /**
     * done
     * closes the stream resource and the output object
     * 
     * @return void
     */
    public function done() : void {
        // close the stream
        $this->close_stream();
    }

    /**
     * close_stream
     * closes the stream resource and the output object
     * NOTE: this will not delete the stream resource if it is a temp file
     * 
     * @return void
     */
    private function close_stream() : void {
        if (is_resource($this->stream_resource)) {
            fclose($this->stream_resource);
        }
        $this->stream_resource = null;
    }
    
    /**
     * load_stream
     * loads the stream resource and the output object
     * will close the current stream if it is open
     * defaults to memory stream
     * you can pass a resource or a string
     * if you pass a string it will be interpreted as follows:
     * "stdout", "stderr", "temp", "memory"
     * 
     * @param  mixed $stream - stream resource or string to create a stream
     * @return void
     */
    private function load_stream(mixed $stream = null) : void {

        // create output stream if not already created
        if (!is_null($stream)) {
            
            //First check if currently we have a stream and if so close it
            $this->close_stream();
            
            //Now create the resource:
            if (is_resource($stream)) {
                $this->stream_resource = $stream;
            } else {
                switch ($stream) {
                    
                    case 'stdout':
                        $this->stream_resource = fopen('php://stdout', 'w+') ?: null;
                        break;
                    case 'stderr':
                        $this->stream_resource = fopen('php://stderr', 'w+') ?: null;
                        break;
                    case 'temp':
                        $this->stream_resource = tmpfile() ?: null; //tmpfile() returns a resource
                        break;
                    case 'memory':
                    default:
                        $this->stream_resource = fopen('php://memory', 'w+') ?: null;
                        break;
                }
            }

            //Now create the output object
            if (is_resource($this->stream_resource)) {
                $this->output = new ComposerStreamOutput($this->stream_resource);
            } else {
                $this->output = null;
            }
        }
    }
    
    /**
     * stream_contents
     * returns the contents of the stream resource
     * 
     * @param ?int $length - maximum bytes to read from the stream
     * @param int $offset - seek to this offset before reading (default: -1 = no seek)
     * @return ?string - contents of the stream resource or null if not a resource
     * @see https://www.php.net/manual/en/function.stream-get-contents.php
     */
    public function stream_contents(?int $length = null, int $offset = -1) : ?string {
        if (is_resource($this->stream_resource)) {
            rewind($this->stream_resource);
            $content = stream_get_contents($this->stream_resource, $length, $offset);
            return  is_string($content) ? $content : null;
        }
        return null;
    }

    /**
     * load_composer
     * Connects to getcomposer.org to fetch the latest composer.phar.
     * Normally, this is only done if the file doesn't exist or is empty.
     *
     * @param bool $force TRUE to re-download composer.phar even if a valid
     *                       file already exists.
     * @return bool TRUE if composer.phar was successfully downloaded or already exists
     * @throws \Exception if we can't load composer.phar
     */
    public function load_composer(bool $force = false) : bool {   
        // avoid re-downloading composer.phar if it already exists:
        if (
            !$force && 
            file_exists($this->composer) &&
            is_readable($this->composer) &&
            filesize($this->composer)
        ) {
            $this->phar_loaded = true;
            return $this->phar_loaded;
        }

        // download the latest composer.phar from getcomposer.org
        $phar = @file_get_contents(static::PHAR_URL);
        if ($phar === false) {
            throw new \Exception(sprintf("Can't download %s from %s", self::PHAR_FILE, static::PHAR_URL));
        }
        
        // check if the download is valid
        if (empty($phar)) {
            throw new \Exception(sprintf("Empty download %s from %s", self::PHAR_FILE, static::PHAR_URL));
        }

        // write the phar to disk
        $bytes_written = @file_put_contents($this->composer, $phar);
        if ($bytes_written === false) {
            throw new \Exception(sprintf("Can't write %s to %s", self::PHAR_FILE, $this->composer));
        }

        $this->phar_loaded = true;
        return $this->phar_loaded;
    }
    
    /**
     * mem_str_to_bytes
     * Convert a memory limit string to bytes
     * @param  string $str_value
     * @return int
     */
    private function mem_str_to_bytes(string $str_value) : int {
        $unit = strtolower(substr($str_value, -1, 1));
        // remove the unit from the value
        $value = intval(substr($str_value, 0, -1));
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        return $value;
    }

    /**
     * check_mem_limit
     * Check whether the current setup meets the minimum memory requirements
     * for composer; raise a notice if not.
     * 
     * @return void
     * @throws \Exception if the memory limit is too low
     */
    private function check_mem_limit() : void {
        if (function_exists('ini_get')) {
            /**
             * Note that this calculation is incorrect for memory limits that
             * exceed the value range of the underlying platform's native
             * integer.
             * In practice, we will get away with it, because it doesn't make
             * sense to configure PHP's memory limit to half the addressable
             * RAM (2 GB on a typical 32-bit system).
             */
            $cur_memory_limit = trim(ini_get('memory_limit'));
            $min_memory_limit = self::MIN_MEMORY_LIMIT;
            $min_memory_limit_bytes = $this->mem_str_to_bytes($min_memory_limit);

            // Increase memory_limit if it is lower than 512M
            if (
                "".$cur_memory_limit !== "-1" && 
                $this->mem_str_to_bytes($cur_memory_limit) < $min_memory_limit_bytes && 
                ini_set('memory_limit', $min_memory_limit) === false
            ) {
                throw new \Exception(sprintf("Memory limit too low: %s", $cur_memory_limit));
            }
        }

    }

    /**
     * fix selfupdate issue
     * only works if the we are running from the command line
     * @param string $cli_args
     * @return void
     */
    private function fix_self_update(string $cli_args) : void {
        if (
            preg_match('/self-?update/', $cli_args) &&
            array_key_exists('argv', $_SERVER)
        ) {
            $_SERVER['argv'][0] = $this->composer;
        }
    }
}