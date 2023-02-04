<?php
define('DS', DIRECTORY_SEPARATOR);
define("ROOT_PATH", dirname(__FILE__).DS.'..'.DS.'..' );
define('USE_BSIK_ERROR_HANDLERS', false);

require_once ROOT_PATH.DS."bsik.php";
require_once BSIK_AUTOLOAD;

use \Bsik\Settings\CoreSettings;
use \Bsik\Base;

/******************************************************************************/
/*********************  LOAD CONF AND DB CONNECTION  **************************/
/******************************************************************************/
Base::configure($conf);
Base::connect_db();
if (!CoreSettings::extend_from_database(Base::$db)) {
    throw new Exception("Cant Load Settings", E_PLAT_ERROR);
}
//Core settings:
CoreSettings::load_constants();
Base::$db->setTrace(false);