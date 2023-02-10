<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Input\Command;
use \Siktec\Bsik\Tools\Shell;

class TestsCommand extends Command
{

    public const COMMAND        = 'tests';
    public const DESCRIPTION    = 'Run BSIK tests or custom BSIK-App tests';
    public const ALIAS          = 'test';

    public const DEFAULT_OF = 'core';
    public const TESTS_OFS = [
        'core',
        'app',
        'modules' // TODO: Implement modules tests
    ];

    private ?string $unit_test_path = null;
    private ?string $core_test_path = null;
    private string $app_test_folder = "";
    private string $modules_folder  = "";

    public function __construct(
        ?string $unit_test_path     = null, 
        ?string $core_test_path     = null,
        string  $app_test_folder    = "tests", 
        string $modules_folder      = "tests"
    ) {

        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->unit_test_path = $unit_test_path;
        $this->core_test_path = $core_test_path;
        $this->app_test_folder = $app_test_folder;
        $this->modules_folder = $modules_folder;

        $this->argument(
            '[testsOf]', 
            sprintf('Which level of tests to run (%s) empty for interactive mode', implode(',', self::TESTS_OFS))
        );
        $this->option('-a, --all', 'Run all tests', 'boolval', false);
        $this->option('-t, --test', 'Which test to run', 'strval', "__none__");

        $this->usage(
            // append details or explanation of given example with ` ## ` so they will be uniformly aligned when shown
            sprintf('<bold> %s</end><eol/>', self::COMMAND)
        );
    }

    private function validate_test_name($input) {
        
        // Remove all illegal characters from a filename and replace them with a hyphen:
        $input = preg_replace('/[^A-Za-z0-9\-._]/', '-', $input);
        if (!empty($input) && $input !== "__none__") {
            return $input;
        }
        throw new \InvalidArgumentException('test name is invalid');
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {

        if (!in_array($this->testsOf, self::TESTS_OFS)) {

            if (!empty($this->testsOf)) 
                $io->error(sprintf('Invalid testsOf: {%s} ', $this->testsOf)); 

            $choices = self::TESTS_OFS;
            $choice  = $io->choice('Select a tests of:', $choices, self::DEFAULT_OF);
            $io->greenBold("You selected: {$choice}", true);
            $this->set('testsOf', $choice);

        }

        if ($this->test === "__none__" && !$this->all) {
            $test = trim(
                $io->prompt(
                    text : 'Which test to run?', 
                    default : null, 
                    fn : \Closure::fromCallable([$this, 'validate_test_name']),
                    retry : 3
                )
            );
            if ($test === "") {
                $io->error('--test name is invalid');
                exit(1);
            } elseif ($test === "all") {
                $this->set('all', true);
            } else {
                $this->set('test', $test);
            }
        }

    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $of   = $this->testsOf;
        $test = $this->test;
        $all  = $this->all;

        //Summarize the input:
        $mes = "Running tests of <boldCyan>%s</end> - Tests: <boldGreen>%s</end>";
        $io->writer()->colors(sprintf($mes, $of, $all ? 'all' : $test))->eol();

        //Parse the test name:
        if (!$all) {
            if (str_ends_with( $test, "Test")) {
                $test = "/".ltrim(trim($test).".php", "\\/");
            } elseif(str_ends_with( $test, ".php")) {
                $test = "/".trim($test, "\\/ \t\n\r\0\x0B");
            } else {
                $test = "/".ltrim(trim($test)."Test.php", "\\/");
            }
        } else {
            $test = "";
        }

        //Build the path:
        $path = "";
        switch ($of) {
            case 'core':
                $path = rtrim($this->core_test_path, "\\/ ");
                break;
            case 'app':
                $path = rtrim($this->app_test_folder, "\\/ ");
                break;
            case 'modules':
                $path = rtrim($this->modules_folder, "\\/ ");
                // TODO: Implement modules tests should test be a module name?
                // and then test all module test inside the module tests folder?
                break;
        }

        $path = $path.$test;
        //validate the path:
        if (!file_exists($path)) {
            $io->error(sprintf("Tests directory or file not found: %s", $path));
            exit(1);
        }

        // Command to run:
        $com = sprintf("php %s --colors=\"always\" --testdox %s", 
            $this->unit_test_path ?? "phpunit", 
            $path
        );
        // Run tests:
        switch ($of) {
            case 'core':
                $com = sprintf($com, 
                    $this->unit_test_path ?? "phpunit", 
                    $path,
                    $all ? '' : $test
                );

                // print $com;
                $shell = new Shell($com);
                // $shell->loadCurrentEnvPath();
                // $shell->setOptions(env : null);
                $shell->execute();
                echo $shell->getErrorOutput();
                echo $shell->getOutput();

                // echo $shell->getExitCode();
                break;
            case 'app':
                $io->info("Not implemented yet");
                break;
            case 'modules':
                $io->info("Not implemented yet");
                break;
        }

        
    }
}
