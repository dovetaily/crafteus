<?php

namespace Crafteus\Environment\Support;

use Crafteus\Environment\Template;

class AnonymousTemplate extends Template
{

	/**
	 * Closure to dynamically determine the file name.
	 *
	 * @var \Closure|null
	 */
	private \Closure|null $get_file_name = null;

	/**
	 * Constructs an AnonymousTemplate instance.
	 * 
	 * This constructor allows dynamic property assignment based on the provided arguments.
	 * Note: The use of dynamic properties is deprecated in PHP 8.2+.
	 *
	 * @param mixed ...$args Key-value pairs to dynamically assign properties.
	 * 
	 */
	public function __construct(...$args) {

		error_reporting(E_ALL & ~E_DEPRECATED);

		foreach ($args as $key => $value) {

			if(preg_match('/^[a-z_][a-z0-9_]+$/i', $key))
				$this->{$key} = $value; // PHP Deprecated:  Creation of dynamic property

		}

		error_reporting(E_ALL);

	}
	
	/**
	 * Retrieves the file name for the template.
	 * 
	 * If no closure is set, it returns an array containing the foundation name.
	 * Otherwise, it executes the closure to determine the file name dynamically.
	 *
	 * @param string|int|null|null $key_path An optional key used in file name generation.
	 * 
	 * @return array|string The determined file(s) name.
	 * 
	 */
	public function getFileName(string|int|null $key_path = null) : array|string {

		$foundation_name = $this->getFoundationName();

		return is_null($this->get_file_name) 
			? [$foundation_name]
			: ($this->get_file_name)($key_path, $this)
		;

	}

	/**
	 * Sets the closure used to determine the file name dynamically.
	 *
	 * @param \Closure|null $closure A closure that defines the logic for generating file names.
	 * 
	 * @return void
	 * 
	 */
	public function setGetFileName(\Closure|null $closure) : void {

		$this->get_file_name = $closure;

	}

}
