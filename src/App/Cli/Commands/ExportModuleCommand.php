<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Input\Command;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\Module\ModuleInstall;

class ExportModuleCommand extends Command
{

    public const COMMAND        = 'export-module';
    public const DESCRIPTION    = 'Package a module for distribution as a zip file';
    public const ALIAS          = 'em';

    public string $cwd  = __DIR__;
    public string $def_path = 'manage/modules'; // relative to cwd for the module folder
    public string $def_out  = '';               // relative to cwd for the output folder or file
    
    public array $exclude = [
        'node_modules/',
        'vendor/',
        'templates/cache/',
        '.git/',
        'composer.lock',
    ];

    /**
     * __construct
     * 
     * @param string $cwd the current working directory if not provided will use getcwd()
     * @param string $folder_path the path to the folder where the logs are stored if is null will use the default path
     * @return void
     */
    public function __construct(
        string $cwd = null,
        string $folder_path = null 
    ) {

        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        if (!is_null($folder_path)) {
            $this->set_folder($folder_path);
        }

        $this->argument(
            '<module>', 
            sprintf('The name of the module to export (in %s)', $this->def_path)
        );
        $this->argument(
            '[output]', 
            sprintf('The output file or folder (default: %s)', $this->def_out)
        );

        $usage = sprintf(
            '<bold> %s</end> <comment>[module:required]</end> <info>[output:optional]</end><eol/>', 
            self::COMMAND
        );
        $this->usage($usage);
    }
    
    /**
     * set_folder
     * 
     * set the folder where the modules are located
     * adds the path to the current working directory
     * 
     * @param  string $path
     * @return void
     * @throws \Exception if path is invalid or not a directory
     * 
     */
    public function set_folder(string $path) : void {

        // check if path is valid
        $build_path = Std::$fs->path($this->cwd, $path);

        // check if path is a directory
        if (!file_exists($build_path) || !is_dir($build_path)) {
            throw new \Exception("Invalid modules folder path: {$build_path}");
        }
        // Save path
        $this->def_path = $path;
    }

    /**
     * find_module
     * get the full path to a module folder if it exists
     * 
     * @param  string $name name of the module
     * @return string|null the full path to the module folder or null if not found
     */
    public function find_module(string $name) : ?string {

        $name = strtolower(trim($name));

        $path = Std::$fs->path($this->cwd, $this->def_path, $name);

        if (!file_exists($path) || !is_dir($path)) {
            return null;
        }

        return $path;
    }

    // validate the module name
    private function validate_module($name) {
        $name = $name ?? "";
        if (empty($name)) {
            throw new \InvalidArgumentException("Invalid empty module name");
        }
        $found = $this->find_module($name);
        if (is_null($found)) {
            throw new \InvalidArgumentException("Invalid module name: {$name}");
        }
        return $found;
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {

        // normalize the module name and validate it if it is not valid prompt for a new one
        $module = $this->find_module($this->module);
        if (is_null($module)) {
            $module = $io->prompt(
                text : 'Invalid module name, please enter a valid module name: ', 
                default : null,
                fn : \Closure::fromCallable([$this, 'validate_module']),
                retry : 2
            );
            if (empty($module)) {
                $io->error('Invalid module name, aborting.');
                exit(1);
            }
        }
        $this->set('module', $module); // now the module is the full path to the module folder

        // normalize the output path
        $rel_output = trim($this->output ?? "") ?: $this->def_out;
        $rel_output = $rel_output === '.' ? '' : $rel_output;
        foreach (['/', '\\', '.\\', './'] as $char) {
            if (strpos($rel_output, $char) === 0) {
                $rel_output = substr($rel_output, strlen($char));
            }
        }
        $output = Std::$fs->path($this->cwd, $rel_output);
        $this->set('output', $output);

    }

    private function auto_generate_name(string $module_path) : string {
        $name = basename($module_path);
        $name = preg_replace('/[^a-z0-9]/i', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        $name = strtolower($name);
        $timestamp = date('YmdHis');
        return "{$timestamp}_{$name}.zip";
    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $module   = $this->module ?? "";
        $output   = $this->output ?? "";
 
        //if output is a folder then add the module name to the path
        if (is_dir($output)) {
            $gen_name = $this->auto_generate_name($module);
            $output = Std::$fs->path($output, $gen_name);
        } elseif (!str_ends_with(strtolower($output), '.zip')) {
            // this is not a folder and the output does not end with .zip
            $io->error("Invalid output file name: {$output} - must be a folder or a .zip file");
            exit(1);
        }

        $io->writer()->colors("Exporting module: <cyan>{$module}</end> to <cyan>{$output}</end></eol>");
        
        
        // Do we have a required files?
        // Not a real installer just for using the ModuleInstall class
        $installer = new ModuleInstall( 
            source      : $output,
            in          : $module, 
            load_zip    : false     // will disable the zip file loading
        );

        // validate the folder structure according to installer:
        $errors = $installer->validate_required_files_in_extracted(
           $module, 
           $installer::REQUIRED_FILES_MODULE
        );
        if (!empty($errors)) {
            $io->info("Invalid module folder structure: {$module}", true);
            foreach ($errors as $error) {
                $io->error(" - {$error}", true);
            }
            exit(1);
        }

        // Do we have a valid module.jsonc or module.json file?
        $jsonc = $installer->get_definition_from_folder($module, $errors);
        if (!empty($errors)) {
            $io->info("Invalid module jsonc: {$module}", true);
            foreach ($errors as $error) {
                $io->error(" - {$error}", true);
            }
            exit(1);
        }

        // Zip the module folder:
        Std::$zip::zip_folder(
            path    : $module, 
            out     : $output, 
            exclude : $this->exclude
        );
        
    }
}
