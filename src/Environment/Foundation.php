<?php

namespace Crafteus\Environment;

use Crafteus\Exceptions\InvalidEcosystemException;
use Crafteus\Exceptions\TemplateNotRecognizedException;

class Foundation
{
	/**
	 * Instance of the main application.
	 *
	 * @var App
	 */
	public App $app;

	/**
	 * Name of the foundation.
	 *
	 * @var string|int
	 */
	public string|int $name;

	/**
	 * Instance of the current ecosystem.
	 *
	 * @var Ecosystem|null
	 */
	public Ecosystem|null $ecosystem_instance = null;

	/**
	 * Name of the class representing the ecosystem.
	 *
	 * @var string
	 */
	public string $ecosystem;

	/**
	 * Configuration of templates associated with the ecosystem.
	 *
	 * @var array
	 */
	public array $templates_config;

	/**
	 * Data used by the foundation.
	 *
	 * @var array
	 */
	public array $data;

	/**
	 * Foundation class constructor.
	 * Initializes the properties and cleans the ecosystem.
	 *
	 * @param App $app Instance of the application.
	 * @param string $name Name of the foundation.
	 * @param string $ecosystem Name of the ecosystem class.
	 * @param array $data Data associated with the foundation.
	 * @param array $templates_config Configuration of the ecosystem templates (optional).
	 * 
	 */
	public function __construct(App $app, string|int $name, string $ecosystem, array $data, array $templates_config = []) {
		$this->app = $app;
		$this->name = $name;
		$this->data = $data;
		$this->ecosystem = $ecosystem;
		$this->templates_config = $templates_config;

		$this->cleanEcosystem();
	}

	/**
	 * Checks the integrity of the ecosystem data.
	 *
	 * @return void
	 * 
	 */
	public function cleanEcosystem() : void {
		$this->cleanTemplateEcosystem();
	}

	/**
	 * Verifies whether the ecosystem class exists and is a subclass of Ecosystem.
	 *
	 * @throws InvalidEcosystemException If the ecosystem is invalid (code 3200).
	 * @return void
	 * 
	 */
	public function cleanClassEcosystem() : void {
		if(!(
			class_exists($this->ecosystem)
			&&
			is_subclass_of($this->ecosystem, Ecosystem::class)
		)) throw new InvalidEcosystemException($this->ecosystem, code : 2201);
	}

	/**
	 * Validates and initializes the ecosystem templates.
	 *
	 * @throws TemplateNotRecognizedException If a template is unknown in the ecosystem (code 4200).
	 * @return void
	 * 
	 */
	public function cleanTemplateEcosystem() : void {

		$ecosystem_instance = $this->getEcosystemInstance();

		foreach (array_keys($ecosystem_instance->template()) as $template_name) {

			$template_instance = $ecosystem_instance->getTemplateInstance(
				$template_name,
				$this->templates_config[$template_name] ?? []
			);

			if($template_instance === false)
				throw new TemplateNotRecognizedException(
					$template_name,
					get_class($ecosystem_instance),
					code : 4200
				);
			
			if($ecosystem_instance->initStubContent())
					$template_instance->initStub();

		}

	}

	/**
	 * Retrieves a unique instance of the current ecosystem.
	 * If the instance does not exist, it is created and associated with the foundation.
	 *
	 * @param mixed ...$args Arguments passed to the ecosystem constructor.
	 * 
	 * @return Ecosystem Instance of the ecosystem.
	 * 
	 */
	public function getEcosystemInstance(...$args) : Ecosystem {
		$this->cleanClassEcosystem();

		$c = $this->ecosystem;

		if(is_null($this->ecosystem_instance))
			$this->ecosystem_instance = (new $c(...$args))->setFoundation($this);

		return $this->ecosystem_instance;

		// return $this->ecosystem_instance ?? ($this->ecosystem_instance = new $c(...$args));

	}

	/**
	 * Generates templates from the ecosystem and returns the results.
	 *
	 * @return array List of generated templates.
	 * 
	 */
	public function generate() : array {

		return $this->getEcosystemInstance()->generateTemplates();

	}

	/**
	 * Cancels the generation of templates for this ecosystem.
	 *
	 * @return void
	 * 
	 */
	public function cancelGenerated() : void {

		$this->getEcosystemInstance()->cancelTemplatesGenerated();

	}

	/**
	 * Retrieves the foundation's name.
	 *
	 * @return string Foundation name.
	 * 
	 */
	public function getName() : string {

		return $this->name;

	}

	/**
	 * Retrieves the data associated with the foundation.
	 *
	 * @return array Foundation data.
	 * 
	 */
	public function getData() : array {

		return $this->data;

	}

}