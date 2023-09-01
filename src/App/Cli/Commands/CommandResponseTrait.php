<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Ahc\Cli\IO\Interactor;

// respose trait
trait CommandResponseTrait
{

    // json status - default is done
    const JSON_STATUS_DONE      = 'done';
    const JSON_STATUS_ERROR     = 'error';
    const JSON_STATUS_WARNING   = 'warning';
    const JSON_STATUS_ABORT     = 'abort';
    const JSON_STATUS_INFO      = 'info';

    const DEFAULT_EXIT_CODES = [
        "done"      =>  0,
        "info"      =>  1,
        "warning"   =>  2,
        "error"     =>  3,
        "abort"     =>  4
    ];

    /**
     * get_response_code
     * get the response code for the status
     * @param string $status - the status to get the code for
     * @return int
     */
    private function get_response_code(string $status) : int {
        return self::DEFAULT_EXIT_CODES[$status] ?? 3;
    }

    /**
     * finalize_response
     * finalize the response and exit if needed
     * @param bool $die - exit the script?
     * @param string $type - the type of response to finalize
     * @param int $exit_code - the exit code to use default is -1 which is automatic
     * 
     * @return void
     */
    private function finalize_response(bool $die = false, string $type = "done", int $exit_code = -1) : void {
        if ($die) {
            exit(
                $exit_code == -1 ? 
                    self::DEFAULT_EXIT_CODES[$type] : 
                    $exit_code
            );
        }
    }

    /**
     * prepare_response
     * prepare the response message
     * @param  mixed $io the interactor
     * @param  mixed $message the message to display can contain placeholders
     * @param  mixed $args the arguments to pass to the message placeholders
     * @param  mixed $data the data to add to the response (json only)
     * @param  mixed $status the status of the response (json only) - done, error, warning, abort, info
     * @return string
     */
    public function prepare_response(Interactor $io, string $message, array $args = [], array $data = [], string $status = "done") : string {
        $message = sprintf($message, ...$args);
        if ($this->force_json) {
            return json_encode([
                'status' => $status,
                'message' => $message,
                'data' => $data
            ]);
        }
        return $message;
    }
    
    /**
     * response_error 
     * response error message
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $die - exit the script with code 1
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     * @return void
     */
    public function response_error(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false,
        int $exit_code = -1 
    ) : void {
        
        // Prepare response:
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_ERROR);

        // Print response:
        $io->error($respose, $new_line);

        // Finalize response:
        $this->finalize_response($die, "error", $exit_code);
    }

    /**
     * response_success 
     * response success message
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $die - exit the script with code 1
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     * @return void
     */
    public function response_success(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false,
        int $exit_code = -1 
    ) : void {

        // Prepare response:
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_DONE);
        
        // Print response:
        $io->ok($respose, $new_line);

        // Finalize response:
        $this->finalize_response($die, "done", $exit_code);
    }

    /**
     * response_warning 
     * response warning message
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $die - exit the script with code 1
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     * @return void
     */
    public function response_warning(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false,
        int $exit_code = -1 
    ) : void {

        // Prepare response:
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_WARNING);

        // Print response:
        $io->warn($respose, $new_line);
        
        // Finalize response:
        $this->finalize_response($die, "warning", $exit_code);
    }

    /**
     * response_info 
     * response warning message
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $die - exit the script with code 1
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     * @return void
     */
    public function response_info(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false,
        int $exit_code = -1 
    ) : void {

        // Prepare response:
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_INFO);

        // Print response:
        $io->info($respose, $new_line);
        
        // Finalize response:
        $this->finalize_response($die, "info", $exit_code);
    }

    /**
     * response_warning 
     * response warning message
     * @param  Interactor $io the interactor
     * @param  string $message the message to display can contain placeholders
     * @param  array $args the arguments to pass to the message placeholders
     * @param  array $data the data to add to the response (json only)
     * @param  bool $new_line - add a new line after the message
     * @param  bool $die - exit the script with code 1
     * @param  int $exit_code - the exit code to use default is -1 which is automatic
     * @return void
     */
    public function response_abort(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false,
        int $exit_code = -1 
    ) : void {

        // Prepare response:
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_ABORT);

        // Print response:
        $io->warn($respose, $new_line);
        
        // Finalize response:
        $this->finalize_response($die, "abort", $exit_code);
    }
}