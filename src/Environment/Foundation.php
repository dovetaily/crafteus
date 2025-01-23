<?php

namespace Crafteus\Environment;

use Exception;
use Crafteus\Environment\Template;
use Crafteus\Support\Helper;

use Crafteus\Exceptions\InvalidEcosystemException;
use Crafteus\Exceptions\TemplateNotRecognizedException;

class Foundation
{
	public App $app;

	public string $name;

	public Ecosystem|null $ecosystem_instance = null;

	public string $ecosystem;

	public array $templates_config;

	public array $data;

	public function __construct(App $app, string $name, string $ecosystem, array $data, array $templates_config = []) {
		$this->app = $app;
		$this->name = $name;
		$this->data = $data;
		$this->ecosystem = $ecosystem;
		$this->templates_config = $templates_config;

		$this->cleanEcosystem();
	}

	public function cleanEcosystem() : void {
		$this->cleanTemplateEcosystem();
	}

	public function cleanClassEcosystem() : void {
		if(!(
			class_exists($this->ecosystem)
			&&
			is_subclass_of($this->ecosystem, Ecosystem::class)
		)) throw new InvalidEcosystemException($this->ecosystem, code : 3200);
	}

	public function cleanTemplateEcosystem() : void {

		$ecosystem_instance = $this->getEcosystemInstance();

		foreach ($ecosystem_instance->template() as $template_name => $value) {
			$template_instance = $ecosystem_instance->getTemplateInstance($template_name, isset($this->templates_config[$template_name]) ? $this->templates_config[$template_name] : []);
			if($template_instance){
				if($ecosystem_instance->initStubContent()){
					$template_instance->initStub();
				}
			}
			else throw new TemplateNotRecognizedException($template_name, get_class($ecosystem_instance), code : 4200);
			// else throw new Exception("Le template `" . $template_name . "` n'a pas été réconnu dans l'écosystem `" . get_class($ecosystem_instance) . "`", 1);
			
		}

	}

	public function getEcosystemInstance(...$args) : Ecosystem {
		$this->cleanClassEcosystem();
		$c = $this->ecosystem;
		if(is_null($this->ecosystem_instance)){
			$this->ecosystem_instance = new $c(...$args);
			$this->ecosystem_instance->setFoundation($this);
		}
		return $this->ecosystem_instance;
		// return $this->ecosystem_instance ?? ($this->ecosystem_instance = new $c(...$args));
	}

	public function getName() : string {
		return $this->name;
	}

	public function getData() : array {
		return $this->data;
	}

}