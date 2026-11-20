<?php

namespace Crafteus\Environment;

use Crafteus\Exceptions\FoundationAlreadyExistsException;

/**
 * @phpstan-import-type TFoundationOptions from \Crafteus\Environment\Foundation
 * @phpstan-import-type TTemplatesConfigPartial from \Crafteus\Environment\Template
 * @phpstan-import-type TEcosystemTemplatesGenerationResult from \Crafteus\Environment\Ecosystem
 *
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 */
class App
{
	/**
	 * @var string $base_dir Base directory path for the application.
	 */
	public readonly string $base_dir;

	/**
	 * Array holding all the created foundations, keyed by their names.
	 *
	 * @var array<\Crafteus\Environment\Foundation<T_Ecosystem>> $foundations
	 */
	protected array $foundations = [];

	/**
	 * Constructor for the App class.
	 *
	 * @param string|null $base_dir Custom base directory path. Defaults to five levels up from the current directory.
	 */
	public function __construct(string|null $base_dir = null)
	{

		$this->base_dir = $base_dir ?? dirname(__DIR__, 5);
	}

	/**
	 * Creates and registers multiple foundations.
	 *
	 * @template KE of T_Ecosystem
	 *
	 * @param class-string<KE> $ecosystem The ecosystem class name.
	 * @param TFoundationOptions $options An array of foundation options.
	 *   Each entry should have the foundation name as the key, and the associated options as the value.
	 * @param TTemplatesConfigPartial $templates_config Additional
	 *   configuration for templates. Optional.
	 *
	 * @return static<KE> Returns the current instance for method
	 *   chaining.
	 */
	public function make(string $ecosystem, array $options, array $templates_config = []): static
	{
		foreach ($options as $name => $value) {
			if (is_string($value)) {
				$name = $value;
				$value = [];
			}

			$this->addFoundation(
				$name,
				$value['ecosystem'] ?? $ecosystem,
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
	 * Adds a new foundation.
	 *
	 * @param string|int $name The name of the foundation.
	 * @param class-string<T_Ecosystem> $ecosystem The ecosystem name or type.
	 * @param array $data Data associated with the foundation.
	 * @param TTemplatesConfigPartial $templates_config Template configuration for the foundation.
	 *
	 * @return static Returns the current instance for method chaining.
	 *
	 * @throws \Crafteus\Exceptions\FoundationAlreadyExistsException If a foundation with the same name already exists.
	 */
	public function addFoundation(string|int $name, string $ecosystem, array $data = [], array $templates_config = []): static
	{
		if (isset($this->foundations[$name])) {
			throw new FoundationAlreadyExistsException($name, 2100);
		}

		$this->foundations[$name] = new Foundation($this, $name, $ecosystem, $data, $templates_config);

		$this->foundations[$name]->cleanEcosystem();

		return $this;
	}

	/**
	 * Magic getter to retrieve the ecosystem instance of a foundation by name.
	 *
	 * @param string $property The name of the foundation.
	 * @return T_Ecosystem|null Returns the ecosystem instance of the foundation, or null if not found.
	 */
	public function __get($property): ?Ecosystem
	{
		return $this->getFoundation($property)?->ecosystemInstance();
	}

	/**
	 * Retrieves all foundations.
	 *
	 * @return array<\Crafteus\Environment\Foundation<T_Ecosystem>>
	 *
	 */
	public function getFoundations(): array
	{
		return $this->foundations;
	}

	/**
	 * Retrieves a foundation instance by its name.
	 *
	 * @param string|int $name The name of the foundation.
	 * @return \Crafteus\Environment\Foundation<T_Ecosystem>|null Returns the `Foundation` instance if found, or `false` if not.
	 */
	public function getFoundation(string|int $name): Foundation|null
	{
		return $this->foundations[$name] ?? null;
	}

	/**
	 * Checks if foundation exists.
	 *
	 * @param string|int $name
	 *
	 * @return bool
	 *
	 */
	public function foundationExists(string|int $name): bool
	{
		return array_key_exists($name, $this->getFoundations());
	}

	/**
	 * Generate all foundation templates on the ecosystem.
	 *
	 * @param bool $reinit_stub Whether to reinitialize stubs.
	 *
	 * @return TEcosystemTemplatesGenerationResult Returns generate results of all foundations
	 *
	 */
	public function generate(bool $reinit_stub = false): array
	{
		$error = (object) ['status' => false];

		$result = array_map(
			function ($foundation) use ($error, $reinit_stub) {
				return array_map(
					function ($value) use ($error) {
						if (is_array($value) && isset($value['not_generated']) && !empty($value['not_generated'])) {
							array_map(
								function (Stub $stub) use ($error) {
									if ($stub->errorExists()) {
										$error->status = true;
									}
								},
								$value['not_generated']
							);
						}

						return $value;
					},
					$foundation->generateEcosystem(reinit_stub: $reinit_stub)
				);
			},
			$this->foundations
		);

		if ($error->status) {

			$this->cancelGenerated(true);
		}

		return $result;
	}

	/**
	 * Cancel all generate templates on the ecosystem.
	 *
	 * @param bool $dueToError
	 * @return void
	 *
	 */
	public function cancelGenerated(bool $dueToError = false): void
	{
		array_map(
			fn($foundation) => $foundation->cancelGeneratedEcosystem($dueToError),
			array_reverse($this->foundations)
		);
	}

	/**
	 * Removes a foundation from the collection by its name or identifier.
	 *
	 * @param string|int $name The foundation name or identifier.
	 *
	 * @return static
	 *
	 */
	public function removeFoundation(string|int $name): static
	{
		if ($this->foundationExists($name)) {
			unset($this->foundations[$name]);
		}

		return $this;
	}
}
