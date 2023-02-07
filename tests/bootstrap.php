<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR );
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__).DS.'..' );
}
if (!defined('BSIK_AUTOLOAD')) {
    // First try to load from vendor folder:
    $package = ROOT_PATH.DS.'vendor'.DS.'autoload.php';
    $required_package = ROOT_PATH.DS.'..'.DS.'..'.DS.'autoload.php';
    if (file_exists($package)) {
        define('BSIK_AUTOLOAD', $package);
    } elseif (file_exists($required_package)) {
        define('BSIK_AUTOLOAD', $required_package);
    } else {
        throw new Exception("Cant find autoload.php");
    }
}
if (!defined('USE_BSIK_ERROR_HANDLERS')) {
    define('USE_BSIK_ERROR_HANDLERS', false);
}

require_once BSIK_AUTOLOAD;

use \Siktec\Bsik\CoreSettings;
use \Siktec\Bsik\Exceptions\BsikUseExcep;
use \Siktec\Bsik\Base;

/******************************************************************************/
/*********************  LOAD CONF AND DB CONNECTION  **************************/
/******************************************************************************/
// Base::configure($conf);
// Base::connect_db();
// if (!CoreSettings::extend_from_database(Base::$db)) {
//     throw new Exception("Cant Load Settings", E_PLAT_ERROR);
// }

//Core settings:
CoreSettings::init();
CoreSettings::load_constants();
BsikUseExcep::init();
// Base::$db->setTrace(false);

//Phpunit var_dump:
function buf_var_dump(...$var) {

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $from = ($trace[1]['file'] ?? "Unknown File").
            " -> ".
            ($trace[1]['function'] ?? "Unknown Function").
            " -> ".
            ($trace[1]['line'] ?? "Unknown Line");
    echo PHP_EOL.PHP_EOL."Debug Test: ".PHP_EOL.$from.PHP_EOL;
    echo "=============================".PHP_EOL.PHP_EOL;
    // print the output:
    var_dump(...$var);
    echo PHP_EOL."=============================".PHP_EOL;

    // flush the output if needed:
    if (ob_get_level() > 0) {
        ob_flush();
    }

}