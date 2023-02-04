<?php
/******************************************************************************/
// Created by: shlomo hassid.
// Release Version : 1.2
// Creation Date: 12/09/2013
// Copyright 2013, shlomo hassid.
/******************************************************************************/
/******************************************************************************/

/*****************************      Changelog       ***************************/
// 1.1: initial
// 1.2:
//      -> added a remove button in the debugger console.
//      -> Improved styling by stretching left:0, right:0 of the console.
// 1.3
//      -> Improved add_trace to support several argument registration. 
//      -> Added multiple vars registration reg_vars(["name" => $var1, "name" => $var])
//      -> fixed bug with multiple var registration assignment in add_trace.
//      -> fixed bug/Error in reg_var -> changed var to vars.
/******************************************************************************/

namespace Siktec\Bsik;

class Trace {

    public static $enable = true;
    public static $trace_arr = array();
    public static $script_start;
    public static $queries_used = array();
    public static $registered_vars = array();
    public static $peak_mem = array("r"=>0,"e"=>0);
    public static $exec_q = 0;
    public static function add_trace($op, $class = 'unknown', ...$var_catch) {
        if (!self::$enable) { return; }
        $b_arr = array(
            "method"    => $class,
            "operation" => $op,
            "time"      => date("r"),
            "var_catch" => '',
            "mem_real"    => self::trace_conv_size(memory_get_usage(true)),
            "mem_emalloc" => self::trace_conv_size(memory_get_usage(false))
        );
        foreach($var_catch as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $var_name => $data) {
                    $b_arr["var_catch"] .= $var_name
                            ."&nbsp;=>&nbsp;"
                            .self::dump($data,1)
                            ."&nbsp;<br />";
                }
            } elseif ($arg !== "-") {
                $b_arr["var_catch"] .= self::dump($arg);
            }
            $b_arr["var_catch"] .= "---------------<br />";
        }
        self::$trace_arr[] = $b_arr;
    }
    public static function add_query_trace($query = '', $time = 0, $info = '') {
        if (!self::$enable) { return; }
        self::$exec_q++;
        self::$queries_used[] = array(
            'query'     => $query, 
            'time'      => $time,
            'time_str'  => number_format($time, 5)." sec",
            'info'      => $info
        );
    }
    public static function get_qindex(){
        return (self::$exec_q + 1);
    }
    public static function add_step($file, $step) {
        if (!self::$enable) { return; }
        self::$trace_arr[] = basename($file).' => '.$step;
    }
    public static function reg_var($name, $var) {
        if (!self::$enable || !is_string($name)) { return; }
        self::$registered_vars[] = array($name,self::dump($var));
    }
    public static function reg_vars($vars) {
        if (!self::$enable) { return; }
        if (is_array($vars)) {
            foreach ($vars as $name => $var) {
                self::$registered_vars[] = array($name, self::dump($var));
            }
        } else  {
            self::$registered_vars[] = array("unknown", self::dump($vars));
        }
    }
    public static function expose_trace() {
        if (!self::$enable) { return; }
        //Get peaks before:
        self::$peak_mem['r'] = memory_get_peak_usage(true);
        self::$peak_mem['e'] = memory_get_peak_usage(false);
        $styles = self::get_expose_styles();
        //Building blocks:
        $buildHtml = array();
        //Main Wrapper:
        $buildHtml[] = "<style>.iid_debugger_view * { direction:ltr; }</style><div class='iid_debugger_view' ".$styles['wrapper'].">";
        //Header:
        $buildHtml[] = self::get_expose_header('DEBUGGER CONSOLE');
        //Content wrapper:
        $buildHtml[] = "<div ".$styles['toggle']." class='iid_debugger_content_wrapper'><div class='iid_debugger_content' ".$styles['content'].">";
        //General:
        $buildHtml[] = self::get_expose_cat_header("GENERAL");  
        $buildHtml[] = self::get_expose_general();
        //Registered variables:
        $buildHtml[] = self::get_expose_cat_header("REGISTERED VARIABLES");  
        $buildHtml[] = self::get_expose_regs();
        //Queries:
        $buildHtml[] = self::get_expose_cat_header("QUERIES: collected database events");  
        $buildHtml[] = self::get_expose_queries();
        //Events:
        $buildHtml[] = self::get_expose_cat_header("EVENTS: steps & trace");  
        $buildHtml[] = self::get_expose_events();
        //Close wrappers:
        $buildHtml[] = "</div></div></div>";
        $buildHtml[] = self::get_expose_script();
        echo implode("", $buildHtml);
    }
    private static function get_expose_script() {
        $buildHtml = '<script>"use strict"; var getHeight=function(a){var b=window.getComputedStyle(a),c=b.display,e=b.position,f=b.visibility,b=b.maxHeight.replace("px","").replace("%",""),d=0;if("none"!==c&&"0"!==b)return a.offsetHeight;a.style.position="absolute";a.style.visibility="hidden";a.style.display="block";d=a.offsetHeight;a.style.display=c;a.style.position=e;a.style.visibility=f;return d},toggleSlide=function(a){var b=0;a.getAttribute("data-max-height")?"0"===a.style.maxHeight.replace("px","").replace("%","")?a.style.maxHeight=a.getAttribute("data-max-height"):a.style.maxHeight="0":(b=getHeight(a)+"px",a.style.transition="max-height 0.5s ease-in-out",a.style.overflowY="hidden",a.style.maxHeight="0",a.setAttribute("data-max-height",b),a.style.display="block",setTimeout(function(){a.style.maxHeight=b},10))};';
        $buildHtml .= " document.getElementById('iid_debugger_release').addEventListener('click', function(e) { event.stopPropagation(); document.querySelector('.iid_debugger_view').remove(); }, false);";
        $buildHtml .= " document.querySelector('.iid_debugger_header').addEventListener('click', function(e) { toggleSlide(document.querySelector('.iid_debugger_content_wrapper')); }, false);</script>";
        return $buildHtml;
    }
    private static function get_expose_header($header = '') {
        $styles = self::get_expose_styles();
        return "<div class='iid_debugger_header'".
               $styles['header'].
               "><strong>".$header."</strong><span id='iid_debugger_release' ".$styles['remover'].">X</span></div>";
    }
    private static function get_expose_general() {
        $buildHtml      = array();
        $styles         = self::get_expose_styles();
        $header_cols    = self::get_expose_cols();  
        $exec_time_queries = 0;
        foreach(self::$queries_used as $data) { 
            $exec_time_queries += $data['time']; 
        } 
        $tds = array(
            "trepo" => count(self::$trace_arr),
            "tq"    => count(self::$queries_used),
            "exet"  => (!is_null(self::$script_start))?
                       number_format(
                            (microtime(true) - self::$script_start),3
                       ).' sec':
                       'not_measured',
            "exeq"  => number_format($exec_time_queries,5),
            "pmemr" => self::trace_conv_size(self::$peak_mem['r']),
            "pmeme" => self::trace_conv_size(self::$peak_mem['e']),
            "load"  => (function_exists('sys_getloadavg'))?sys_getloadavg():false
        );
        $tds['load'] = (is_array($tds['load']) && count($tds['load']) === 3)?
                        "1 min ago: ".$tds['load'][0]."<br />"
                        ."5 min ago: ".$tds['load'][1]."<br />"
                        ."15 min ago: ".$tds['load'][2]."<br />":"Unknown";
        
        $buildHtml[] = "<table ".$styles['table_general']."><tr>";
        foreach ($header_cols["general"] as $data ) {
            $buildHtml[] = "<th ".$styles['th_general'].">".ucwords($data)."</th>";
        }
        $buildHtml[] = "</tr><tr>";
        foreach ($tds as $data) {
            $buildHtml[] = "<td ".$styles['td_general'].">".$data."</td>";
        }
        $buildHtml[] = "</tr></table><br />";
        return implode("", $buildHtml);
    }
    private static function get_expose_regs() {
        $buildHtml = array();
        $styles = self::get_expose_styles();
        $header_cols = self::get_expose_cols();          
        $buildHtml[] = "<table ".$styles['table_regs']."><tr>";
        foreach ($header_cols["regs"] as $data ) {
            $buildHtml[] = "<th ".$styles['th_regs'].">".ucwords($data)."</th>";
        } 
        $buildHtml[] = "</tr>";
        foreach (self::$registered_vars as $key => $data) {
            $buildHtml[] = "<tr>";
            $buildHtml[] = "<td ".$styles['td_regs'].">".($key+1)."</td>";
            $buildHtml[] = "<td ".$styles['td_regs_fixed'].">".$data[0]."</td>";
            $buildHtml[] = "<td ".$styles['td_regs']."><div ".
                            $styles['div_regs_fixed'].">".$data[1]."</div></td>";
            $buildHtml[] = "</tr>";
        }
        $buildHtml[] = "</table><br/>";
        return implode("", $buildHtml);
    }
    private static function get_expose_queries() {
        $buildHtml = array();
        $styles = self::get_expose_styles();
        $header_cols = self::get_expose_cols();  
        $tds = array("info","time_str","query");
        $buildHtml[] = "<table ".$styles['table_queries']."><tr>";
        foreach ($header_cols["queries"] as $data ) {
            $buildHtml[] = "<th ".$styles['th_queries'].">".ucwords($data)."</th>";
        } 
        $buildHtml[] = "</tr>";
        foreach (self::$queries_used as $key => $data) {
            $buildHtml[] = "<tr><td ".$styles['td_queries'].">Q:".($key+1)."</td>";
            foreach ($tds as $data2) {
                $buildHtml[] = "<td ".$styles['td_queries'].">".$data[$data2]."</td>";
            }
            $buildHtml[] = "</tr>";
        }
        $buildHtml[] = "</table><br/>";
        return implode("", $buildHtml);
    }
    private static function get_expose_events() {
        $open = false;
        $buildHtml = array();
        $styles = self::get_expose_styles();
        $header_cols = self::get_expose_cols();
        $tds = array("method","operation","var_catch","mem_real","mem_emalloc","time");
        foreach (self::$trace_arr as $data) {
        if (is_array($data)) {
            if (!$open) { 
                $buildHtml[] = "<table ".$styles['table_events']."><tr>";
                foreach ($header_cols["events"] as $data2 ) {
                    $buildHtml[] = "<th ".$styles['th_events'].">".ucwords($data2)."</th>";
                } 
                $buildHtml[] = "</tr>";                     
                $open = true;
            }
            $buildHtml[] = "<tr>";
            foreach ($tds as $data3) {
                $buildHtml[] = "<td ".$styles['td_events'].">".$data[$data3]."</td>";
            }
            $buildHtml[] = "</tr>";
        } else {
            if ($open) {
                $buildHtml[] = "</table>";
                $open = false;
            }
            $buildHtml[] = self::get_expose_step_header($data);
        } 
        }
        return implode("", $buildHtml);
    }
    private static function get_expose_styles() {
        return array(
            'wrapper'       => "style='color: black;background:#F2F2F2; border-top:1px solid #525252; margin:0; padding:0; display: block; z-index:1000; "
                              ."position:fixed;left:0;right:0;width:100%; bottom:0; font-size:14px; font-family:arial; box-shadow: 0px -5px 9px -3px rgb(0,0,0); direction:ltr;'",
            'remover'       => "style='padding:0px;color:white;float:left;margin-left:10px;cursor:pointer;'",
            'toggle'        => "style='padding:0; margin:0; display:none;'",
            'content'       => "style='max-height: 400px; overflow-y: scroll; overflow-x:auto; padding: 0; margin: 0; display: block; color: #050505;'",
            'header'        => "style='width:100%; padding-top:10px; padding-bottom:10px; font-size:14px; color:#BDBDBD; background:#2E2E2E; text-align:center; cursor:pointer;'",
            'table_general' => "style='width:90%; margin-left:50px; border-collapse: collapse;color: black; '",
            'th_general'    => "style='text-align:center; border:1px solid black; background-color:#D8F6CE; font-size:12px; padding:2px 5px; border-collapse: collapse;'",
            'td_general'    => "style='text-align:left; border:1px solid black; padding:2px 5px; font-size:11px; border-collapse: collapse;'",
            'table_regs'    => "style='width:90%; margin-left:50px; border-collapse: collapse;color: black; '",
            'th_regs'       => "style='text-align:center; border:1px solid black; background-color:#E6E0F8; font-size:12px; padding:2px 5px; border-collapse: collapse;'",
            'td_regs'       => "style='text-align:left; border:1px solid black; padding:2px 5px; font-size:11px; border-collapse: collapse;'",
            'td_regs_fixed' => "style='text-align:left; border:1px solid black; padding:2px 5px; font-size:11px; border-collapse: collapse; width:150px;'",
            'div_regs_fixed'=> "style='max-height:350px; width:100%; overflow:auto'",
            'table_queries' => "style='width:90%; margin-left:50px; border-collapse: collapse;color: black; '",
            'th_queries'    => "style='text-align:center; border:1px solid black; background-color:#F3E2A9; font-size:12px; padding:2px 5px; border-collapse: collapse;'",
            'td_queries'    => "style='text-align:left; border:1px solid black; padding:2px 5px; font-size:11px; border-collapse: collapse;'",            
            'table_events'  => "style='width:90%; margin-left:50px; border-collapse: collapse; word-break: break-all;color: black;'",
            'th_events'     => "style='min-width: 100px; text-align:center; border:1px solid black; background-color:orange; font-size:12px; padding:2px 5px; border-collapse: collapse;'",
            'td_events'     => "style='text-align:left; border:1px solid black; padding:2px 5px; font-size:11px; border-collapse: collapse;'",             
        );
    }
    private static function get_expose_cols() {
        return array(
            "general"   => array("total reported","total queries","exec_time",
                "queries time","mem peak real","mem peak emalloc","server load"),
            "regs"      => array("index","variable name","content"),
            "queries"   => array("index","query info", "exec time", "query"),
            "events"    => array("file::method","operation","caught vars",
                "mem real","mem emalloc","timestamp")
        );
    }
    private static function get_expose_cat_header($name = '',$ident = 4) {
        if (!is_integer($ident)) { $ident = 4; }
        return "<br />"
               .str_repeat('&nbsp;',$ident).
               "<strong><u>".$name.":</u></strong><br /><br />";
    }
    private static function get_expose_step_header($step = '',$ident = 10) {
        if (!is_integer($ident)) { $ident = 10; }
        return "<br />"
               .str_repeat('&nbsp;',$ident).
               "<strong><u>Step => ".$step."</u></strong><br /><br />";
    }
    public static function dump($data, $indent=0) {
      $retval = '';
      $prefix=str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;', $indent);
      if (is_numeric($data)) { $retval.= "Number: $data"; }
      elseif (is_string($data)) { $retval.= "String: '".htmlspecialchars($data)."'"; }
      elseif (is_null($data)) { $retval.= "NULL"; }
      elseif ($data===true) { $retval.= "TRUE"; }
      elseif ($data===false) { $retval.= "FALSE"; }
      elseif (is_array($data)) {
        $retval.= "Array (".count($data).')';
        $indent++;
        foreach($data AS $key => $value) {
          $retval.= "<br />".$prefix." [".$key."] = ";
          $retval.= self::dump($value, $indent);
        }
      } elseif (is_object($data)) {
        $retval.= "Object (".get_class($data).")";
        $indent++;
        foreach($data AS $key => $value) {
          $retval.= "<br />".$prefix." ".$key." -> ";
          $retval.= self::dump($value, $indent);
        }
      } else {
          $retval.="not supported";
      }
      return $retval;
    }
    public static function trace_conv_size($size,$unit="") {
        if( (!$unit && $size >= 1<<30) || $unit == "GB")
          return number_format($size/(1<<30),2)."GB";
        if( (!$unit && $size >= 1<<20) || $unit == "MB")
          return number_format($size/(1<<20),2)."MB";
        if( (!$unit && $size >= 1<<10) || $unit == "KB")
          return number_format($size/(1<<10),2)."KB";
        return number_format($size)." bytes";
    }
}

Trace::$script_start = microtime(true);