<?php

namespace Siktec\Bsik\Render\Blocks;

use \Siktec\Bsik\Std;
use \Siktec\Bsik\Render\Templates\Template;

class Block {

    /** 
     * The header default values / settings
     */
    public array  $defaults   = [];
    /** 
     * The header extended values / settings
     */
    public array  $settings  = [];
    /** 
     * The header extended values / settings
     */
    public array $templates     = [];
    public array $file_template = [];

    public Template|null $engine;

    public function __construct(array $settings = [], Template|null $engine = null) {
        $this->extend_defaults($settings);
        $this->engine = $engine;
    }

    public function extend_defaults(array $settings = []) {
        $this->settings = Std::$arr::extend($this->defaults, $settings);
    }

    public function render() : string {
        return "";
    }

}