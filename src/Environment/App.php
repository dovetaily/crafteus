<?php

namespace Crafteus\Environment;

use Crafteus\Exceptions\FoundationAlreadyExistsException;

class App
{

	/**
	 * @var string $base_dir Base directory path for the application.
	 */
	public readonly string $base_dir;

	/**
	 * @var array<Foundation> $foundations Array holding all the created foundations, keyed by their names.
	 */
	public array $foundations = [];

	/**
	 * Constructor for the App class.
	 * 
	 * @param string|null $base_dir Custom base directory path. Defaults to five levels up from the current directory.
	 */
	public function __construct(string|null $base_dir = null){

		$this->base_dir = $base_dir ?? dirname(__DIR__, 5);

	}

	/**
	 * Creates and registers multiple foundations.
	 * 
	 * @param string $ecosystem The ecosystem class name.
	 * @param array $data An array of foundation data. Each entry should have the foundation name as the key, and the associated data as the value.
	 * @param array $templates_config Additional configuration for templates. Optional.
	 * @return self Returns the current instance for method chaining.
	 */
	public function make(string $ecosystem, array $data, array $templates_config = []) : App{

		foreach ($data as $name => $value) {

			if(is_string($value)){
				$name = $value;
				$value = [];
			}

			$this->addFoundation(
				$ecosystem,
				$name,
				$value['data'] ?? [],
				array_merge(
					$templates_config,
					$value['config']['template'] ?? []
				)
			);
		}

		return $this;

	}

	/**
	 * Adds a new foundation to the `foundations` array.
	 * 
	 * @param string $ecosystem The ecosystem name or type.
	 * @param string|int $name The name of the foundation.
	 * @param array $data Data associated with the foundation.
	 * @param array $templates_config Template configuration for the foundation.
	 * @throws FoundationAlreadyExistsException If a foundation with the same name already exists.
	 * @return self Returns the current instance for method chaining.
	 */
	private function addFoundation(string $ecosystem, string|int $name, array $data, array $templates_config) {

		if(isset($this->foundations[$name]))
			throw new FoundationAlreadyExistsException($name, 2100);

		$this->foundations[$name] = new Foundation($this, $name, $ecosystem, $data, $templates_config);

		return $this;

	}

	/**
	 * Magic getter to retrieve the ecosystem instance of a foundation by name.
	 * 
	 * @param string $property The name of the foundation.
	 * @return Ecosystem|null Returns the ecosystem instance of the foundation, or null if not found.
	 */
	public function __get($property)
	{
		return !is_null($f = $this->getFoundation($property))
			? $f->getEcosystemInstance()
			: null
		;
	}

	/**
	 * Retrieves a foundation instance by its name.
	 * 
	 * @param string|int $name The name of the foundation.
	 * @return Foundation|null Returns the `Foundation` instance if found, or `false` if not.
	 */
	public function getFoundation(string|int $name) : Foundation|null {

		return isset($this->foundations[$name])
			? $this->foundations[$name]
			: null
		;

	}
	
	/**
	 * Generate all foundation templates on the ecosystem.
	 *
	 * @return array Returns generate results of all foundations
	 * 
	 */
	public function generate() : array {

		return array_map(
			fn($foundation) => $foundation->generateEcosystem(),
			$this->foundations
		);

	}

	/**
	 * Cancel all generate templates on the ecosystem.
	 *
	 * @return array Returns generate results of all foundations
	 * 
	 */
	public function cancelGenerated() : void {

		array_map(
			fn($foundation) => $foundation->cancelGeneratedEcosystem(), 
			$this->foundations
		);

	}

}
