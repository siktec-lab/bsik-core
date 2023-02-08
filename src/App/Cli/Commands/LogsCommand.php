<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Input\Command;
use \Siktec\Bsik\Std;

// TODO: implement output as json

class LogsCommand extends Command
{

    public const COMMAND        = 'logs';
    public const DESCRIPTION    = 'Run BSIK logs commands';
    public const ALIAS          = 'log';

    public string $cwd  = __DIR__;
    public string $path = 'logs';

    public int $tail_lines = 20;

    public array $supported = [
        "all"       => "", // all logs reserved for future use
        "manage"    => "apage-general.log",
        "frontend"  => "fpage-general.log", 
        "php"       => "php_errors.log"
    ];

    public const LOGS_OP = [
        "tail"  => [ "t", "Tail the log (default)", false],
        "show"  => [ "s", "Show the log", false],
        "clear" => [ "c", "Clear the log", false]
    ];

    public function __construct(
        string $cwd = null,
        string $folder_path = null
    ) {

        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        if (!is_null($folder_path)) {
            $this->set_folder($folder_path);
        }

        $this->argument(
            '<which>', 
            sprintf('Which log builtin (%s)', implode(',', array_keys($this->supported)))
        );

        // add options from LOGS_OP
        foreach (self::LOGS_OP as $key => $value) {
            $this->option(
                sprintf('-%s, --%s', $value[0], $key), 
                $value[1], 
                'boolval', 
                $value[2]
            );
        }

        $usage = sprintf(
            '<bold> %s</end> <comment>[which]</end> <info>[options]</end><eol/>', 
            self::COMMAND
        );
        $this->usage($usage);
    }
    
    /**
     * set_folder
     * 
     * set the folder where the logs are stored
     * adds the path to the current working directory
     * 
     * @param  string $path
     * @return void
     * @throws \Exception if path is invalid or not a directory
     * 
     */
    public function set_folder(string $path) : void {

        // check if path is valid
        $build_path = Std::$fs->path($this->cwd, $path);

        // check if path is a directory
        if (!file_exists($build_path) || !is_dir($build_path)) {
            throw new \Exception("Invalid logs folder path: {$build_path}");
        }
        // Save path
        $this->path = $path;
    }
    
    /**
     * add_log_file
     * Register a new log file to be supported
     * will overwrite existing log file with same name
     * 
     * @param  string $name name of to be used to refer to the log file
     * @param  string $file the file name of the log file not including the path
     * @return void
     * @throws \Exception if name is reserved
     */
    public function add_log_file(string $name, string $file) : void {
        // trim $file from left and right of directory separator:
        $file = trim($file, "/\\ \n\r\t");
        $name = strtolower(trim($name));
        if ($name == "all")
            throw new \Exception("Name 'all' is reserved for reference to all logs");

        $this->supported[$name] = $file;
    }

    /**
     * get_log_file
     * get the full path to a log file if its defined and exists on disk
     * 
     * @param  string $name name of the log file
     * @return string|null the full path to the log file or null if not found or not on disk
     */
    public function get_log_file(string $name) : ?string {
        $name = strtolower(trim($name));
        if ($name == "all")
            return null;

        if (!$this->log_file_defined($name))
            return null;

        $path = Std::$fs->path($this->cwd, $this->path, $this->supported[$name]);

        if (!$this->log_file_exists($path))
            return null;

        return $path;
    }
        
    /**
     * get_all_log_files
     * get all log files as an array of name => path
     * @return array
     */
    public function get_all_log_files() : array {
        $logs = [];
        foreach ($this->supported as $name => $file) {
            $path = $this->get_log_file($name);
            if (!is_null($path))
                $logs[$name] = $path;
        }
        return $logs;
    }

    /**
     * log_file_defined
     * check if a log file is defined
     * 
     * @param  string $name
     * @return bool
     */
    public function log_file_defined(string $name) : bool {
        $name = strtolower(trim($name));
        if ($name === "all")
            return false;
        return isset($this->supported[$name]);
    }

    /**
     * log_file_exists
     * check if a log file exists on disk
     * 
     * @param  string $path
     * @return bool
     */
    private function log_file_exists(string $path) : bool {
        return file_exists($path);
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {
        if (!$this->tail && !$this->show && !$this->clear) {
            $this->set('tail', true);
        }
    }

    private function cleat_log(string $log_file) : void {
        $fp = fopen($log_file, "w");
        if ($fp) {
            fclose($fp);
        }
    }

    private function tail_log(string $log_file, bool $print) : array {
        $fp = fopen($log_file, "r");
        $tail_lines = $this->tail_lines;
        $lines = [];
        if ($fp) {
            $pos = -2;
            $eof = "";
            while ($tail_lines > 0) {
                while ($eof != "\n") {
                    if (!fseek($fp, $pos, SEEK_END)) {
                        $eof = fgetc($fp);
                        $pos--;
                    } else {
                        break;
                    }
                }
                $eof = "";
                if ($print)
                    echo fgets($fp);
                else
                    $lines[] = fgets($fp);
                $tail_lines--;
            }
            fclose($fp);
        }
        return $lines;
    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $which   = $this->which;
        $tail   = $this->tail ?? false;
        $show   = $this->show ?? false;
        $clear  = $this->clear ?? false;
        $logs  = $which === "all" ? $this->get_all_log_files() : [$which => $this->get_log_file($which)];

        // clear null values:
        $logs = array_filter($logs, function($v) { return !is_null($v); });

        // Do we have any logs to work with?
        if (empty($logs)) {
            $io->error("Log file not found");
            return;
        }

        // Priority: tail > show > clear:
        $op = $tail ? "tail" : ($show ? "show" : ($clear ? "clear" : "unknown"));

        foreach ($logs as $name => $log) {
            $io->writer()->colors("Log: <boldCyan>{$name}</end> - File: <boldGreen>{$log}</end> - Op: <boldCyan>{$op}</end></eol>");
            switch ($op) {
                case "tail" : {
                    // Tail the log file
                    $this->tail_log($log, true);
                } break;
                case "show" : {
                    // not implemented
                    $io->info("Not implemented yet");
                } break;
                case "clear" : {
                    // Clear the log file
                    $this->cleat_log($log);
                } break;
            }
        }
        
    }
}
