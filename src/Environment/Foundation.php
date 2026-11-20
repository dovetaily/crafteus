<?php

namespace Crafteus\Environment;

use Crafteus\Exceptions\InvalidEcosystemException;
use Crafteus\Exceptions\TemplateNotRecognizedException;
use Crafteus\Support\Helper;

/**
 * @phpstan-import-type TTemplatesConfigPartial from \Crafteus\Environment\Template
 * @phpstan-import-type TEcosystemTemplatesGenerationResult from \Crafteus\Environment\Ecosystem
 *
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 *
 * @phpstan-type TFoundationOptions array<string|array{
 *   data?: array,
 *
 *   config?: array{
 *     template?: TTemplatesConfigPartial
 *   },
 *
 *   ecosystem?: class-string<\Crafteus\Environment\Ecosystem>
 * }>
 */
class Foundation
{

	/**
	 * Name of the foundation.
	 *
	 * @var string|int
	 */
	public readonly string|int $name;

	/**
	 * Name of the class representing the ecosystem.
	 *
	 * @var class-string<T_Ecosystem>
	 */
	public readonly string $ecosystem;

	/**
	 * Configuration of templates associated with the ecosystem.
	 *
	 * @var TTemplatesConfigPartial
	 */
	public readonly array $templates_config;

	/**
	 * Instance of the main application.
	 *
	 * @var \Crafteus\Environment\App<T_Ecosystem>|null
	 */
	private readonly ?App $app;

	/**
	 * Instance of the current ecosystem.
	 *
	 * @var T_Ecosystem|null
	 */
	private Ecosystem|null $ecosystem_instance = null;

	/**
	 * Data used by the foundation.
	 *
	 * @var array
	 */
	private readonly array $data;

	/**
	 * Whether the ecosystem is clean.
	 *
	 * @var bool
	 */
	private bool $ecosystem_is_clean = false;

	/**
	 * Foundation class constructor.
	 * Initializes the properties and cleans the ecosystem.
	 *
	 * @param \Crafteus\Environment\App<T_Ecosystem>|null $app Instance of the application.
	 * @param string $name Name of the foundation.
	 * @param class-string<T_Ecosystem> $ecosystem Name of the ecosystem class.
	 * @param array $data
	 *        Data associated with the foundation.
	 * @param TTemplatesConfigPartial $templates_config
	 *        Configuration of the ecosystem templates (optional).
	 */
	public function __construct(?App $app, string|int $name, string $ecosystem, array $data = [], array $templates_config = [])
	{
		$this->name = $name;
		$this->ecosystem = $ecosystem;
		$this->templates_config = $templates_config;

		$this->app = $app;
		$this->data = $data;
	}





	/**
	 * Retrieves the foundation's name.
	 *
	 * @return string Foundation name.
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Retrieves the data associated with the foundation.
	 *
	 * @return array Foundation data.
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Get App object.
	 *
	 * @return \Crafteus\Environment\App<T_Ecosystem>|null
	 */
	public function getApp(): App|null
	{
		return $this->app;
	}

	/**
	 * Indicates whether the ecosystem is clean.
	 *
	 * @return bool
	 *         True if the ecosystem is clean,
	 *         false otherwise.
	 */
	public function ecosystemIsClean(): bool
	{
		return $this->ecosystem_is_clean;
	}





	/**
	 * Checks the integrity of the ecosystem data.
	 *
	 * @return void
	 */
	public function cleanEcosystem(): void
	{
		$this->ecosystem_is_clean = false;

		$this->cleanTemplateEcosystem();

		if (method_exists(
			$eco = $this->ecosystemInstance(),
			'afterFoundationCleanEcosystem'
		)) {
			$eco->afterFoundationCleanEcosystem(...[$this]);
		}

		$this->ecosystem_is_clean = true;
	}

	/**
	 * Verifies whether the ecosystem class exists and
	 * is a subclass of Ecosystem.
	 *
	 * @throws \Crafteus\Exceptions\InvalidEcosystemException
	 *         If the ecosystem is invalid (code 3200).
	 *
	 * @return void
	 */
	public function cleanClassEcosystem(): void
	{
		if (!(
			class_exists($this->ecosystem)
			&&
			is_subclass_of($this->ecosystem, Ecosystem::class)
		)) {
			throw new InvalidEcosystemException($this->ecosystem, code: 2201);
		}
	}

	/**
	 * Validates and initializes the ecosystem templates.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\TemplateNotRecognizedException
	 *         If a template is unknown in the ecosystem (code 4200).
	 */
	public function cleanTemplateEcosystem(): void
	{
		$this->ecosystemInstance()->cleanTemplates();
	}





	/**
	 * Retrieves the current ecosystem instance,
	 * if it has been created.
	 *
	 * @return T_Ecosystem|null
	 *         The current ecosystem instance,
	 *         or null if it has not been initialized.
	 */
	public function getEcosystemInstance(): ?Ecosystem
	{
		return $this->ecosystem_instance;
	}

	/**
	 * Retrieves a unique instance of the current ecosystem.
	 * If the instance does not exist, it is created and associated
	 * with the foundation.
	 *
	 * @param mixed ...$args
	 *        Arguments passed to the ecosystem constructor.
	 *
	 * @return T_Ecosystem Instance of the ecosystem.
	 */
	public function ecosystemInstance(...$args): Ecosystem
	{
		$this->cleanClassEcosystem();


		if (is_null($this->ecosystem_instance)) {

			$c = $this->ecosystem;

			/** @phpstan-ignore new.noConstructor */
			$this->ecosystem_instance = (new $c(...$args))->setFoundation($this);
		}

		return $this->ecosystem_instance;
	}





	/**
	 * Generates templates from the ecosystem and returns the results.
	 *
	 * @param bool $reinit_stub
	 *        Whether to reinitialize stubs.
	 *
	 * @return TEcosystemTemplatesGenerationResult
	 *         List of generated templates.
	 */
	public function generateEcosystem(bool $reinit_stub = false): array
	{
		if (!$this->ecosystem_is_clean)
			$this->cleanEcosystem();

		return $this->ecosystemInstance()->generateTemplates(reinit_stub: $reinit_stub);
	}

	/**
	 * Cancels the generation of templates for this ecosystem.
	 *
	 * @param bool $dueToError
	 * @return void
	 */
	public function cancelGeneratedEcosystem(bool $dueToError = false): void
	{
		$this->ecosystemInstance()->cancelTemplatesGenerated($dueToError);
	}
}
