<?php

namespace Crafteus\Environment\Support;

use Crafteus\Environment\Template;

/**
 * @method void afterConfigUpdated(array $new_config, array $old_config, array $template) Executes after the initial configuration is updated, which initializes the template.
 */
class AnonymousTemplate extends Template
{

	/**
	 * Closure to dynamically determine the file name.
	 *
	 * @var \Closure|null
	 */
	private \Closure|null $transform_basename = null;

	public const UNAUTHORIZED_KEYS = [
		"UNAUTHORIZED_KEYS",
		"transform_basename",
		"__construct",
		"transformBasename",
		"setTransformBasename",

		// Template
		"template_name", // private
		"ecosystem",
		"getBaseName",
		"stubs",

		"current_data", // protected
		"unique_stub_config_properties",
		"config_rule",
		"data_rule",
		"initData",
		"setCurrentData",
		"getPath",
		"getExtension",
		"getStubFile",
		"getGenerate",
		"getTemplating",
		"generateStubContent",
		"applyStubContent",

		// "path", // public
		// "extension",
		// "stub_file",
		// "generate",
		// "templating",
		"UNIQUE_STUB_CONFIG_PROPERTIES",
		"getFoundationName",
		"getData",
		"compliantData",
		"getEcosystem",
		"setEcosystem",
		"getTemplateName",
		"setTemplateName",
		"getUniqueConfig",
		"getConfig",
		"getUniqueStubConfigProperties",
		"setUniqueStubConfigProperties",
		"getConfigRule",
		"getDataRule",
		"setConfigRule",
		"setDataRule",
		"getRules",
		"defaultRuleConfig",
		"initStub",
		"addStub",
		"getStubs",
		"getStub",
		"generateStubFile",
		"generateStubsFile",
		"cancelStubsFilesGenerated",
	];

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

			if(in_array($key, self::UNAUTHORIZED_KEYS)) throw new \Exception("Unauthorized `" . $key . "` key name.", 1);

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
	public function transformBasename(string|int|null $key_path = null) : array|string {

		$foundation_name = $this->getFoundationName();

		return is_null($this->transform_basename) 
			? [$foundation_name]
			: ($this->transform_basename)($key_path, $this)
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
	public function setTransformBasename(\Closure|null $closure) : void {

		$this->transform_basename = $closure;

	}

}
