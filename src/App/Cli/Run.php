<?php

namespace Siktec\Bsik\App\Cli;

use \Ahc\Cli\Application;
use \Siktec\Bsik\App\Cli\About as CliAbout;
use \Siktec\Bsik\App\Cli\Commands;

//TODO: add tests to Run and Commands and Shell
//TODO: some of the commands have default values, but they are not set in the constructor
//      maybe its a good idea to set them in the constructor and make them optional
class Run {

    public ?Application $cli = null;

    const LOAD_COMMANDS = [
        Commands\InfoCommand::class,
        Commands\TestsCommand::class,
        Commands\LogsCommand::class,
        Commands\ExportModuleCommand::class,
    ];

    const LOGO1 = <<<EOT

    ██████╗  ███████╗ ██╗ ██╗  ██╗
    ██╔══██╗ ██╔════╝ ██║ ██║ ██╔╝
    ██████╔╝ ███████╗ ██║ █████╔╝ 
    ██╔══██╗ ╚════██║ ██║ ██╔═██╗ 
    ██████╔╝ ███████║ ██║ ██║  ██╗
    ╚═════╝  ╚══════╝ ╚═╝ ╚═╝  ╚═╝

    EOT;

    const LOGO2 = <<<EOT

     ____   ____   ___  _  __
    | __ ) / ___| |_ _|| |/ /
    |  _ \ \___ \  | | | ' / 
    | |_) | ___) | | | | . \ 
    |____/ |____/ |___||_|\_\
                                
    EOT;

    protected string $cwd;

    public function __construct($cwd = null) {

        $this->cli = new Application(CliAbout::NAME, CliAbout::VERSION);
        
        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        // register all default commands:
        //TODO: maybe its a good idea to load all commands from a folder
        //TODO: maybe its a good idea to prefix all commands with a namespace indicating the source of the command
        
        //TODO: add response traits to the commands
        //TODO: add json support to the commands

        // InfoCommand:
        $this->cli->add(new Commands\InfoCommand(), Commands\InfoCommand::ALIAS);
        
        // TestsCommand:
        $this->cli->add(
            command : new Commands\TestsCommand(
                unit_test_path  : $this->cwd . '/vendor/bin/phpunit',
                core_test_path  : $this->cwd . '/vendor/siktec/bsik/tests',
                app_test_folder : $this->cwd . '/tests',
                modules_folder  : $this->cwd . '/manage/modules'
            ), 
            alias   : Commands\TestsCommand::ALIAS
        );

        // LogsCommand:
        $this->cli->add(
            command : new Commands\LogsCommand(
                cwd         : $this->cwd, // will be used to set the default folder path for the logs
                folder_path : 'logs'
            ), 
            alias   : Commands\LogsCommand::ALIAS
        );

        // ExportModuleCommand:
        $this->cli->add(
            command : new Commands\ExportModuleCommand( 
                cwd : $this->cwd,
                folder_path : null
            ), 
            alias   : Commands\ExportModuleCommand::ALIAS
        );
        
        // InstallModuleCommand:
        $this->cli->add(
            command : new Commands\InstallModuleCommand( 
                cwd         : $this->cwd,
                folder_path : null,
                as_json     : false
            ), 
            alias   : Commands\InstallModuleCommand::ALIAS
        );

        // Set logo
        $this->cli->logo(self::LOGO1); // TODO: add logo from character art
    }

    public function handle(?array $argv = null) {
        
        if (is_null($argv)) {
            $argv = $_SERVER['argv'];
        }
        
        $this->cli->handle($argv);
    }
}

