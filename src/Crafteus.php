<?php
namespace Crafteus;

use Crafteus\Environment\App;

/**
 * @method static \Crafteus\Environment\App make(string $ecosystem, array $data, array $templates_config = []) Creates and registers multiple foundations.
 */
class Crafteus
{

	/**
	 * The Crafteus library version.
	 *
	 * @var string
	 */
	public const CRAFTEUS_VERSION = '1.0.0';

	/**
	 * Determines whether file writing and creation are disabled.
	 * 
	 * When set to `true`, the class prevents any file creation 
	 * or modification in the system.
	 * 
	 * @var bool $disable_file_writes
	 */
	public static bool $disable_file_writes = false;

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
