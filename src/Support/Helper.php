<?php
namespace Crafteus\Support;

use Crafteus\Support\CompliantArray;

abstract class Helper
{
	
	/**
	 * [Description for compliantArray]
	 *
	 * @param mixed ...$args
	 * 
	 * @return \Crafteus\Support\CompliantArray
	 * 
	 */
	public static function compliantArray(...$args) : CompliantArray {

		return new CompliantArray(...$args);

	}

	public static function getPublicProperties(string $className): array
	{
		// Vérifie si la classe existe
		if (!class_exists($className)) {
			throw new \InvalidArgumentException("La classe '$className' n'existe pas.");
		}
	
		// Utilisation de la réflexion pour analyser la classe
		$reflection = new \ReflectionClass($className);
	
		// Récupère les propriétés publiques uniquement
		$properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
	
		// Retourne un tableau avec les noms des propriétés
		return array_map(fn($prop) => $prop->getName(), $properties);
	}

	/**
	 * Retourne la ligne de définition d'une méthode d'une classe
	 *
	 * @param string $className Nom de la classe
	 * @param string $methodName Nom de la méthode
	 * @return array|null Tableau contenant le fichier, la ligne de début et la ligne de fin, ou null si la méthode n'existe pas
	 */
	public static function getMethodLocation(string $className, string $methodName): ?array {
		try {
			$reflectMethod = new \ReflectionMethod($className, $methodName);

			return [
				'file' => $reflectMethod->getFileName(),
				'start_line' => $reflectMethod->getStartLine(),
				'end_line' => $reflectMethod->getEndLine()
			];
		} catch (\ReflectionException $e) {
			// Si la classe ou la méthode n'existe pas
			return null;
		}
	}
	/**
	 * Dump the given variables and show the file and line number where it was called.
	 *
	 * @param  mixed  ...$args
	 * @return void
	 */
	public static function dump(...$args)
	{
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		$file = $backtrace['file'] ?? 'unknown file';
		$line = $backtrace['line'] ?? 'unknown line';
		var_dump(...$args);
		echo("\033[90m... {$file} on line {$line}\033[0m\n\n");
	}
	/**
	 * Dump the given variables, show file and line, and end the script.
	 *
	 * @param  mixed  ...$args
	 * @return never
	 */
	public static function dd(...$args)
	{
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
		$file = $backtrace['file'] ?? 'unknown file';
		$line = $backtrace['line'] ?? 'unknown line';
		var_dump(...$args);
		echo("\033[90m... {$file} on line {$line}\033[0m\n\n");
		exit(1); // Arrête l'exécution
	}

	public static function redText(string $text, bool $disable_server_color = false) : string {
		return self::baseColorText(
			$text,
			"\033[31m",
			'red',
			disable_server_color: $disable_server_color
		);
	}
	public static function grayText(string $text, bool $disable_server_color = false) : string {
		return self::baseColorText(
			$text,
			"\033[90m",
			'red',
			disable_server_color: $disable_server_color
		);
	}

	public static function baseColorText(string $text, array|string $cli_color, string $cli_server_color, array $cli_target = ['cli'], array $cli_server_target = ['cli-server'], bool $disable_server_color = false) : string {

		$sapi = php_sapi_name();

		$cli_color = [
			'start' => is_string($cli_color) 
				? $cli_color
				: (isset($cli_color['start'])
					? $cli_color['start']
					: ''
				)
			,
			'end' => is_array($cli_color) && isset($cli_color['end']) && is_string($cli_color['end']) 
				? $cli_color['end'] 
				: "\033[0m"
		];

		return in_array($sapi, $cli_target)
			? $cli_color['start'] . $text . $cli_color['end']
			: (!$disable_server_color && in_array($sapi, $cli_server_target)
				? "<span style=\"color:" . $cli_server_color . ";\">" . $text . "</span>"
				: $text
			)
		;

	}

}
