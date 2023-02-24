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
     * @return void
     */
    public function response_error(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false
    ) : void {
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_ERROR);
        $io->error($respose, $new_line);
        if ($die) {
            exit(1);
        }
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
     * @return void
     */
    public function response_success(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false
    ) : void {
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_DONE);
        $io->ok($respose, $new_line);
        if ($die) {
            exit(1);
        }
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
     * @return void
     */
    public function response_warning(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false
    ) : void {
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_WARNING);
        $io->warn($respose, $new_line);
        if ($die) {
            exit(1);
        }
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
     * @return void
     */
    public function response_info(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false
    ) : void {
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_INFO);
        $io->info($respose, $new_line);
        if ($die) {
            exit(1);
        }
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
     * @return void
     */
    public function response_abort(
        Interactor $io, 
        string $message, 
        array $args = [],
        array $data = [], 
        bool $new_line = true, 
        bool $die = false
    ) : void {
        $respose = $this->prepare_response($io, $message, $args, $data, self::JSON_STATUS_ABORT);
        $io->warn($respose, $new_line);
        if ($die) {
            exit(1);
        }
    }
}