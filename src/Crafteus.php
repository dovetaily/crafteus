<?php

namespace Crafteus;

use Crafteus\Environment\App;

/**
 * @phpstan-import-type TFoundationOptions from \Crafteus\Environment\Foundation
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
	 * @var bool $disable_file_writing
	 */
	public static bool $disable_file_writing = false;
	/**
	 * Determines the base path used to resolve relative file paths.
	 *
	 * Accepted values:
	 * - 'vendor': Uses the vendor directory.
	 * - 'getcwd': Uses the current working directory.
	 * - string: Uses the specified project root path.
	 *
	 * @var 'vendor'|'getcwd'|string
	 */
	public static string $relative_path_with = 'getcwd';

	/**
	 * Creates and registers multiple foundations.
	 *
	 * @template KE of \Crafteus\Environment\Ecosystem
	 *
	 * @param class-string<KE> $ecosystem
	 * @param TFoundationOptions $foundation_options
	 * @param array $templates_config
	 *
	 * @return \Crafteus\Environment\App<KE>
	 *
	 */
	public static function make(
		string $ecosystem,
		array $foundation_options,
		array $templates_config = []
	): App {

		$instance = new App();

		return $instance->make($ecosystem, $foundation_options, $templates_config);
	}

	/**
	 * Disables file writing and creation.
	 *
	 * Sets the `$disable_file_writing` property to `true`, preventing
	 * any file creation or modification within the system.
	 *
	 * @return void
	 */
	public static function disableFileWriting(): void
	{
		self::$disable_file_writing = true;
	}

	/**
	 * Enables file writing and creation.
	 *
	 * Sets the `$disable_file_writing` property to `false`, allowing
	 * file creation and modification within the system.
	 *
	 * @return void
	 */
	public static function enableFileWriting(): void
	{
		self::$disable_file_writing = false;
	}
}
