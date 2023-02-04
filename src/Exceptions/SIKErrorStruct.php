<?php

namespace Siktec\Bsik\Exceptions;

class SIKErrorStruct
{
    private $obj;
    
    public function __construct($class, $code, $mes, $file, $line, $trace = [])
    {
        $this->obj = [
            "class"     => $class,
            "code"      => $code,
            "mes"       => $mes,
            "file"      => $file,
            "line"      => $line,
            "trace"     => $trace
        ];
    }
    
    public function get_class()
    {
        return $this->obj["class"];
    }
    
    public function getCode()
    {
        return $this->obj["code"];
    }
    
    public function getMessage()
    {
        return $this->obj["mes"];
    }
    
    public function getFile()
    {
        return $this->obj["file"];
    }
    
    public function getLine()
    {
        return $this->obj["line"];
    }
    
    public function getTrace()
    {
        return $this->obj["trace"];
    }
    
    public function str()
    {
        return sprintf(
            " %s -> %s : File[%s:%s] ",
            $this->obj["class"] ?? "",
            $this->obj["mes"] ?? "",
            $this->obj["file"] ?? "",
            $this->obj["line"] ?? ""
        );
    }
}