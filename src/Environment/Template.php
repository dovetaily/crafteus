<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Traits\TemplateRule;
use Crafteus\Environment\Traits\TemplateStub;
use Crafteus\Support\Helper;

class Template
{
	use TemplateRule, TemplateStub;

	private ?string $template_name;
	private ?Ecosystem $ecosystem;
	private ?array $current_data = null;

	/**
	 * Chemin du fichier d'où elle sera généré
	 *
	 * @var string
	 */
	public string|array $path;

	/**
	 * Extension du fichier généré
	 *
	 * @var string
	 */
	public string|array $extension = '';

	/**
	 * Le modèle du fichier stub
	 *
	 * @var string|array
	 */
	public string|array $stub_file;

	/**
	 * Généré le fichier ou pas
	 *
	 * @var array|bool
	 */
	public bool|array $generate;

	/**
	 * Class of Templating
	 *
	 * @var string|array
	 */
	public string|array|\Closure $templating = [];

	// protected function normalizeBaseConfig() : void {
	// 	foreach ([
	// 		'path' => 'array',
	// 		'stub_file' => 'array',
	// 		'generate' => 'array',
	// 		'templating' => 'array',
	// 	] as $key => $type) {
	// 		if(property_exists($this, $key))
	// 			if($type == 'array' && !is_array($this->{$key})) 
	// 				$this->{$key} = [$this->{$key}];
	// 	}
	// }

	private function getBaseName(string|int|null $key = null, bool $last = false) : array|string {
		$name = [$this->getAppName()];

		if(method_exists($this, $m = 'getFileName')){
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
	public function getAppName() : string {
		return $this->getEcosystem()->getFoundation()->getName();
	}

	public function getData() : array {
		if(is_null($this->current_data))
			$this->initData();
		$compliant = Helper::compliantArray(
			rules : $this->getDataRule(),
			data : $this->current_data
		);
		if($compliant->check()->errorExists()){
			$m = 'Les données ne sont pas conforme !';
			Helper::console_err($m . "\n\n". print_r($compliant->getErrors(), true));
			throw new \Exception($m, 1);
		}
		// Helper::dump($this->current_data, $compliant->check()->errorExists());
		return $this->current_data;
	}

	protected function initData() : void {
		if(is_null($this->current_data)){
			$data = [...$this->getEcosystem()->getFoundation()->getData()];
			if(isset($data['__template'])){
				$template_data = [];
				if(is_array($data['__template']) && isset($data['__template'][$this->getTemplateName()])){
					$template_data =  $data['__template'][$this->getTemplateName()];
				}
				// else throw new Exception("Error Processing Request", 1);
				unset($data['__template']);
				$data = false 
					? array_merge_recursive($data, $template_data)
					: array_merge($data, $template_data)
				;
			}
			$this->current_data = $data;
		}
	}
	
	protected function getEcosystem() : ?Ecosystem {
		return $this->ecosystem;
	}
	public function setEcosystem(Ecosystem $ecosystem) : void {
		$this->ecosystem = $ecosystem;
	}

	public function getTemplateName() : string {
		return $this->template_name;
	}
	public function setTemplateName(string $template_name) : void {
		$this->template_name = $template_name;
	}

	protected function getPath() : array {
		return is_string($this->path) ? [$this->path] : $this->path;
	}

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

	protected function getGenerate(string|int|null $key = null, bool $last = false) : array|string {
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
