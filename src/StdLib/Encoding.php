<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\StdLib;

/**********************************************************************************************************
* Encoding Methods:
**********************************************************************************************************/

class Encoding {

    const ICONV_TRANSLIT  = "TRANSLIT";
    const ICONV_IGNORE    = "IGNORE";
    const WITHOUT_ICONV   = "";

   /**
    * removeBOM
    * remove the BOM from a string
    * @param string $str="" - the string to remove the BOM from
    * 
    * @return string
    */
    final public static function removeBOM(string $str="") : string {
        if(substr($str, 0,3) === pack("CCC",0xef,0xbb,0xbf)) {
        $str = substr($str, 3);
        }
        return $str;
    }

    /**
     * strlen
     * get the length of a string supports mb_string w
     * @param string $text
     * 
     * @return int
     */
    final public static function strlen(string $text) : int {
        return (function_exists('mb_strlen') && ((int) ini_get('mbstring.func_overload')) & 2) ?
            mb_strlen($text,'8bit') : strlen($text);
    }

    /**
     * normalize_encoding_label
     * normalize the encoding label to a valid one e.g. UTF8 -> UTF-8
     * @param string $encoding_label - the encoding label to normalize
     * 
     * @return string
     */
    final public static function normalize_encoding_label(string $encoding_label) : string {
        $encoding = strtoupper($encoding_label);
        $encoding = preg_replace('/[^a-zA-Z0-9\s]/', '', $encoding);
        $equivalences = array(
            'ISO88591'      => 'ISO-8859-1',
            'ISO8859'       => 'ISO-8859-1',
            'ISO'           => 'ISO-8859-1',
            'LATIN1'        => 'ISO-8859-1',
            'LATIN'         => 'ISO-8859-1',
            'UTF8'          => 'UTF-8',
            'UTF'           => 'UTF-8',
            'WIN1252'       => 'ISO-8859-1',
            'WINDOWS1252'   => 'ISO-8859-1',
            "AUTO"          => 'AUTO'
        );

        return $equivalences[$encoding] ?? 'NONE';

    }

    /**
     * detect
     * detect the encoding of a string
     * depends on mb_string if its not available will return the fail value
     * 
     * @param string $text
     * @param string|array|null $from - the encoding to detect from if auto is used will try to detect
     * @param mixed|null $fail - the value to return if the encoding is not detected
     * 
     * @return mixed
     */
    final public static function detect(string $text, string|array|null $from = "auto", mixed $fail = null) : mixed {
        
        //if mb_string is available:
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($text, $from, true) ?: $fail;
        }
        return $fail;

    }

    /**
     * encode
     * encode a string to a specific encoding
     * will try to use mb_string if available if not will use iconv
     * 
     * @param string $text
     * @param string $to
     * @param string|array|null $from
     * 
     * @return string
     * @throws ValueError if the encoding is not supported or mb_string, iconv is not available
     */
    final public static function encode(string $text, string $to, string|array|null $from = 'AUTO') : string {
        // Normalize encoding labels:
        $from   = self::normalize_encoding_label($from);
        $to     = self::normalize_encoding_label($to);

        // Check if we need to encode:
        if ($to === 'NONE' || $from === 'NONE') {
            return $text;
        }

        // Encode using mb_convert_encoding if available:
        if (function_exists('mb_convert_encoding')) {
            //detect encoding:
            $from = $from === 'AUTO' ? self::detect(text : $text, fail : "UTF-8") : $from;
            return mb_convert_encoding($text, $to, $from);
        }

        // Encode using iconv if available:
        if (function_exists('iconv')) {
            // Auto detect encoding:
            if ($from === 'AUTO') {
                $from = mb_detect_encoding($text, "auto");
            }
            return iconv($from, $to."//".self::ICONV_TRANSLIT, $text);
        }

        // Raise error:
        throw new \ValueError("Encoding::encode() requires either mb_string or iconv to be available.");   
    }
}