<?php

namespace Siktec\Bsik\Exceptions;

use \Siktec\Bsik\CoreSettings;

if (!defined("USE_BSIK_ERROR_HANDLERS"))    define("USE_BSIK_ERROR_HANDLERS",   false);
if (!defined("E_PLAT_NOTICE"))              define("E_PLAT_NOTICE",             E_USER_NOTICE);
if (!defined("E_PLAT_WARNING"))             define("E_PLAT_WARNING",            E_USER_WARNING);
if (!defined("E_PLAT_ERROR"))               define("E_PLAT_ERROR",              E_USER_ERROR);

class FrameWorkExcepHandler
{
    public static array $types = [
        0               => "UNKNOWN",
        E_ERROR         => "ERROR",
        E_USER_ERROR    => "ERROR",
        E_PLAT_ERROR    => "ERROR",

        E_CORE_ERROR    => "FATAL",
        E_COMPILE_ERROR => "FATAL",
        E_CORE_WARNING  => "FATAL",
        E_PARSE         => "FATAL",

        E_NOTICE          => "NOTICE",
        E_USER_NOTICE     => "NOTICE",
        E_DEPRECATED      => "NOTICE",
        E_USER_DEPRECATED => "NOTICE",
        E_STRICT          => "NOTICE",
        E_PLAT_NOTICE     => "NOTICE",

        E_WARNING       => "WARNING",
        E_USER_WARNING  => "WARNING",
        E_PLAT_WARNING  => "WARNING",

    ];
    public static function handleFatalShutdown()
    {
        $error = error_get_last();
        if ( 
            is_array($error) && (
                $error["type"] == E_CORE_ERROR || 
                $error["type"] == E_CORE_WARNING || 
                $error["type"] == E_COMPILE_ERROR  
            )
        ) {
            self::handleError($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }
    public static function handleException(\Throwable $e)
    {
        //Log the Exception:
        $to_log = new SIKErrorStruct(
            (self::$types[@$e->getCode()] ?? "UNKNOWN"), 
            $e->getCode(), 
            $e->getMessage(), 
            $e->getFile(), 
            $e->getLine()
        );
        error_log($to_log->str(), 0);
        
        //Expose:
        if (defined("ERROR_METHOD") && constant('ERROR_METHOD') == 'inline') {
            print self::render($e);
        } else { // (ERROR_METHOD == 'redirect') {
            header(
                "Location: ".
            str_replace('\\', '/', CoreSettings::$url["full"].DS."error.php?"."&pack=".base64_encode(self::json_pack($e))),
                true,
                301
            );
            exit();
        }
        return true;
    }
    public static function handleError($errno, $errstr, $errfile, $errline)
    {

        //Suppressed ?
        if (!(error_reporting() & $errno)) {
            return false; // Silenced
        }
        
        //Log the Error first:
        $to_log = new SIKErrorStruct(
            (self::$types[$errno] ?? "UNKNOWN"), 
            $errno, 
            $errstr, 
            $errfile, 
            $errline,
            @debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        );
        error_log($to_log->str(), 0);

        if (defined("ERROR_METHOD") && constant('ERROR_METHOD') == 'inline') {
            switch ($errno) {
                /*Fatal*/
                case E_ERROR:
                case E_USER_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_PLAT_ERROR:
                    @ob_end_clean();
                    print self::render($to_log, true);
                    exit(1);
                break;
                default:
                print self::render($to_log, true);
                break;
            }
        } else {
            return false;
        }
        /* Don't execute PHP internal error handler */
        return true;
    }
    private static function json_pack($e, $flags = 0)
    {
        return json_encode([
            "class" => get_class($e),
            "code"  => $e->getCode(),
            "message"  => $e->getMessage(),
            "file"     => $e->getFile(),
            "line"     => $e->getLine(),
            "trace"    => $e->getTrace()
        ], $flags);
    }
    private static function getSetContent() {
        $headers = headers_list(); // get list of headers
        foreach ($headers as $header) { // iterate over that list of headers
            if(stripos($header,'Content-Type') !== false) { // if the current header hasthe String "Content-Type" in it
                $headerParts = explode(':',$header); // split the string, getting an array
                return trim($headerParts[1]); // take second part as value
            }
        }
    }
    private static function render($e, $class = false)
    {
        if (
            ini_get('display_errors') === "off" ||
            ini_get('display_errors') === 0 ||
            ini_get('display_errors') === false
        ) {
            return "";
        }
        if (self::getSetContent() === "application/json") {
            return self::json_pack($e, JSON_PRETTY_PRINT);
        } else {
            $style_con = "color:black; display:block; border: 1px solid #cf7474;background-color: #fff5f5;padding: 15px;font-size: 11px;width: 500px;font-family: monospace;";
            $style_header = "margin: 0;text-decoration: underline;font-size: 20px; font-weight:bold; direction:ltr;";
            $style_list_mes = "margin: 0px 15px; direction: ltr;";
            $style_list_trace = "margin: 0px 15px; direction: ltr;";
            $style_list_ele =  "direction: ltr; margin:0;";
            $finalmes = "<div class='error_con' style='".$style_con."'>".
                    "<h2 style='".$style_header."'>".($class == false?get_class($e):$e->get_class())." - Bsik Framework Error!</h2>".
                    "<ul style='".$style_list_mes."'>".
                        "<li style='".$style_list_ele."'>Code:    ".$e->getCode()."</li>".
                        "<li style='".$style_list_ele."'>Message: ".$e->getMessage()."</li>".
                        "<li style='".$style_list_ele."'>File:    ".$e->getFile()."</li>".
                        "<li style='".$style_list_ele."'>Line:    ".$e->getLine()."</li>".
                    "</ul>".
                    "<h3 style='".$style_header." font-size:16px;'>Back-Trace:</h3>".
                    "<ul style='".$style_list_trace."'>";
            foreach ($e->getTrace() as $t) {
                $finalmes .= "<li style='".$style_list_ele."'>";
                $finalmes .= (isset($t['file'])?$t['file']:"Unknown-File")." ";
                $finalmes .= "line " .(isset($t['line'])?$t['line']:"Unknown-Line")." ";
                $finalmes .= "calls " .(isset($t['function'])?$t['function']:"Unknown-Func")."()";
                $finalmes .= "</li>";
            }
            $finalmes .="</ul></div>";
            return $finalmes;
        }
    }
}

