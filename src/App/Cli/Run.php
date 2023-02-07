<?php

namespace Siktec\Bsik\App\Cli;

use \Ahc\Cli\Application;
use \Siktec\Bsik\App\Cli\About as CliAbout;
use \Siktec\Bsik\App\Cli\Commands;

class Run {

    public ?Application $cli = null;

    const LOAD_COMMANDS = [
        InfoCommand::class,
        TestsCommand::class
    ];

    protected string $cwd;

    public function __construct($cwd = null) {

        $this->cli = new Application(CliAbout::NAME, CliAbout::VERSION);
        
        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        // register all default commands:
        //TODO: maybe its a good idea to load all commands from a folder
        //TODO: maybe its a good idea to prefix all commands with a namespace indicating the source of the command
        $this->cli->add(new Commands\InfoCommand(), Commands\InfoCommand::ALIAS);
        $this->cli->add(
            command : new Commands\TestsCommand(
                unit_test_path  : $this->cwd . '/vendor/bin/phpunit',
                core_test_path  : $this->cwd . '/vendor/siktec/bsik/tests',
                app_test_folder : $this->cwd . '/tests',
                modules_folder  : $this->cwd . '/manage/modules'
            ), 
            alias   : Commands\TestsCommand::ALIAS
        );

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

