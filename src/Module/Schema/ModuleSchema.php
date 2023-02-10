<?php

namespace Siktec\Bsik\Module\Schema;

use \Exception;
use \Siktec\Bsik\Std;

class ModuleSchema {

    private const   DEFAULT_VERSION = "1.0.0";
    public const    SCHEMA_FOLDER   = "Versions";

    private const   SCHEMAS = [
        "install" => [
            "template" => "module.install.%s.jsonc"
        ],
        "module"  => [
            "template" => "module.define.%s.jsonc"
        ],
    ];

    public static array $CUSTOM_VALIDATORS = []; /** decalred in the constructor */

    private string $type;
    private string $version;
    private string $template_name;
    public ?SchemaObj $schema_template = null;
    
    /**
     * __construct
     *
     * @param  string $_type - which schema type we are proccessing 
     * @param  ?string $_version - target version or null for default
     * @throws Exception => E_PLAT_ERROR if schema type is not supported.
     * @return void
     */
    public function __construct(string $_type, ?string $_version = null) {
        $this->type            = trim($_type);
        $this->version         = trim($_version ?? self::DEFAULT_VERSION);
        //Load the correct template:
        if (array_key_exists($this->type, self::SCHEMAS) && Std::$str::is_version($this->version)) {
            $this->template_name     = sprintf(self::SCHEMAS[$this->type]["template"], $this->version);
            $this->schema_template = $this->get_template($this->template_name);
        } else {
            $this->schema_template = new SchemaObj();
        }
    }

    public function get_template(string $template_name) : ?SchemaObj {

        $sch = new SchemaObj();
        
        //Find the schema template:
        $template_path = Std::$fs::file_exists("raw", [__DIR__, self::SCHEMA_FOLDER, $template_name]);
        
        //If not found return:
        if ($template_path === false) {
            $sch->status    = false;
            $sch->message   = "Schema version is not supported";
            return $sch;
        }
        //Load the schema:
        $sch->struct = Std::$fs::get_json_file($template_path["path"]) ?? [];
        if (empty($sch->struct)) {
            $sch->status    = false;
            $sch->message   = "Schema is corrupted";
        } else {
            $sch->status    = true;
            $sch->message   = "loaded";
        }
        return $sch;
    }
    
    public function is_loaded() {
        return $this->schema_template->status;
    }

    public function get_message() {
        return $this->schema_template->message;
    }

    /**
     * naming - get the human readable name of the container.
     *
     * @param  string $naming
     * @return string
     */
    public function naming(string $naming) : string {
        return $this->is_loaded() ? $this->schema_template->struct['$schema_naming'][$naming] ?? "" : "";
    }
    
    /**
     * validate a json file against the loaded schema
     *
     * @param  array $struct
     * @return bool
     */
    public function validate(ModuleDefinition $definition) : bool {
        return $this->is_loaded() 
                ? Std::$arr::validate(
                    $this->schema_template->struct['$schema_required'], 
                    $definition->struct, 
                    ModuleSchema::$CUSTOM_VALIDATORS, 
                    $definition->errors, 
                    false
                    )
                : false;
    }

    public function create_definition(array $struct) : ModuleDefinition {

        $definition = new ModuleDefinition();
        
        // clone the schema template:
        $definition->schema = $this;
        $definition->struct = Std::$arr::extend($this->schema_template->struct, $struct);
        $definition->valid = $this->validate($definition);
        return $definition;
    }

}

/* 
 * Declare some validators used by the schemaloader:
 * Later bindings because we are using static methods
 */

ModuleSchema::$CUSTOM_VALIDATORS["version"] = \Closure::fromCallable([Std::$str, "is_version"]);
ModuleSchema::$CUSTOM_VALIDATORS["strlen"] = function($value, $min, $max) {
    if (strlen($value) > $max) 
        return "value is too long";
    if (strlen($value) < $min) 
        return "value is too short";
    return true;
};
ModuleSchema::$CUSTOM_VALIDATORS["oneof"] = function($value, $opt) {
    if (!in_array($value, $opt, true)) 
        return "value is not one of the allowed values";
    return true;
};
ModuleSchema::$CUSTOM_VALIDATORS["url"] = function($value) {
    return empty($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
};
ModuleSchema::$CUSTOM_VALIDATORS["domain"] = function($value, $allowed) {
    $domain = parse_url($value, PHP_URL_HOST);
    return empty($value) || in_array($domain, $allowed, true);
};
ModuleSchema::$CUSTOM_VALIDATORS["github"] = function($value) {
    return empty($value) || (filter_var($value, FILTER_VALIDATE_URL) && parse_url($value, PHP_URL_HOST) === "github.com");
};
ModuleSchema::$CUSTOM_VALIDATORS["email"] = function($value) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
};
ModuleSchema::$CUSTOM_VALIDATORS["equal"] = function($value, $cmp) {
    return strcmp($value, $cmp) === 0;
};