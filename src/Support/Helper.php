<?php
namespace Crafteus\Support;

use Crafteus\Crafteus;
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

	/**
	 * Normalize path.
	 *
	 * @param string $path
	 * 
	 * @return string
	 * 
	 */
	public static function normalizePath(string $path) : string {
		return self::checkOS('windows')
			? str_replace('/', '\\', $path)
			: str_replace('\\', '/', $path)
		;
	}
	
	/**
	 * Get relative path.
	 *
	 * @param string $path
	 * 
	 * @return string
	 * 
	 */
	public static function relativePath(string $path) : string {

		$path = self::normalizePath($path);

		$dir = Crafteus::$relative_path_with == 'vendor'
			? dirname(__DIR__, 5)
			: (Crafteus::$relative_path_with == 'getcwd'
				? getcwd()
				: Crafteus::$relative_path_with
			)
		;

		return preg_replace(
			'/' . str_replace('/', '\\/', preg_quote($dir)) . '[\/\\\](.*)$/',
			'$1', 
			$path
		);

	}

	/**
	 * Check if the bone corresponds to that of the system.
	 *
	 * @param string $os
	 * 
	 * @return bool
	 * 
	 */
	public static function checkOS(string $os) : bool {
		return stripos(strtolower(PHP_OS_FAMILY), strtolower($os)) !== false;
	}

	/**
	 * Filters and merges values from an associative array based on a key pattern.
	 *
	 * This function scans the given `$data` array and collects values from keys that  
	 * contain both $separator(`,`) and the specified `$base_key`. The collected values are merged  
	 * into a single array.
	 *
	 * @param string $base_key The base key to search for within array keys.
	 * @param array $data The associative array containing key-value pairs.
	 * @param string $separator
	 * @return array The merged array of values from matching keys.
	 */
	public static function filterAndMergeByKey(string $base_key, array $data, string $separator = ',') : array{
		$result = [];
		foreach (array_keys($data) as $key) {
			if(str_contains($key, $separator) && str_contains($key, $base_key))
				$result = array_merge($result, $data[$key]);
		}
		return $result;
	}

	/**
	 * Parses a formatted string into an associative array with optional key and value conversions.
	 *
	 * This function converts a string like:
	 * 'only|required:true|unique:true|mess:"he\|l\:lo"|sec:\'l|o:wa\'|max:255'
	 * into an associative array:
	 * [
	 *     'only' => true,
	 *     'required' => true,
	 *     'unique' => true,
	 *     'mess' => 'he|l:lo',
	 *     'sec' => 'l|o:wa',
	 *     'max' => 255
	 * ]
	 *
	 * Optionally, it can convert both keys and values using custom callables provided as `$convert_key` and `$convert_value`.
	 *
	 * @param string $str The formatted string containing key-value pairs separated by `|` and `:`.
	 * @param mixed $default The default value to assign to keys with no value (e.g., `true`, `null`).
	 * @param array $defaults_for_keys An associative array of default values for specific keys.
	 * @param callable|bool $convert_value A callable for custom value conversion or `true` to automatically convert values (e.g., `true`, `false`, numbers). Defaults to `false` (no conversion).
	 * @param callable|null $change_key A callable for custom key (optional).
	 * @param array $convert_keys_value
	 *
	 * @return array An associative array with the extracted data from the string.
	 */
	public static function parseCustomString($str, $default = null, array $defaults_for_keys = [], callable|bool $convert_value = false, callable|null $change_key = null, array $convert_keys_value = []) {
		// Pattern to match the keys and values
		$pattern = '/(\w+)(?:\s*:\s*(?:(?:"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\')|([^|]+)))?/';
		preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);

		$result = [];
		foreach ($matches as $match) {
			$key = $match[1];

			if (!is_null($change_key)) {
				$key = $change_key($key);
			}

			$value = $defaults_for_keys[$key] ?? $default;

			if (isset($match[2]) && $match[2] !== '') { // Double quotes
				$value = stripcslashes($match[2]);
			} elseif (isset($match[3]) && $match[3] !== '') { // Single quotes
				$value = stripcslashes($match[3]);
			} elseif (isset($match[4]) && $match[4] !== '') { // Unquoted value
				$auto_convert = $match[4] === "true" ? true : ($match[4] === "false" ? false : (is_numeric($match[4]) ? (int)$match[4] : $match[4]));
				$value = $convert_value === true || in_array($key, $convert_keys_value)
					? $auto_convert
					: (is_callable($convert_value)
						? $convert_value(...[$auto_convert, $match[4], $key, $result])
						: $match[4]
					);
			}

			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * Ensures that unset keys in the array are filled with a default value (or null).
	 * 
	 * This function replaces elements in an array where the key is a numeric index (and the value is not numeric)
	 * by associating them with a default value. If the `ignore` parameter is set to `true`, numeric keys are ignored.
	 *
	 * @param array $tab The input array to process.
	 * @param bool $ignore If `true`, numeric keys will be ignored and not processed.
	 * @param mixed $default The default value to use for unset keys. The default is `null`.
	 * @param callable|null $ensure_value
	 * 
	 * @return array The transformed array with default values for unset keys or numeric indices.
	 */
	public static function ensureNullForUnsetKeys(array $tab, bool $ignore = false, $default = null, callable|null $ensure_value = null) : array {
		$result = [];
		foreach ($tab as $key => $value){
			$k = null;
			if(is_int($key) && is_string($value) && !is_numeric($value)){
				$result[$value] = is_callable($default) ? $default(...[$tab, $key, $value]) : $default;
				$k = $value;
			}
			elseif(!is_numeric($key) || (is_int($key) && !$ignore)){
				$result[$key] = $value;
				$k = $key;
			}

			if(!is_null($k) && !is_null($ensure_value)){
				$result[$k] = $ensure_value(...[$tab, $k, $result[$k], $key, $value]);
			}
		}
		return $result;
	}

	public static function base_path(string $path) : string {

		$dir = Crafteus::$relative_path_with == 'vendor'
			? dirname(__DIR__, 5)
			: (Crafteus::$relative_path_with == 'getcwd'
				? getcwd()
				: Crafteus::$relative_path_with
			)
		;

		return self::normalizePath($dir . '/' . $path);

	}

	/**
	 * Retrieves a value from a multidimensional array using a key path.
	 *
	 * @param string $key The key path, using a separator to navigate nested levels.
	 * @param array $data The array to search within.
	 * @param mixed $default_value The default value to return if the key is not found.
	 * @param string $separator The character used to separate key levels (default is '.').
	 * @param bool $result_only
	 *
	 * @return mixed Returns an associative array (if $result_only is false) with:
	 *               - 'result': The found value or the default value.
	 *               - 'found': A boolean indicating whether the key was found.
	 */
	public static function getNestedArrayValue(string $key, array $data, $default_value = null, string $separator = '.', bool $result_only = false) {
		$result = $default_value;
		$found = false;
		if(!empty($data) && (!empty($key) || is_numeric($key))){
			if(array_key_exists($key, $data)){
				$result = $data[$key];
				$found = true;
			}
			elseif(str_contains($key, $separator)){
				preg_match('/^([^' . preg_quote($separator) . ']+)' . preg_quote($separator) . '(.*)$/', $key, $matches);
				if(!is_null($kk = $matches[1] ?? null)){
					if(array_key_exists($kk, $data) && is_array($data[$kk])){
						$ret = self::getNestedArrayValue($matches[2], $data[$kk], $default_value, $separator);
						['result' => $result, 'found' => $found] = $ret;
					}
				}
			}
		}
		return $result_only ? $result : ['result' => $result, 'found' => $found];
	}

}
