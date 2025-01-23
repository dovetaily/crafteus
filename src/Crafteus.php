<?php
namespace Crafteus;

use Crafteus\Environment\App;

class Crafteus
{
	
	public static function __callStatic($method, $arguments)
	{
		$instance = new App();

		return $instance->$method(...$arguments);
		// if(method_exists($instance, $method))
		// 	return $instance->$method(...$arguments);
		// else throw new \Error("Call to undefined method Crafteus\Environment\App::" . $method . "()", 1);
		
	}
}
