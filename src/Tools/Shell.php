<?php
/******************************************************************************/
// Created by: Shlomi Hassid.
// Release Version : 1.0.1
// Creation Date: date
// Copyright 2020, Shlomi Hassid.
/******************************************************************************/

namespace Siktec\Bsik\Tools;

use Ahc\Cli\Helper\Shell as ShellBase;

class Shell extends ShellBase 
{

    /**
     * @param string $command Command to be executed
     * @param string $input   Input for stdin
     */
    public function __construct(protected string $command, protected ?string $input = null)
    {
        parent::__construct($command, $input);
    }
    
    /**
     * loadPartialEnv
     * sets a subset of the current environment variables
     * expects a string or an array of environment variable names.
     * 
     * @param  string|array  $vars environment variable names delimited by comma or an array of names
     * @return array - array of environment variable names that were set
     */
    public function loadPartialEnv(string|array $vars = '') : array
    {
        // if its a string, convert it to an array supporting multiple vars delimited by comma
        if (is_string($vars)) {
            $vars = explode(',', $vars);
        }
        
        // check if the vars are set in the environment
        $env = [];
        foreach ($vars as $var) {
            if (($valid = getenv($var)) !== false) {
                $env[$var] = $valid;
            }
        }

        // We avoid empty env vars because they will override the current environment to an empty array
        if (count($env) > 0) {
            $this->setOptions(env : $env);
        }
        
        return array_keys($env);

    }
    
    /**
     * removeFromEnv
     * removes a subset of the current environment variables
     * expects a string or an array of environment variable names.
     * 
     * @param  string|array $vars environment variable names delimited by comma or an array of names
     * @return array - array of environment variable names that were removed
     */
    public function removeFromEnv(string|array $vars = '') : array
    {
    
        // if its a string, convert it to an array supporting multiple vars delimited by comma
        if (is_string($vars)) {
            $vars = explode(',', $vars);
        }
        if (empty($vars)) {
            return [];
        }

        // we load the current environment if its not set - this is the default behavior
        if (is_null($this->env)) {
            $this->env = $_ENV;
        }

        $removed = [];
        foreach ($vars as $var) {
            if (array_key_exists($var, $this->env)) {
                unset($this->env[$var]);
                $removed[] = $var;
            }
        }

        return $removed;
    }
    
    /**
     * extendEnv
     * extends the current environment variables with the given ones
     * if the current environment is not (null) it will automatically merge the new env with the existing one from $_ENV
     * 
     * @param  array $env environment variables to be added - key value pairs
     * @return void 
     */
    public function extendEnv(array $env = []) : void
    {
        // if the env is null, we inherit the current environment which is the default behavior
        if (is_null($this->env)) { 
            $this->env = array_merge($_ENV, $env);

        // Allready set, we merge the new env with the existing one
        } else {
            $this->env = array_merge($this->env, $env);
        }
    }
    
}