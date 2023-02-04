<?php

namespace Siktec\Bsik\Render\Templates;

use \Siktec\Bsik\CoreSettings;
use \Twig\Loader\FilesystemLoader;
use \Twig\Loader\ArrayLoader;
use \Twig\Loader\ChainLoader;
use \Twig\Environment;

class Template {

	public static $default_debug 	  = false;
	public static $default_autoreload = true;
	private static string $ext = 'tpl';

	private string $cache_path = "";

	private bool   $cache_enable = false;
	private bool   $debug 		 = false;
	private bool   $auto_reload  = true;

	public ChainLoader $loader;
	public Environment $env;

	public function __construct(
		string|null $cache 		= null,
		bool   $cache_enable 	= true,
		?bool   $debug 			= null,
		?bool   $auto_reload 	= null
	) {
		$cache = $cache ?? CoreSettings::$path["manage-cache"];
		$this->cache_enable = $cache_enable;
		$this->cache_path   = $cache;
		$this->debug 		= is_null($debug) ? self::$default_debug : $debug;
		$this->auto_reload  = is_null($auto_reload) ? self::$default_autoreload : $auto_reload;
		$this->set();
	}

	public function set() {
		$this->loader = new ChainLoader([]);
		$this->env = new Environment($this->loader, [
			'debug' 		=> $this->debug,
			'auto_reload' 	=> $this->auto_reload,
			'cache' 		=> !empty($this->cache_path) && $this->cache_enable ? $this->cache_path : false,
		]);
		$this->env->addGlobal("__DEBUG__", $this->debug);
		$this->addExtension(new \Twig\Extension\DebugExtension());
		$this->addExtension(new TemplatingExtension());
	}

	public function addExtension(\Twig\Extension\ExtensionInterface $ext) {
		if (!$this->env->hasExtension(\get_class($ext))) {
			$this->env->addExtension($ext);
		}
	}

	public function addTemplates(array $templates) {
		$to_load = [];
		foreach ($templates as $name => $template) {
			if (!str_ends_with($name, self::$ext))
				$to_load[$name.'.'.self::$ext] = $template;
			else 
				$to_load[$name] = $template;
		}
		$array_loader = new ArrayLoader($to_load);
		$this->loader->addLoader($array_loader);
	}

	public function addFolders($paths) {
		$to_load = [];
		foreach ($paths as $path) {
			if (file_exists($path))
				$to_load[] = $path;
		}
		$folder_loader = new FilesystemLoader($to_load);
		$this->loader->addLoader($folder_loader);
	}
	
	public function render(string $name, array $context = []) : string {
		$template = $this->env->load($name.'.'.self::$ext);
		return $template->render($context);
	}
}
