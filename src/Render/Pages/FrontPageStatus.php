<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Render\Pages;

/**
 * FrontPageStatus
 * 
 * This class is used to store the status of the front page
 */
class FrontPageStatus {

    public static $states = [
        0 => "draft",
        1 => "active",
    ];
    
    public $status = false;
    
    /**
     * state_translate
     * - translate between index to value bidirectional
     * @param  mixed $state
     * @return mixed
     */   
    public static function state_translate(mixed $state) : mixed {
        if (is_string($state)) {
            return array_search($state, self::$states);
        }
        if (is_int($state)) {
            return self::$states[$state] ?? false;
        }
        return false;
    }

    /**
     * set
     * - sets the status index translated if needed
     * @param  mixed $state
     * @return void
     */
    public function set(mixed $state) : void {
        $check = self::state_translate($state);
        if ($check !== false) {
            if (is_int($check)) {
                $this->status = $check;
            } else {
                $this->status = $state;
            }
        } else {
            $this->status = false;
        }
    }
    
    /**
     * is
     * - checks if status matches
     * @param  string $state
     * @return bool
     */
    public function is(string $state) : bool {
        $check = self::state_translate($state);
        return $check !== false ? ($this->status === $check) : false;
    }
}