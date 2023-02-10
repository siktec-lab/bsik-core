<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Siktec\Bsik\About as BsikAbout;
use \Siktec\Bsik\App\Cli\About as CliAbout;
use \Siktec\Bsik\App\About as AppAbout;
use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Input\Command;



class InfoCommand extends Command
{

    public const COMMAND        = 'info';
    public const DESCRIPTION    = 'Information about current BSIK installation';
    public const ALIAS          = 'i';

    public function __construct()
    {
        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->usage(
                // append details or explanation of given example with ` ## ` so they will be uniformly aligned when shown
                sprintf('<bold> %s</end><eol/>', self::COMMAND)
            );
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {

    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $bsik = [
            "CORE Version"  => BsikAbout::VERSION,
            "APP Version"   => CliAbout::VERSION,
            "CLI Version"   => AppAbout::VERSION,
            "PHP Version"   => PHP_VERSION,
            "OS Version"    => PHP_OS,
        ];

        foreach ($bsik as $key => $value) {
            $io->cyan(sprintf("%-15s:", $key))->write($value, true);
        }
    }
}
