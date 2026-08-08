<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Traits\TemplateRule;
use Crafteus\Environment\Traits\TemplateStub;
use Crafteus\Exceptions\InvalidTemplateDataException;
use Crafteus\Support\Helper;


/**
 * @method string|array transformBaseName(string|int $key_path) Retrieves the file name for the template.
 * @method void afterConfigUpdated(array $origin_config) Executes after the initial configuration is updated, which initializes the template.
 */
class Template
{
	use TemplateRule, TemplateStub;

	/**
	 * Name of the template.
	 *
	 * @var string|null
	 */
	private ?string $template_name;

	/**
	 * Ecosystem instance associated with this template.
	 *
	 * @var Ecosystem|null
	 */
	private ?Ecosystem $ecosystem;

	/**
	 * Current template data.
	 *
	 * @var array|null
	 */
	protected ?array $current_data = null;

	/**
	 * Path where the file will be generated.
	 *
	 * @var string|array
	 */
	public string|array $path;

	/**
	 * Extension of the generated file.
	 *
	 * @var string|array
	 */
	public string|array $extension = '';

	/**
	 * Stub file model for the template.
	 *
	 * @var string|array
	 */
	public string|array $stub_file;

	/**
	 * Determines whether the file should be generated.
	 *
	 * @var array|bool
	 */
	public array|bool $generate;

	/**
	 * Templating class or function.
	 *
	 * @var string|array
	 */
	public string|array|\Closure $templating = [];

	/**
	 * List of default public properties that should return a single value for the Stub 
	 * if their values are arrays, based on the Stub's key ID.
	 *
	 * @var array
	 */
	public const UNIQUE_STUB_CONFIG_PROPERTIES = ['path', 'extension', 'stub_file', 'generate', 'templating'];

	/**
	 * List of public properties that should return a single value for the Stub
	 * if their values are arrays, based on the Stub's key ID.
	 *
	 * @var array
	 */
	protected array $unique_stub_config_properties = [];

	/**
	 * Retrieves the base name of the template file.
	 *
	 * @param string|int|null $key Optional key to retrieve a specific name.
	 * @param bool $last Whether to return the last element if the key is not found.
	 * 
	 * @return array|string The base name(s) of the template.
	 * 
	 */
	private function getBaseName(string|int|null $key = null, bool $last = false) : array|string {
		$name = [$this->getFoundationName()];

		if(method_exists($this, $m = 'transformBasename')){
			$response = $this->$m($key);
			if(is_array($response) || is_string($response))
				$name = is_string($response) ? [$response] : $response;
		}

		return !is_null($key)
			? (array_key_exists($key, $name)
				? $name[$key]
				: ($last
					? end($name)
					: current($name)
				)
			)
			: $name
		;
	}

	/**
	 * Retrieves the foundation name from the ecosystem's foundation.
	 *
	 * @return string Foundation name.
	 * 
	 */
	public function getFoundationName() : string {
		return $this->getEcosystem()->getFoundation()->getName();
	}

	/**
	 * Retrieves the template's data.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param bool $checkCompliantData
	 * @param bool $forceInitData
	 * 
	 * @throws InvalidTemplateDataException If the data does not comply with the rules.
	 * @return mixed Validated template data.
	 * 
	 */
	public function getData(string|null $key = null, $default_value = null, bool $checkCompliantData = true, bool $forceInitData = false) {

		$this->initData($forceInitData);

		if($checkCompliantData) $this->compliantData();

		return is_null($key) ? $this->current_data : Helper::getNestedArrayValue($key, $this->current_data, $default_value)['result'];

	}

	/**
	 * Check the compliance of the template data.
	 *
	 * @throws InvalidTemplateDataException If the data does not comply with the rules.
	 * @return void
	 * 
	 */
	public function compliantData() : void {

		$compliant = Helper::compliantArray(
			rules : $this->getDataRule(),
			data : $this->current_data
		);

		if($compliant->check()->errorExists())
			throw new InvalidTemplateDataException(validation_errors: $compliant->getErrors(), code : 4402);
		
	}

	/**
	 * Initializes the template's data from the ecosystem's foundation.
	 *
	 * @param bool $force Forces the initialization of the data if it is true.
	 *
	 * @return void
	 * 
	 */
	protected function initData(bool $force = false) : void {

		if(is_null($this->current_data) || $force){

			$data = [...$this->getEcosystem()->getData()];

			if(isset($data['__template'])){

				$template_data = [];

				if(is_array($data['__template'])){
					$template_data =  array_merge(
						$data['__template']['*'] ?? [],
						Helper::filterAndMergeByKey($this->getTemplateName(), $data['__template']),
						$data['__template'][$this->getTemplateName()] ?? []
					);
				}

				unset($data['__template']);

				$data = false 
					? array_merge_recursive($data, $template_data)
					: array_merge($data, $template_data)
				;

			}

			$this->setCurrentData($data);

		}

	}
	
	/**
	 * Set the template's data
	 *
	 * @param array $data
	 * 
	 * @return void
	 * 
	 */
	protected function setCurrentData(array $data) : void {

		$this->current_data = $data;

	}

	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return Ecosystem|null Ecosystem instance.
	 * 
	 */
	public function getEcosystem() : ?Ecosystem {
		return $this->ecosystem;
	}

	/**
	 * Sets the ecosystem instance for this template.
	 *
	 * @param Ecosystem $ecosystem Ecosystem instance.
	 * 
	 * @return self
	 * 
	 */
	public function setEcosystem(Ecosystem $ecosystem) : self {

		$this->ecosystem = $ecosystem;

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		if(!in_array($backtrace[1]['class'], [Ecosystem::class])){
			trigger_error("[W001] - The `setEcosystem` method must be used only in Crafteus classes. So be careful by calling it in your processes.", E_USER_WARNING);
		}

		return $this;

	}

	/**
	 * Get the template name.
	 *
	 * @return string Template name.
	 * 
	 */
	public function getTemplateName() : string {
		return $this->template_name;
	}

	/**
	 * Sets the template name.
	 *
	 * @param string $template_name Name of the template.
	 * 
	 * @return self
	 * 
	 */
	public function setTemplateName(string $template_name) : self {

		$this->template_name = $template_name;

		return $this;

	}

	/**
	 * Retrieves the file path(s) for the template.
	 *
	 * @return array List of paths.
	 * 
	 */
	protected function getPath() : array {
		return is_string($this->path) ? [$this->path] : $this->path;
	}

	/**
	 * Retrieves the file extension(s) for the template.
	 *
	 * @param string|int|null $key Optional key to retrieve a specific extension.
	 * @param bool $last Whether to return the last element if the key is not found.
	 * 
	 * @return array|string File extension(s).
	 * 
	 */
	protected function getExtension(string|int|null $key = null, bool $last = false) : array|string {
		return self::getUniqueConfig($this->extension, $key, $last);
	}

	/**
	 * Retrieves the stub file(s) for the template.
	 *
	 * @param string|int|null $key Optional key to retrieve a specific stub file.
	 * @param bool $last Whether to return the last element if the key is not found.
	 * 
	 * @return array|string Stub file(s).
	 * 
	 */
	protected function getStubFile(string|int|null $key = null, bool $last = false) : array|string {
		return self::getUniqueConfig($this->stub_file, $key, $last);
	}

	/**
	 * Retrieves the generate flag(s) for the template.
	 *
	 * @param string|int|null $key Optional key to retrieve a specific flag.
	 * @param bool $last Whether to return the last element if the key is not found.
	 * 
	 * @return array|bool Generate flag(s).
	 * 
	 */
	protected function getGenerate(string|int|null $key = null, bool $last = false) : array|bool {
		return self::getUniqueConfig($this->generate, $key, $last);
	}

	/**
	 * Retrieves the templating method(s) for the template.
	 *
	 * @param string|int|null $key Optional key to retrieve a specific method.
	 * @param bool $last Whether to return the last element if the key is not found.
	 * 
	 * @return array|string|\Closure Templating method(s).
	 * 
	 */
	protected function getTemplating(string|int|null $key = null, bool $last = false) : array|string|\Closure {
		return self::getUniqueConfig($this->templating, $key, $last);
	}

	/**
	 * Retrieves a unique configuration value from an array or a single value.
	 *
	 * If the provided data is not an array, it wraps it in an array. It then retrieves
	 * a specific element by its key, or returns the first or last element based on the `last` flag.
	 *
	 * @param mixed $data The data to retrieve the configuration from. Can be an array or a single value.
	 * @param string|int|null $key Optional key to retrieve a specific value from the array.
	 * @param bool $last Whether to return the last element if the key is not found. Defaults to false.
	 *
	 * @return mixed The requested configuration value, either from the key or first/last element.
	 */
	public static function getUniqueConfig($data, string|int|null $key = null, bool $last = false) : mixed {
		$result = !is_array($data) ? [$data] : $data;
		return !is_null($key)
			? (array_key_exists($key, $result)
				? $result[$key]
				: ($last
					? end($result)
					: current($result)
				)
			)
			: $result
		;
	}

	/**
	 * Retrieves the configuration of the Template from its public properties.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * 
	 * @return mixed The template's configuration.
	 * 
	 */
	public function getConfig(string|null $key = null, $default_value = null) {
		if(!function_exists('crafteus\\environment\\dug8e2e8h_template_substitute')){
			function dug8e2e8h_template_substitute($o){ return get_object_vars($o); }
		}

		$result = dug8e2e8h_template_substitute($this);

		return is_null($key) ? $result : Helper::getNestedArrayValue($key, $result, $default_value)['result'];
	}

	/**
	 * Retrieves public properties that should return a single value for the Stub.
	 *
	 * @return array
	 * 
	 */
	public function getUniqueStubConfigProperties() : array {
		return $this->unique_stub_config_properties;
	}

	/**
	 * Set public properties that should return a single value for the Stub.
	 *
	 * @param $data
	 * 
	 * @return void
	 * 
	 */
	public function setUniqueStubConfigProperties(array $data) : void {
		$this->unique_stub_config_properties = $data;
	}

}
