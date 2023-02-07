<?php

namespace Siktec\Bsik\Exceptions;

if (!defined("USE_BSIK_ERROR_HANDLERS"))    define("USE_BSIK_ERROR_HANDLERS",   false);
if (!defined("E_PLAT_NOTICE"))              define("E_PLAT_NOTICE",             E_USER_NOTICE);
if (!defined("E_PLAT_WARNING"))             define("E_PLAT_WARNING",            E_USER_WARNING);
if (!defined("E_PLAT_ERROR"))               define("E_PLAT_ERROR",              E_USER_ERROR);

use \Siktec\Bsik\Exceptions;

class BsikUseExcep {

    public static function init()
    {
        //Register:
        if (USE_BSIK_ERROR_HANDLERS && !defined('PREVENT_BSIK_ERROR_HANDLERS')) {
            set_exception_handler(
                [Exceptions\FrameWorkExcepHandler::class, "handleException"]
            );
            set_error_handler(
                [Exceptions\FrameWorkExcepHandler::class, "handleError"], 
                E_ALL
            );
            register_shutdown_function([Exceptions\FrameWorkExcepHandler::class, "handleFatalShutdown"]);
        }
    }
}

