<?php
/******************************************************************************/
// Created by: SIKTEC.
// Release Version : 1.0.0
// Creation Date: 2021-03-16
// Copyright 2021, SIKTEC.
/******************************************************************************/
/*****************************      Changelog       ****************************
1.0.0:
    ->initial
*******************************************************************************/

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

    public function add_debug_data(array $data = []) {
        foreach ($data as $key => $value)
            $this->answer->debug[$key] = $value;
    }

    public function append_debug_data(string $to, $value) {
        if (isset($this->answer->debug[$to]) && is_array($this->answer->debug[$to])) {
            $this->answer->debug[$to][] = $value;
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
