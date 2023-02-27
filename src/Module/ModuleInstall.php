<?php

namespace Siktec\Bsik\Module;

/******************************  Requires       *****************************/
use \Exception;
use \Throwable;
use \ZipArchive;
use \SplFileInfo;
use \Siktec\Bsik\Std;
use \Siktec\Bsik\CoreSettings;
use \Siktec\Bsik\Base;
use Siktec\Bsik\Module\Schema\ModuleDefinition;
use \Siktec\Bsik\Module\Schema\ModuleSchema;
use Siktec\Bsik\Storage\MysqliDb;


/********************** Installer *******************************************/
class ModuleInstall {

    //Path and extraction related:
    public const            MODULE_FILE_EXT         = "zip";
    public const            MODULE_REGISTER_TABLE   = "bsik_modules";
    private const           TEMP_FOLDER_PREFIX      = "bsik_m_temp_";
    private string          $rand_id                = "";
    private string          $temp_folder_path       = "";  
    private ?SplFileInfo    $install_in             = null;
    public  ?SplFileInfo    $temp_extracted         = null;
    private ?SplFileInfo    $source                 = null;
    public  ?ZipArchive     $zip                    = null;

    private ?MysqliDb       $db                     = null;

    //Validation related
    public const REQUIRED_FILES_INSTALL = [
        "module.jsonc" => "jsonc"
        //"module.php"   => "exists" //TODO: this should be checked only when single included or 
    ];
    public const REQUIRED_FILES_MODULE = [
        "module.jsonc" => "jsonc",
        "module.php"   => "exists"
    ];

    public const MODULE_STATUS_DISABLED = 0;
    public const MODULE_STATUS_ACTIVE   = 1;
    public const MODULE_STATUS_UPDATES  = 2;
    /** 
     * Construct ModuleInstall
     * 
     * @param string $source => path to archive on server.
     * @param string $in   => path to default extract destination folder.
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return ModuleInstall
     */
    public function __construct(
        string|SplFileInfo $source, 
        string|SplFileInfo|null $in = null,
        bool $load_zip = true,
        MysqliDb|null $db = null
    ) {
        $in = $in ?? CoreSettings::$path["manage-modules"];
        $this->source           = is_string($source) ? new SplFileInfo($source) : $source;
        $this->install_in       = is_string($in) ? new SplFileInfo($in) : $in;
        $this->rand_id          = std::$date::time_datetime("YmdHis");
        $this->temp_folder_path = Std::$fs::path($this->install_in->getRealPath(), self::TEMP_FOLDER_PREFIX.$this->rand_id);
        if ($load_zip) {
            $this->zip = Std::$zip::open_zip($this->source->getRealPath() ?: "");
        } 

        // We try to set the db connection if not passed as a dependency
        if (is_null($db)) {
            $this->db = Base::$db;
        } else {
            $this->db = $db;
        }
        
    }

    /** 
     * validate_required_files_in_zip - validate an zip in module archive.
     * 
     * @return array => array of errors message if any
     */
    public function validate_required_files_in_zip(array $required = []) : array {
        $errors = [];
        if ($this->zip->filename && $this->zip->status === ZipArchive::ER_OK) {
            //List the files in zip
            $list = Std::$zip::list_files($this->zip);
            //Validate required - simple validation just of presence and format:
            foreach ($required as $file => $validate) {
                if (array_key_exists($file, $list)) {
                    $content = $this->zip->getFromIndex($list[$file]["index"]);
                    if (!$this->validate_file("", $validate, $content)) {
                        $errors[] = "File is invalid [{$file}] expected [{$validate}]";
                    }
                } else {
                    $errors[] = "Required file missing [{$file}]";
                }
            }
        } else {
            $errors[] = "zip archive not loaded";
        }
        return $errors;
    }

    /** 
     * validate_required_files_in_extracted - validate an extracted zip in folder.
     * @param  mixed $folder => null for loaded, string for given folder path 
     * @return array         => array of errors message if any
     */
    public function validate_required_files_in_extracted($folder = null, array $required = []) : array {
        $folder = $folder ?? $this->temp_extracted->getRealPath();
        $errors = [];
        foreach ($required as $file => $validate) {
            if (!Std::$fs::path_exists($folder, $file)) {
                $errors[] = "Required file missing [{$file}]";
                continue;
            }
            if (!$this->validate_file(Std::$fs::path($folder, $file), $validate)) {
                $errors[] = "File is invalid [{$file}] expected [{$validate}]";
            }
        }
        return $errors;
    }

    /** 
     * validate_file - validate an a file that is required by its type.
     * @param  string $path             => the file path will be used only if $content is null. 
     * @param  string $validate_type    => the file content validation required (json, jsonc etc.)  
     * @param  mixed $content           => null for load from path or the file content
     * @return bool                     => true on valid
     */
    public function validate_file(string $path, string $validate_type, ?string $content = null) : bool {
        
        switch ($validate_type) {
            case "jsonc": {
                if (is_null($content)) {
                    $content = file_get_contents($path) ?? "";
                }
                $json = Std::$str::strip_comments($content);
                if (!Std::$str::is_json($json)) {
                    return false;
                }
            } break; 
            case "json": {
                if (is_null($content)) {
                    $content = file_get_contents($path) ?? "";
                }
                $json = $content;
                if (!Std::$str::is_json($json)) {
                    return false;
                }
            } break;
        }
        return true;
    }

    /** 
     * close_zip - close and release a loaded zip file
     * safe to use even when not opened
     * @return void
     */
    public function close_zip() {
        try {
            $this->zip->close();
        } catch (Throwable) { ; }
    }

    /** 
     * temp_deploy
     * extracts the loaded zip archive to a temp folder inside
     * the install folder.
     * 
     * @param   bool $close_after - whether to close the zip file or not.
     * @return  bool true when success
     */
    public function temp_deploy(bool $close_after = false, int $flags = 0) : bool {
        if ($result = Std::$zip::extract_zip($this->zip, $this->temp_folder_path, $flags)) {
            $this->temp_extracted = new SplFileInfo($this->temp_folder_path);
        } 
        if ($close_after) {
            $this->close_zip();
        }
        return $result;
    }
    
    public function temp_delete() : bool {
        if ($this->temp_extracted && $path = $this->temp_extracted->getRealPath()) {
            return Std::$fs::clear_folder($path, true);
        }
        return false;
    }
    
    /**
     * clean - will clear an remove the temp folder if its there
     * 
     * @return bool - true if temp was cleared false if no temp folder.
     */
    public function clean() : bool {
        if ($this->temp_extracted && $path = $this->temp_extracted->getRealPath()) {
            return Std::$fs::clear_folder($path, true);
        }
        return false;
    }
    
    public function get_definition_from_zip(array &$errors = []) : ?ModuleDefinition {
        $module_json = [];

        if ($this->zip->locateName('module.jsonc') !== false) {
            $json = $this->zip->getFromName('module.jsonc') ?: "";
            $module_json = Std::$str::parse_jsonc($json);
        }

        if (!is_array($module_json) || empty($module_json)) {
            $errors[] = "module.jsonc is missing or not readable";
            return null;
        }

        return $this->get_definition($module_json, $errors);
    }

    public function get_definition_from_folder($folder = null, array &$errors = []) : ?ModuleDefinition {
        
        $folder = $folder ?? $this->temp_extracted->getRealPath();

        // get the module.jsonc: 
        $module_json = Std::$fs::get_json_file(
            Std::$fs::path($folder, "module.jsonc")
        );

        if (empty($module_json)) {
            $errors[] = "module.jsonc is missing";
            return null;
        }

        return $this->get_definition($module_json, $errors);
    }

    private function get_definition(array $json, array &$errors = []) : ?ModuleDefinition {
        if (empty($json["schema"] ?? "")) {
            $errors[] = "module.jsonc is missing schema version definition";
            return null;
        }

        if (empty($json["schema_type"] ?? "")) {
            $errors[] = "module.jsonc is missing schema type definition";
            return null;
        }

        // Prepare the schema and validate:
        $schema = new ModuleSchema($json["schema_type"], $json["schema"]);
        if (!$schema->is_loaded()) {
            $errors[] = $schema->get_message();
            return null;
        }

        //Create the given definition and validate:
        $module_def = $schema->create_definition($json);
        if (!$module_def->valid) {
            // Push the errors to the given array we need to flatten the errors:
            foreach ($module_def->errors as $type => $error) {
                if (is_array($error)) {
                    foreach ($error as $e) {
                        $errors[] = $type.": ".$e;
                    }
                } else {
                    $errors[] = $error;
                }
            }
            return null;
        }

        return $module_def;
    }

    public function install($by = null, ?string $from = null, ?ModuleDefinition $module_def = null) : array {

        $from = $from ?? $this->temp_extracted->getRealPath();
        $installed = [];
        
        if (is_null($module_def)) {
            // Get the module definition from the given path:
            $errors = [];
            $module_def = $this->get_definition_from_folder($from, $errors);
            if (!empty($errors)) {
                return [false, $errors, []];
            }
        }

        // Install from module define:
        if ($module_def->get_value("schema_type") === "module") {
            
            //Install the module:
            [$status, $module_name, $errors] = $this->install_definition($module_def, $by);

            if (!$status) {
                return [false, $errors, $installed];
            } else {
                $installed[] = $module_name;
            }

        } elseif ($module_def->get_value("schema_type") === "install") {
            //Its an install schema:
            switch ($module_def->get_value("this.type")) {
                case "single" : {
                    //Install the module:
                    $module = $module_def->get_value("\$modules_container")[0];
                    [$status, $module_name, $errors] = $this->install_bundle($module, $by);
                    if (!$status) {
                        return [false, $errors, $installed];
                    } else {
                        $installed[] = $module_name;
                    }
                } break;
                case "bundle" : {
                    return [false, ["bundle installation is not supported yet"], []];
                } break;
            }
        }
    
        //Return
        return [true, ["installed"], $installed];
    }

    private function install_definition(ModuleDefinition $module, $by = null) : array {

        $name = $module->get_value('name');
        $name = Std::$str::filter_string($name ?? "unknown", ["A-Z","a-z","0-9", "_"]);
        $type = $module->get_value('type');
        $path = Std::$fs::path($this->install_in->getRealPath(), $name);
        
        // validate its a new module:
        if ( Std::$fs::path_exists($path) ) {
            return [true, $name, ["Module already installed - you must uninstall it first or use the update command."]]; // We return true because its allready in
        }

        // Load the schema to use:
        $schema = new ModuleSchema("module", $module->get_value("schema"));
        if (!$schema->is_loaded()) {
            return [false, $name, [$schema->get_message()]];
        }

        // install the module:
        //Now install the module:
        switch ($type) {

            case "included": {

                //Move temp folder:
                if (!Std::$fs::xcopy($this->temp_extracted->getRealPath(), $path)) {
                    return [false , $name , ["failed to copy module to destination"]];
                }

                //Register on DataBase:
                $info = $module->struct;

                // Remove the schema and menu from the info:
                unset($info["menu"]);
                unset($info["\$schema_naming"]);
                unset($info["\$schema_required"]);

                // Register the module:
                if (!$this->db->insert("bsik_modules", [
                    "name"          => $name,
                    "status"        => self::MODULE_STATUS_ACTIVE,
                    "updates"       => 0,
                    "path"          => $name.'/',
                    "settings"      => "{}",
                    "menu"          => json_encode($module->struct[$schema->naming("menu_container")]),
                    "version"       => $module->get_value("ver"),
                    "created"       => $this->db->now(),
                    "updated"       => $this->db->now(),
                    "info"          => json_encode($info),
                    "installed_by"  => $by // TODO: set the user id or system user.
                ])) {
                    //Remove folder:
                    Std::$fs::clear_folder($path, true);
                    return [false, $name, ["failed to register module to database"]];
                };
            } break;
            case "remote": {
                return [false, $name, ["remote modules are not supported yet"]];
            } break;
        }

        return [true, $name, ["module installed"]];

        return [];
    }

    private function install_bundle(array $single_definition, $by = null) : array {
        $module_name = Std::$str::filter_string($single_definition["name"] ?? "unknown", ["A-Z","a-z","0-9", "_"]);
        $module_path = Std::$fs::path($this->install_in->getRealPath(), $module_name);

        // validate its new module:
        if (
                $this->db->where("name", $module_name)->has("bsik_modules")
            ||  Std::$fs::path_exists($module_path)
        ) {
            return [true, $module_name, ["allready installed"]]; // We return true because its allready in
        }
        
        //Load schema:
        $schema = new Schema\ModuleSchema("module", $single_definition["schema"] ?? "");
        if (!$schema->is_loaded()) {
            return [false, $module_name, [$schema->get_message()]];
        }

        //Create the given definition and validate:
        $module = $schema->create_definition($single_definition);
        if (!$module->valid) {
            return [false, $module_name, $module->errors];
        }

        //Now install the module:
        switch ($module->struct["type"]) {
            case "included": {
                //Vallidate required:
                $errors = $this->validate_required_files_in_extracted(null, self::REQUIRED_FILES_MODULE);
                if (!empty($errors)) {
                    return [false, $module_name, $errors];
                }
                //Move temp folder:
                if (!Std::$fs::xcopy($this->temp_extracted->getRealPath(), $module_path)) {
                    return [false , $module_name , ["failed to copy module to destination"]];
                }
                //Register on DataBase:
                $info = $module->struct;
                unset($info["menu"]);
                unset($info["\$schema_naming"]);
                unset($info["\$schema_required"]);
                if (!$this->db->insert("bsik_modules", [
                    "name"          => $module_name,
                    "status"        => 1,
                    "updates"       => 0,
                    "path"          => $module_name.'/',
                    "settings"      => "{}",
                    "menu"          => json_encode($module->struct[$schema->naming("menu_container")]),
                    "version"       => $module->struct["ver"],
                    "created"       => $this->db->now(),
                    "updated"       => $this->db->now(),
                    "info"          => json_encode($info),
                    "installed_by"  => $by
                ])) {
                    //Remove folder:
                    Std::$fs::clear_folder($module_path, true);
                    return [false, $module_name, ["failed to register module to database"]];
                };
            } break;
            case "remote": {
                return [false, $module_name, ["remote modules are not supported yet"]];
            } break;
        }
        return [true, $module_name, ["module installed"]];
    }
}