<?php

namespace Siktec\Bsik\App\Cli;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\CoreSettings;

class CliExec {

    private string $cli_path = "";
    private string $command  = "";
    private string $args     = "";
    private string $options  = "";

    public static string $CLI_EXEC_NAME = "bsikc";
 
    /**
     * __construct
     * @param string $command - the command to execute
     * @param string|array $args - arguments to the command (can be an array or a string)
     * @param string|array $options - options to the command (can be an array or a string)
     * @param string|null $cli_path - the path to the cli executable (if not set it will use the base path)
     * @return void
     */
    public function __construct(
        string $command         = "",
        string|array $args      = "",
        string|array $options   = "",
        ?string $cli_path       = null,
    ) {
        
        // Cli Path:
        if (empty($cli_path)) {
            $this->cli_path = CoreSettings::$path["base"] ?? "";
        } else {
            $this->cli_path = $cli_path;
        }

        //Command:
        if (empty($command)) {
            $this->command = "";
            $args = "";
            $options = "-h";
        } else {
            $this->command = $command;
        }

        // Args:
        $this->parse_args($args);

        // Options:
        $this->parse_options($options);
    }
    
    /**
     * parse_args
     * Parse the arguments and make sure they are a string
     * @param  string|array $args - the arguments to parse
     * @return void
     */
    private function parse_args(string|array $args) {
        if (is_array($args)) {
            $this->args = implode(" ", $args);
        } else {
            $this->args = $args;
        }
    }
    
    /**
     * parse_options
     * Parse the options and make sure they are a string
     * @param  string|array $options
     * @return void
     */
    private function parse_options(string|array $options) {
        if (is_array($options)) {
            // loop through options and add them to the string option that is one character long add a single dash otherwise add a double dash:
            foreach ($options as $option => $value) {
                if (strlen($option) === 1) {
                    $this->options .= "-" . $option . " " . $value . " ";
                } else {
                    $this->options .= "--" . $option . " " . $value . " ";
                }
            }
        } else {
            $this->options = $options;
        }
    }
    
    /**
     * get_exec_command
     * Get the command that will be executed
     * @return string
     */
    public function get_exec_command(bool $join_stderr = false) : string {
        $parts = [
            $this->cli_path . DIRECTORY_SEPARATOR .self::$CLI_EXEC_NAME,
            trim($this->command),
            trim($this->args),
            trim($this->options)
        ];
        if ($join_stderr) {
            $parts[] = "2>&1";
        };
        return implode(" ", $parts);
    }
        
    /**
     * run
     * Run the command and return the output and return code
     * @param  bool $wait - if true the command will be executed in the foreground and the output will be returned
     * @param  mixed $output - the output of the command
     * @param  bool $expect_json - if true the output will be parsed as json
     * @return int - the return code of the command
     */
    public function run(bool $wait = true, mixed &$output = [], bool $expect_json = false) : int {
        
        // Use exec to run the command and get the output and return code:
        $return_code = 0;
        $_output = [];
        //Execute based on platform:
        if (!$wait) {
            
            $command = $this->get_exec_command();

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

                //Execute background windows:
                $return_code = pclose(popen("start /B " . $command . " 1> NUL 2>&1 &", "r"));
            } else {
                // shell_exec( $command . " > /dev/null 2>&1 &" );
                exec($command . " > /dev/null 2>&1 &", $_output, $return_code);
            }
        } else {

            $command = $this->get_exec_command(join_stderr : true);

            //Execute foreground:
            exec($command, $_output, $return_code);

            if (!$expect_json) {
                $output = implode(" ", $_output);
                $output = preg_replace("/\e\[[0-9;]*m/", "", $output);
            } else {
                $result = preg_replace("/\e\[[0-9;]*m/", "", implode("", $_output));
                $output = Std::$str::parse_json($result, "", true);
            }
        }
        
        return $return_code;
    }
}