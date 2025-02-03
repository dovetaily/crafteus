<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Traits\TemplateRule;
use Crafteus\Environment\Traits\TemplateStub;
use Crafteus\Exceptions\InvalidTemplateDataException;
use Crafteus\Support\Helper;

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
	private ?array $current_data = null;

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
	 * @throws InvalidTemplateDataException If the data does not comply with the rules.
	 * @return array Validated template data.
	 * 
	 */
	public function getData() : array {

		if(is_null($this->current_data))
			$this->initData();

		$compliant = Helper::compliantArray(
			rules : $this->getDataRule(),
			data : $this->current_data
		);

		if($compliant->check()->errorExists())
			throw new InvalidTemplateDataException(code : 4402);

		return $this->current_data;

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

			$data = [...$this->getEcosystem()->getFoundation()->getData()];

			if(isset($data['__template'])){

				$template_data = [];

				if(is_array($data['__template']) && isset($data['__template'][$this->getTemplateName()])){
					$template_data =  $data['__template'][$this->getTemplateName()];
				}

				unset($data['__template']);

				$data = false 
					? array_merge_recursive($data, $template_data)
					: array_merge($data, $template_data)
				;

			}

			$this->current_data = $data;

		}

	}
	
	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return Ecosystem|null Ecosystem instance.
	 * 
	 */
	protected function getEcosystem() : ?Ecosystem {
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
	public function setEcosystem(Ecosystem $ecosystem) : Template {

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
	public function setTemplateName(string $template_name) : Template {

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
		$extension = !is_array($this->extension) ? [$this->extension] : $this->extension;
		return !is_null($key)
			? (array_key_exists($key, $extension)
				? $extension[$key]
				: ($last
					? end($extension)
					: current($extension)
				)
			)
			: $extension
		;
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
		$stub_file = !is_array($this->stub_file) ? [$this->stub_file] : $this->stub_file;
		return !is_null($key)
			? (array_key_exists($key, $stub_file)
				? $stub_file[$key]
				: ($last
					? end($stub_file)
					: current($stub_file)
				)
			)
			: $stub_file
		;
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
		$generate = !is_array($this->generate) ? [$this->generate] : $this->generate;
		return !is_null($key)
			? (array_key_exists($key, $generate)
				? $generate[$key]
				: ($last
					? end($generate)
					: current($generate)
				)
			)
			: $generate
		;
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
		$templating = !is_array($this->templating) ? [$this->templating] : $this->templating;
		return !is_null($key)
			? (array_key_exists($key, $templating)
				? $templating[$key]
				: ($last
					? end($templating)
					: current($templating)
				)
			)
			: $templating
		;
	}


}
