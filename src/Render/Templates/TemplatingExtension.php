<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/
/******************************************************************************/
// inspiration from:
// https://github.com/jasny/twig-extensions
/******************************************************************************/

namespace Siktec\Bsik\Render\Templates;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TemplatingExtension extends AbstractExtension
{

    /**
     * Return extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'Bsik/Render/TemplatingExtension';
    }

    public function getFunctions(): array
    {

        // options  : [
        //     //'is_safe' => ['html'], //whether to skip escaping or not 
        //     //'needs_context'     => false, //Passes the template context (all the variable used)
        //     //'needs_environment' => true, //Passes the $env to the function
        //     //'is_variadic'       => false // https://stackoverflow.com/questions/50621564/does-twig-support-variadic-arguments-using-the-token
        // ]
        return [
            new TwigFunction(
                name     : 'render_as_attributes', 
                callable : [$this, 'render_array_as_attributes'], 
            ),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter(
                name     : 'array_values', 
                callable : [$this, 'return_array_values'], 
            ),
            new TwigFilter(
                name     : 'array_keys', 
                callable : [$this, 'return_array_keys'], 
            ),
            new TwigFilter(
                name     : 'array_filter_keys', 
                callable : [$this, 'filter_array_keys'], 
            ),
            new TwigFilter(
                name     : 'print_variable', 
                callable : [$this, 'return_print_variable'], 
            )
        ];
    }

    public function return_print_variable($input) 
    {
        $type = gettype($input);
        
        switch ($type) {
            case "string": 
            case "integer":
            case "double":
            case "float":
                return $input;
            case "array":
                return implode(', ', $input);
            case "boolean":
                return $input ? "TRUE" : "FALSE";
            default:
                return $type;
        }
    }
    /**
     * Return all the values of an array or object
     *
     * @param array|object $input
     * @return array
     */
    public function return_array_values($input)
    {
        return isset($input) ? array_values((array)$input) : null;
    }

    /**
     * Return all the values of an array or object
     *
     * @param array|object $input
     * @return array
     */
    public function return_array_keys($input)
    {
        return isset($input) ? array_keys((array)$input) : null;
    }

    /**
     * Return all the values of an array or object
     *
     * @param array|object $input
     * @param array $keys
     * @return array
     */
    public function filter_array_keys($input, ...$keys)
    {
        $arr = [];
        foreach ($input as $key => $value) {
            if (in_array($key, $keys))
                $arr[$key] = $value;
        }
        return $arr;
    }

    /**
     * Cast an array to an HTML attribute string
     *
     * @param mixed $array
     * @return string
     */
    public function render_array_as_attributes($array)
    {
        if (empty($array)) return null;
        $str = "";
        foreach ($array as $key => $value) {
            if (!isset($value) || $value === false)
                continue;
            if ($value === true) 
                $value = $key;
            $str .= ' ' . $key . '="' . addcslashes($value, '"') . '"';
        }
        return trim($str);
    }

}