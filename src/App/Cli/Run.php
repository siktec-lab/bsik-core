<?php

namespace Siktec\Bsik\App\Cli;

use \Ahc\Cli\Application;
use \Siktec\Bsik\App\Cli\About as CliAbout;
use \Siktec\Bsik\App\Cli\Commands\InfoCommand;

class Run {

    public ?Application $cli = null;

    const LOAD_COMMANDS = [
        InfoCommand::class,
    ];

    public function __construct() {

        $this->cli = new Application(CliAbout::NAME, CliAbout::VERSION);

        $this->cli->add(new InfoCommand(), InfoCommand::ALIAS);

        // Set logo
        $this->cli->logo('BSIK');
    }

    public function handle(?array $argv = null) {
        
        if (is_null($argv)) {
            $argv = $_SERVER['argv'];
        }
        
        $this->cli->handle($argv);
    }
}

