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
}
