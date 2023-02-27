<?php

namespace Siktec\Bsik\App\Cli\Commands;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Input\Command;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\Base;
use \Siktec\Bsik\Module\ModuleInstall;
use \Siktec\Bsik\App\Ext\Composer;

class InstallModuleCommand extends Command
{

    use CommandResponseTrait;

    public const COMMAND        = 'install-module';
    public const DESCRIPTION    = 'Install a module from a zip file';
    public const ALIAS          = 'im';

    public const MODULE_FILE_EXT = 'zip';

    public string $cwd      = __DIR__;
    public string $def_path = 'manage/modules'; // relative to cwd for the module folder

    private bool $force_json = false;

    /**
     * __construct
     * 
     * @param string $cwd the current working directory if not provided will use getcwd()
     * @param string $folder_path the path to the  folder where the modules are located
     * @param bool $as_json if true will force output the result as json
     * @param array $db the database connection settings to use
     * @return void
     */
    public function __construct(
        string $cwd         = null,
        string $folder_path = null, 
        bool $as_json       = false, // this will force the output as json
    ) {

        parent::__construct(self::COMMAND, self::DESCRIPTION);

        $this->cwd = ($cwd ?? getcwd()) ?: __DIR__;

        if (!is_null($folder_path)) {
            $this->set_folder($folder_path);
        }

        $this->argument(
            '<module>', 
            sprintf('path to the module zip file')
        );
        $this->option(
            '-j, --json', 
            sprintf('Output the result as json'),
        );
        $usage = sprintf(
            '<bold> %s</end> <comment>[module:required]</end> <info>--json:optional</end><eol/>', 
            self::COMMAND
        );
        $this->usage($usage);

        //Set the default value for the json force
        $this->force_json = $as_json;

        // Make sure we have a db connection:
        if (Base::$db === null || !Base::$db->ping()) {
            Base::connect_db();
        }

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
     * module_folder
     * get the full path to a module folder
     * 
     * @param  string $name name of the module
     * @return string the full path to the module folder 
     */
    public function module_folder(string $name) : string {

        return Std::$fs->path($this->cwd, $this->def_path, strtolower(trim($name)));
    }

    // This method is auto called before `self::execute()`
    public function interact(Interactor $io) : void
    {

        // force json output?
        $this->force_json = $this->force_json || $this->json;

        $module_path = Std::$fs::path($this->cwd, $this->module ?? "");
        // get the module path to file
        $module = file_exists($module_path) ? $module_path : null;

        if (is_null($module)) {
            // response error:
            $this->response_error($io, 
                'Invalid module file: %s', 
                [$this->module ?? ""], 
                [
                    "module" => $this->module ?? "",
                    "reason" => "Module file does not exist."
                ], 
                true, true
            );
        }
        $this->set('module', $module); // now the module is the full path to the module folder

    }

    // When app->handle() locates `init` command it automatically calls `execute()`
    public function execute()
    {
        /** @var Interactor $io */
        $io = $this->app()->io();

        $file           = new \SplFileInfo($this->module); // we know this is a valid file
        $modules_folder = Std::$fs->path($this->cwd, $this->def_path);

        // if module file is not a valid file or not a zip file:
        if (!$file->isFile() || !$file->isReadable() || strtolower($file->getExtension()) !== self::MODULE_FILE_EXT) {
            $this->response_error($io, 
                'Invalid module file: %s - must be a zip file and readable.', 
                [$file->getFilename()], 
                [
                    "module" => $file->getFilename(), 
                    "module_path" => $file->getPathname(),
                    "reason" => "Must be a zip file and readable."
                ], 
                true, true
            );
        }

        //if module folder is not a valid module folder:
        if (!file_exists($modules_folder) || !is_dir($modules_folder)) {
            $this->response_error($io, 
                'Invalid modules folder: %s', 
                [$modules_folder], 
                ["modules_folder" => $modules_folder], 
                true, true
            );
        }

        // the source:
        $installer = new ModuleInstall( 
            source      : $file,
            in          : $modules_folder, 
            load_zip    : true,     // will disable the zip file loading
            db          : Base::$db
        );

        // validate zip file and get the module name
        $errors = $installer->validate_required_files_in_zip(
            $installer::REQUIRED_FILES_MODULE
        );
        if (!empty($errors)) {
            $this->response_error($io, 
                'Invalid module file: %s - missing required files.', 
                [$file->getFilename()], 
                [
                    "module" => $file->getFilename(), 
                    "module_path" => $file->getPathname(),
                    "reason" => $errors
                ], 
                true, true
            );
        }

        // get and validate module definition:
        $errors = [];
        $definition = $installer->get_definition_from_zip($errors);
        if (!empty($errors) || is_null($definition)) {
            $this->response_error($io, 
                'Invalid module file: %s - module.jsonc is invalid', 
                [$file->getFilename()], 
                [
                    "module" => $file->getFilename(), 
                    "module_path" => $file->getPathname(),
                    "reason" => $errors
                ], 
                true, true
            );
        }

        //The module name:
        $module_name = $definition->get_value('name');
        $module_target_folder = $this->module_folder($module_name);

        // check if module already exists
        if (Std::$fs::file_exists("raw", $module_target_folder)) {
            $this->response_error($io, 
                'Module already installed: %s - you must uninstall it first or use the update command.', 
                [$module_name], 
                [
                    "module" => $module_name, 
                    "module_file" => $file->getFilename(),
                    "module_path" => $file->getPathname(),
                    "currently_installed" => $module_target_folder
                ], 
                true, true
            );
        }

        // install module
        // Temp extract -> then install:
        if (!$installer->temp_deploy( close_after : true)) {

            // make sure we delete the temp folder:
            $installer->temp_delete();

            $this->response_error($io, 
                'Error installing module: %s - could not extract module files.', 
                [$module_name], 
                [
                    "module"      => $module_name, 
                    "module_file" => $file->getFilename(),
                    "module_path" => $file->getPathname(),
                ], 
                true, true
            );
        }
        
        // execute module install script:
        [$status, $errors, $installed_arr] = $installer->install(by : null, from : null, module_def: $definition);
        if (!$status) {

            // make sure we delete the temp folder:
            $installer->temp_delete();

            $this->response_error($io, 
                'Error installing module: %s - %s', 
                [$module_name, implode(", ", $errors)], 
                [
                    "module"      => $module_name, 
                    "module_file" => $file->getFilename(),
                    "module_path" => $file->getPathname(),
                    "reason"      => $errors
                ], 
                true, true
            );
        }

        // make sure we delete the temp folder:
        $installer->temp_delete();

        // Now we can handle the composer changes:
        $composer_require   = $definition->get_value('require');
        $composer_classmap  = $definition->get_value('autoload.classmap');
        $composer_psr4      = $definition->get_value('autoload.psr-4');
        $added_classmaps        = false;
        $added_psr4_autoloader  = false;
        $added_packages         = false;
        $composer_errors        = [];
        if (!empty($composer_require) || !empty($composer_classmap) || !empty($composer_psr4)) {

            $composer = Composer::create(stream : "memory", cwd : $this->cwd);

            // Add composer classmap:
            if (is_array($composer_classmap)) {
                // Add composer classmap:
                $added_classmaps = $composer->run_add_classmap(
                    $composer_classmap, $this->def_path . '/' . $module_name
                );
            }

            // Add composer psr-4:
            if (is_array($composer_psr4)) {
                // Add composer psr-4:
                $added_psr4_autoloader = $composer->run_add_psr4(
                    $composer_psr4, $this->def_path . '/' . $module_name
                );
            }

            if (is_array($composer_require)) {

                // Require composer packages:
                $to_update = "";
                foreach ($composer_require as $package => $version) {
                    $got = $composer->run_require($package, $version, extra : "--no-update");
                    if (!$got["result"]) {
                        $composer_errors[] = "Could not require package: $package";
                    } else {
                        $to_update .= " " . trim($package);
                    }
                }
                if (!empty($to_update)) {
                    $got = $composer->run_update($to_update);
                    $added_packages = $got["result"];
                    if (!$added_packages) {
                        $composer_errors[] = "Could not update packages: $to_update";
                    }
                }
            }

            $composer->done();
        }

        // Check for composer errors:
        if (!empty($composer_errors)) {
            //TODO: handle composer errors
            // 1. delete module folder
            // 2. delete db entries
            // 3. delete composer classmap and psr-4
            // 4. return error
            $this->response_error($io, 
                "Error installing module: %s - \n - %s", 
                [$module_name, implode("\n - ", $composer_errors)], 
                [
                    "module"      => $module_name, 
                    "module_file" => $file->getFilename(),
                    "module_path" => $file->getPathname(),
                    "reason"      => $composer_errors
                ], 
                true, true
            );
        }

        // Execute Module events:
        //TODO: execute module events

        // response success
        $this->response_success($io, 
            "Module '%s' installed successfully", 
            [ $module_name ], // TODO: get module name from zip file
            [
                "module"      => $module_name,
                "module_file" => $file->getFilename(),
                "module_path" => $file->getPathname(),
                "installed"   => $installed_arr, 
                "module_folder" => $module_target_folder,
                'composer' => [
                    'added_classmaps'       => $added_classmaps,
                    'added_psr4_autoloader' => $added_psr4_autoloader,
                    'added_packages'        => $added_packages,
                    'errors'                => $composer_errors
                ]
            ], 
            true, true
        );
    }

    
}
