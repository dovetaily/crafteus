<?php
namespace Crafteus;

use Crafteus\Environment\App;

class Crafteus
{

	/**
	 * The Crafteus library version.
	 *
	 * @var string
	 */
	public const CRAFTEUS_VERSION = '1.0.0';

	/**
	 * Used to deduce the relative path of the files.
	 *
	 * @var string
	 */
	public static string $relative_path_with = 'vendor'; // values : 'vendor', 'getcwd' or 'your base relative path'
	
	public static function __callStatic($method, $arguments)
	{
		$instance = new App();

		return $instance->$method(...$arguments);
		// if(method_exists($instance, $method))
		// 	return $instance->$method(...$arguments);
		// else throw new \Error("Call to undefined method Crafteus\Environment\App::" . $method . "()", 1);
		
	}
}
