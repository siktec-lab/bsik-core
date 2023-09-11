<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/

namespace Siktec\Bsik\Api;

/**
 * ApiRequestObj
 * 
 * This class is used to store the request of an api request
 * 
 * @package Bsik\Api
 */
class ApiRequestObj {

    public string $token = "";
    public string $type  = "";
    public array  $args  = [];
    public ApiAnswerObj $answer;

    public function __construct() {
        $this->answer = new ApiAnswerObj();
    }

    public function answer_code(int|null $code = null) {
        if (is_null($code))
            return $this->answer->code;
        $this->answer->code = $code;
    }

    public function answer_message(string $message = "") {
        if (empty($message))
            return $this->answer->message;
        $this->answer->message = $message;
    }

    public function add_error(string $error) {
        $this->answer->errors[] = $error;
    }

    public function add_errors(array $errors) {
        $this->answer->errors = array_merge($this->answer->errors, $errors);
    }

    public function answer_data(array $data = []) {
        if (empty($data))
            return $this->answer->data;
        else 
            $this->answer->data = $data;
    }

    public function append_answer_data(array $data = []) {
        foreach ($data as $key => $value)
            $this->answer->data[$key] = $value;
    }

    /**
     * add_debug_data
     * adds data to the debug array with key => value
     * @param array $data - the data to add
     * @return void
     * @return void
     */
    public function add_debug_data(array $data = []) : void {
        foreach ($data as $key => $value)
            $this->answer->debug[$key] = $value;
    }

    /**
     * append_debug_data
     * appends data to the debug array
     * @param string $to - the key to append to
     * @param mixed $value - the value to append
     * @param string|null $key - the key to append to - if null appends to the end of the array
     * 
     * @return void
     */
    public function append_debug_data(string $to, $value, string|null $key = null) : void {
        if (isset($this->answer->debug[$to]) && is_array($this->answer->debug[$to])) {
            if (is_null($key))
                $this->answer->debug[$to][] = $value;
            else
                $this->answer->debug[$to][$key] = $value;
        }
    }
    /**
     * update_answer_status - changes the code + adds a row to errors
     *
     * @param  int $code - the http code - if 0 ignored.
     * @param  string|array $error - pushes error or errors - if empty ignored.
     * @param  string $message - sets a custom code message - if empty ignored use default code message.
     * @return void
     */
    public function update_answer_status(int $code = 0, string|array $error = "", string $message = "") {
        //Set code:
        if (isset(BsikApi::$codes[$code])) {
            $this->answer_code($code);
        }
        //Set message:
        if (empty($message) && isset(BsikApi::$codes[$code])) {
            $this->answer_message(BsikApi::$codes[$code]);
        } elseif (!empty($message)) {
            $this->answer_message($message);
        }
        //Add to error:
        if (!empty($error)) {
            is_string($error) ? $this->add_error($error) : $this->add_errors($error);
        }
    }

    function __clone() {
        $this->answer = clone $this->answer;
    }
}
