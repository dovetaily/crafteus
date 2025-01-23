<?php

namespace Crafteus\Environment\Support;
use Crafteus\Environment\Template;

class AnonymousTemplate extends Template
{
	private \Closure|null $get_file_name = null;
	public function __construct(...$args) {
		// parent::__construct($path, $stub_file, $generate, $keyword_class, $keywords);
		error_reporting(E_ALL & ~E_DEPRECATED);
		foreach ($args as $key => $value) {
			if(preg_match('/^[a-z_][a-z0-9_]+$/i', $key))
				$this->{$key} = $value; // PHP Deprecated:  Creation of dynamic property
		}
		error_reporting(E_ALL);
	}
	
	public function getFileName(string|int|null $key_path = null) : array|string {
		$app_name = $this->getAppName();
		return is_null($this->get_file_name) ? [$app_name] : ($this->get_file_name)($key_path, $this);
	}

	public function setGetFileName(\Closure|null $closure) : void {
		$this->get_file_name = $closure;
	}
}
