<?php

namespace Crafteus\Environment;

use Crafteus\Contracts\EcosystemInitializable;
use Crafteus\Contracts\GenerationLifecycle;
use Crafteus\Crafteus;
use Crafteus\Environment\Template;
use Crafteus\Environment\Support\AnonymousTemplate;
use Crafteus\Exceptions\DuplicateTemplateInstanceException;
use Crafteus\Exceptions\EcosystemMissingMethodException;
use Crafteus\Exceptions\InvalidTemplateException;
use Crafteus\Exceptions\InvalidTemplatePropertyException;
use Crafteus\Exceptions\InvalidTemplateTypeException;
use Crafteus\Exceptions\MissingConfigKeyException;
use Crafteus\Exceptions\TemplateConfigException;
use Crafteus\Exceptions\TemplateNotRecognizedException;
use Crafteus\Exceptions\TemplateValidationException;
use Crafteus\Support\Helper;

/**
 * @phpstan-import-type TFoundationOptions from \Crafteus\Environment\Foundation
 * @phpstan-import-type TTemplateStubsGenerationResultPartial from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateObjectConfig from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplatesConfigPartial from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateObjectConfigPartial from \Crafteus\Environment\Template
 *
 * @template T_Templates of array<string|int, \Crafteus\Environment\Template>
 * @template T_TemplatesConfigPartial of TTemplatesConfigPartial
 *
 * @phpstan-type TEcosystemTemplatesGenerationResult array<array<bool|TTemplateStubsGenerationResultPartial>>
 *
 * @phpstan-type TEcosystemTemplateConfigKeys array{
 *   config: TTemplateObjectConfig,
 *   rules?: array{
 *     config?: array<mixed>,
 *     data?: array<mixed>,
 *   },
 *   afterConfigurationChanged?: \Closure(
 *     TTemplateObjectConfig $newConfig,
 *     TTemplateObjectConfig $oldConfig,
 *     class-string<\Crafteus\Environment\Template>|array $ecosystemTemplateConfig,
 *     static $ecosystem
 *   ): void,
 *   afterInitialize?: \Closure(static $ecosystem, \Crafteus\Environment\Support\AnonymousTemplate $anonymous_template): void,
 * }
 *
 * @phpstan-type TEcosystemTemplateConfigKeysPartial array{
 *   config?: TTemplateObjectConfig,
 *   rules?: array{
 *     config?: array<mixed>
 *   },
 *   afterConfigUpdated?: \Closure(
 *     TTemplateObjectConfig $newConfig,
 *     TTemplateObjectConfig $oldConfig,
 *     class-string<\Crafteus\Environment\Template>|array $ecosystemTemplateConfig
 *   ): void
 * }
 *
 * @phpstan-type TEcosystemTemplateConfig class-string<\Crafteus\Environment\Template>|TEcosystemTemplateConfigKeys
 *
 * @phpstan-type TEcosystemTemplates array<TEcosystemTemplateConfig>
 *
 * @method void afterFoundationSet(\Crafteus\Environment\Foundation<static> $foundation) Executes after the foundation has been set.
 * @method void afterFoundationCleanEcosystem(\Crafteus\Environment\Foundation<static> $foundation) Executes actions after the foundation has cleaned (reviewed and fixed) all ecosystem templates.
 * @method void afterFoundationCleanTemplateEcosystem(\Crafteus\Environment\Template<static> $template, static $ecosystem) Executes actions after the foundation has cleaned (reviewed and fixed) ecosystem template.
 */
class Ecosystem
{
	/**
	 * Indicates whether existing files should be replaced.
	 *
	 * @var bool
	 */
	public bool $replace_exist_file = false;

	/**
	 * Cancel all files generated on error.
	 *
	 * @var bool
	 */
	public bool $cancel_all_on_error = false;

	/**
	 * Indicates whether the stub content should be initialized at
	 * the same time as the template is created.
	 *
	 * @var bool
	 */
	protected bool $initialize_stub_content_with_template = false;

	/**
	 * Current ecosystem data.
	 *
	 * @var array|null
	 */
	protected ?array $current_data = null;

	/**
	 * The instance of the foundation associated with the ecosystem.
	 *
	 * @var \Crafteus\Environment\Foundation|null
	 */
	private ?Foundation $foundation;

	/**
	 * An array of loaded template instances.
	 *
	 * @var T_Templates
	 */
	private array $templates_instance = [];





	/**
	 * Abstract method that must be implemented in subclasses
	 * to provide templates.
	 *
	 * @return TEcosystemTemplates An array of templates.
	 *
	 * @throws \Crafteus\Exceptions\EcosystemMissingMethodException If the method is not implemented.
	 */
	public function template(): array
	{
		throw new EcosystemMissingMethodException(static::class, 'template', 3300);

		return [];
	}

	/**
	 * Obtain if you have to initialize the contents of the stub.
	 *
	 * @return bool
	 */
	public function shouldInitializeStubContent(): bool
	{
		return $this->initialize_stub_content_with_template;
	}

	/**
	 * If it returns true, while generating the files if an
	 * error occurs, even the generated files will be canceled.
	 *
	 * @return bool
	 */
	public function cancelAllOnError(): bool
	{
		return $this->cancel_all_on_error;
	}





	/**
	 * Creates and registers multiple foundations.
	 *
	 * @param TFoundationOptions $foundation_options
	 *        An array of foundation options.
	 *        Each entry should have the foundation name as the key,
	 *        and the associated foundation options as the value.
	 * @param T_TemplatesConfigPartial $templates_config
	 *        Additional configuration for templates. Optional.
	 *
	 * @return \Crafteus\Environment\App<static>
	 */
	public static function make(array $foundation_options, array $templates_config = []): App
	{
		return Crafteus::make(static::class, $foundation_options, $templates_config);
	}





	/**
	 * Retrieves the ecosystem data.
	 *
	 * @return array Validated template data.
	 */
	public function getData(): array
	{
		if (is_null($this->current_data))
			$this->initData();

		return $this->current_data ?? [];
	}

	/**
	 * Initializes the ecosystem current data
	 * from the ecosystem's foundation.
	 *
	 * @param bool $force
	 *        Forces the initialization of the data
	 *        if it is true.
	 *
	 * @return void
	 */
	protected function initData(bool $force = false): void
	{
		$foundation = $this->getFoundation();

		if ($foundation && (is_null($this->current_data) || $force))
			$this->setCurrentData($foundation->getData());
	}

	/**
	 * Retrieves the ecosystem current data.
	 *
	 * @return ?array
	 */
	public function getCurrentData(): ?array
	{
		return $this->current_data;
	}

	/**
	 * Set the ecosystem data
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	protected function setCurrentData(array $data): void
	{
		$this->current_data = $data;
	}





	/**
	 * Sets the foundation associated with the ecosystem.
	 *
	 * @param \Crafteus\Environment\Foundation $foundation The foundation instance.
	 *
	 * @return static The instance of the ecosystem.
	 */
	public function setFoundation(Foundation $foundation): static
	{
		$this->foundation = $foundation;

		if (method_exists($this, 'afterFoundationSet')) {
			$this->afterFoundationSet(...[$this->foundation]);
		}

		return $this;
	}

	/**
	 * Retrieves the foundation associated with the ecosystem.
	 *
	 * @return \Crafteus\Environment\Foundation|null
	 *         The foundation instance, or null if not set.
	 */
	public function getFoundation(): ?Foundation
	{
		return $this->foundation ?? null;
	}





	/**
	 * Retrieves a template by its name.
	 *
	 * @param string $template_name
	 *        The name of the template.
	 *
	 * @return TEcosystemTemplateConfig|false
	 *         The corresponding template,
	 *         or false if not found.
	 */
	public function getTemplate(string $template_name): string|array|false
	{
		$templates = $this->template();

		return $templates[$template_name] ?? false;
	}

	/**
	 * Retrieves all templates instance.
	 *
	 * @return T_Templates
	 *         An array of templates instance.
	 */
	public function getTemplatesInstance(): array
	{
		return $this->templates_instance;
	}

	/**
	 * Retrieves template instance.
	 *
	 * @param string|int|key-of<T_Templates> $template_name
	 *
	 * @return value-of<T_Templates>|\Crafteus\Environment\Template|null
	 */
	public function getTemplateInstance(string|int $template_name): ?Template
	{
		return $this->templates_instance[$template_name]
			?? null;
	}

	/**
	 * Retrieves the default validation rule for templates.
	 *
	 * @param array $rule Additional rules to apply.
	 *
	 * @return array The default rule configuration.
	 */
	public static function getDefaultTemplateRule(array $rule = []): array
	{
		return Template::defaultRuleConfig($rule);
	}

	/**
	 * Retrieves a template instance, or returns false if not found.
	 *
	 * @param string|int|key-of<T_Templates> $template_name
	 *        The name or identifier of the template.
	 * @param TTemplateObjectConfigPartial $more_config
	 *        Additional configuration to apply to the instance.
	 *
	 * @return value-of<T_Templates>|\Crafteus\Environment\Template|false
	 *         The template instance, or false if not found.
	 *
	 * @throws \Crafteus\Exceptions\InvalidTemplateException
	 *         If the template is invalid.
	 * @throws \Crafteus\Exceptions\InvalidTemplateTypeException
	 *         If the template type is incorrect.
	 * @throws \Crafteus\Exceptions\DuplicateTemplateInstanceException
	 *         If an instance of the template already exists.
	 * @throws \Crafteus\Exceptions\TemplateConfigException
	 *         If the property value's type in the template instance is incorrect.
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If validation errors are found.
	 * @throws \Crafteus\Exceptions\InvalidTemplatePropertyException
	 * @throws \Crafteus\Exceptions\MissingConfigKeyException
	 */
	public function templateInstance(string|int $template_name, array $more_config = []): Template|false
	{
		if ($this->getTemplateInstance($template_name)) {
			return $this->getTemplateInstance($template_name) ?? false; // PHPDocs forced
		} else {
			if ($template = $this->getTemplate($template_name)) {

				$template_instance = null;

				if (is_string($template)) {
					$template_instance = $this->getTemplateInstanceWithTemplateClass($template, $template_name, $more_config);
				} elseif (is_array($template)) {
					$template_instance = $this->getTemplateInstanceWithTemplateConfig($template, $template_name, $more_config);
				} else throw new InvalidTemplateTypeException(
					$template_name,
					static::class,
					code: 4304
				);

				if ($template_instance) {

					// Validate Template Data
					// $this->compliantTemplateData($template_instance);

					if ($this->addTemplateInstance($template_name, $template_instance)) {
						return $this->templates_instance[$template_name];
					}

					throw new DuplicateTemplateInstanceException(
						$template_name,
						static::class,
						$this->foundation->getName(),
						code: 3302
					);
				}
			}
		}
		return false;
	}

	/**
	 * @param class-string<\Crafteus\Environment\Template> $template Template class.
	 * @param string|int $template_name
	 *        The name or identifier of the template.
	 * @param TTemplateObjectConfigPartial $more_config
	 *        Additional configuration to apply to the instance.
	 *
	 * @return \Crafteus\Environment\Template
	 *
	 * @throws \Crafteus\Exceptions\InvalidTemplateException
	 *         If the template is invalid.
	 * @throws \Crafteus\Exceptions\TemplateConfigException
	 *         If the property value's type in the template instance is incorrect.
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If validation errors are found.
	 */
	private function getTemplateInstanceWithTemplateClass(
		string $template,
		string|int $template_name,
		array $more_config = []
	): ?Template {
		$template_instance = null;

		if (class_exists($template) && is_subclass_of($template, Template::class)) {
			$template_instance = new $template;

			if (is_subclass_of($template_instance, EcosystemInitializable::class)) {
				$template_instance->beforeInitialize($this);
			}

			// update config start...

			$origin_config = get_object_vars($template_instance);
			foreach ($more_config as $key => $value) {
				if (array_key_exists($key, $origin_config) && $origin_config[$key] !== $value) {
					try {
						$template_instance->$key = $value;
					} catch (\TypeError $th) {
						throw new TemplateConfigException(
							static::class,
							$key,
							$template,
							previous: $th,
							code: 4301
						);
					}
				}
			}

			if (method_exists($template_instance, 'afterConfigurationChanged')) {
				$template_instance->afterConfigurationChanged(...[$origin_config]); // old_config
			}
			// update config stop...

			$this->compliantTemplateArray(
				get_object_vars($template_instance),
				"Error in Ecosystem class `" . static::class . "`. Please check the validity of the properties in the template class `" . $template . "`.",
				compliant_message: [
					'required' => 'The class property `:name` is required',
					'type' => 'The class property `:name` type is not available :available_type',
					'empty' => 'The class property `:name` is empty',
					'verify' => 'The class property `:name` has not available value',
					'verify.class-string' => 'The class `:name` class object is not available :available_classes',
				],
				template_rule: $template_instance->getConfigRule()
			);

			$template_instance->setEcosystem($this);
			$template_instance->setTemplateName($template_name);

			if (is_subclass_of(
				$template_instance,
				EcosystemInitializable::class
			)) {
				$template_instance->afterInitialize($this);
			}
		} else
			throw new InvalidTemplateException($template, code: 3401);

		return $template_instance;
	}

	/**
	 * @param TEcosystemTemplateConfigKeys $template
	 *        Configuration of the template.
	 * @param string|int $template_name
	 *        The name or identifier of the template.
	 * @param TTemplateObjectConfigPartial $more_config
	 *        Additional configuration to apply to the instance.
	 *
	 * @return Template|null
	 *
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If validation errors are found.
	 * @throws \Crafteus\Exceptions\MissingConfigKeyException
	 *         If `config` key is missed on template configuration.
	 * @throws \Crafteus\Exceptions\InvalidTemplatePropertyException
	 */
	private function getTemplateInstanceWithTemplateConfig(
		array &$template,
		string|int $template_name,
		array $more_config = []
	): ?Template {
		$template_instance = null;

		if (!isset($template['config']) || !is_array($template['config'])) {
			throw new MissingConfigKeyException($template_name, code: 4303);
		}

		// update config start ...
		$origin_config = $template['config'];

		foreach ($more_config as $key => $value)
			if (array_key_exists($key, $origin_config) && $origin_config[$key] !== $value) {
				$template['config'][$key] = $value;
			}

		if (isset($template['afterConfigurationChanged']) && is_callable($template['afterConfigurationChanged'])) {
			$template['afterConfigurationChanged'](...[
				$template['config'], // &$new_config
				$origin_config,  // $old_config
				$template,
				$this
			]);
		}
		// update config stop ...

		$this->compliantTemplateArray(
			$template['config'],
			template_rule: $template['rules']['config'] ?? []
		);

		try {

			$template_instance = new AnonymousTemplate(...$template['config']);
		} catch (\Throwable $th) {

			throw new InvalidTemplatePropertyException(
				$template_name,
				AnonymousTemplate::UNAUTHORIZED_KEYS,
				code: 4306,
				previous: $th
			);
		}

		$template_instance
			->setConfigRule($template['rules']['config'] ?? [])
			->setDataRule($template['rules']['data'] ?? [])
			->setEcosystem($this)
			->setTemplateName($template_name)
		;

		if (
			isset($template['config']['transformBasename']) &&
			is_callable($template['config']['transformBasename'])
		) {
			$template_instance->setTransformBasename($template['config']['transformBasename']);
		}

		if (
			isset($template['afterInitialize']) &&
			is_callable($template['afterInitialize'])
		) {
			$template['afterInitialize']($this, $template_instance);
		}

		return $template_instance;
	}

	/**
	 * Adds a template instance to the ecosystem's collection of templates.
	 * If the template instance already exists, it will not be added again.
	 * This method ensures that each template has only one instance in the ecosystem.
	 *
	 * @param string $template_name
	 *        The name of the template.
	 * @param \Crafteus\Environment\Template $template
	 *        The template instance to add.
	 *
	 * @return bool
	 *         Returns true if the template was successfully added,
	 *         false if it already exists.
	 */
	protected function addTemplateInstance(string $template_name, Template $template): bool
	{
		if (!isset($this->templates_instance[$template_name])) {
			$this->templates_instance[$template_name] = $template;
			return true;
		}
		return false;
	}

	/**
	 * Checks the compliance of a template's properties
	 * according to the defined rules.
	 *
	 * @param array $template
	 *        The properties of the template.
	 * @param string|null|null $error_message
	 *        The error message to display in case of non-compliance.
	 * @param array $template_rule
	 *        The validation rules.
	 * @param array $compliant_message
	 *        Custom error messages.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If validation errors are found.
	 */
	private function compliantTemplateArray(
		array $template,
		string|null $error_message = null,
		array $template_rule = [],
		array $compliant_message = []
	): void {
		$compliant = Helper::compliantArray(
			rules: Ecosystem::getDefaultTemplateRule($template_rule),
			data: $template
		);

		if ($compliant->check(message: $compliant_message)->errorExists()) {

			throw new TemplateValidationException(
				$compliant->getErrors(),
				$error_message ?? "Error from the `" . static::class . "` ecosystem class, please check that the keys sent to the `template` method are valid or the configurations add in addition to `Crafteus::make`.",
				code: 4305
			);
		}
	}

	/**
	 * Checks the compliance of a template's data according to the defined rules.
	 *
	 * @param \Crafteus\Environment\Template $template The template instance.
	 *
	 * @return void
	 */
	private function compliantTemplateData(Template $template): void
	{
		$template->compliantData();
	}

	/**
	 * Cleans and validates the properties of a given
	 * template according to the defined configuration rules.
	 * This method ensures that the template's properties
	 * comply with the expected structure and values.
	 *
	 * @param \Crafteus\Environment\Template $template
	 *        The template instance to clean and validate.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If the template's properties do not comply with
	 *         the expected rules.
	 */
	protected function cleanTemplate(Template $template): void
	{
		$this->compliantTemplateArray(
			get_object_vars($template),
			AnonymousTemplate::class == get_class($template) ? null : "Error from the `" . static::class . "` ecosystem class, please check the validity of the template class properties (or the configuration of the table of this template) or the configurations added in `Crafteus::make`.",
			compliant_message: AnonymousTemplate::class == get_class($template) ? [] : [
				'required' => 'The class property `:name` is required',
				'type' => 'The class property `:name` type is not available :available_type',
				'empty' => 'The class property `:name` is empty',
				'verify' => 'The class property `:name` has not available value',
				'verify.class-string' => 'The class property `:name` class object is not available :available_classes',
			],
			template_rule: $template->getConfigRule()
		);
	}

	/**
	 * Validates and initializes the templates.
	 *
	 * @return static
	 *
	 * @throws \Crafteus\Exceptions\TemplateNotRecognizedException
	 *         If a template is unknown in the ecosystem (code 4200).
	 */
	public function cleanTemplates(): static
	{
		foreach (array_keys($this->template()) as $template_name) {
			$template_instance = $this->templateInstance(
				$template_name,
				$this->getFoundation() !== null
					? array_merge(
						$this->getFoundation()->templates_config['*'] ?? [],
						Helper::filterAndMergeByKey($template_name, $this->getFoundation()->templates_config),
						$this->getFoundation()->templates_config[$template_name] ?? []
					)
					: []
			);

			if ($template_instance === false) {
				throw new TemplateNotRecognizedException(
					$template_name,
					static::class,
					code: 4300
				);
			}

			if (method_exists($this, 'afterFoundationCleanTemplateEcosystem')) {
				$this->afterFoundationCleanTemplateEcosystem(...[$template_instance, $this]);
			}

			if ($this->shouldInitializeStubContent())
				$template_instance->initStubs();
		}

		return $this;
	}

	/**
	 * Generates the template or returns false if generation fails.
	 *
	 * @param \Crafteus\Environment\Template|string|int|key-of<T_Templates> $template
	 *        The template to generate.
	 * @param bool $reinit_stub Whether to reinitialize stubs.
	 *
	 * @return bool|TTemplateStubsGenerationResultPartial
	 * True if all the stubs are generated,
	 * or a table of all not generated with generated stub files.
	 */
	protected function generateTemplate(Template|string|int $template, bool $reinit_stub = false): bool|array
	{
		if (
			(is_string($template) || is_int($template)) &&
			is_subclass_of(
				$st = $this->templateInstance($template),
				Template::class
			)
		) {
			$template = $st;
		}

		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		)) {
			$this->beforeGenerate($template, __METHOD__);
		}

		if (is_subclass_of(
			$template,
			Template::class
		)) {
			// beforeGenerate
			$stubs_result = $template->generateStubsFile(reinit_stub: $reinit_stub);

			if (
				is_subclass_of($this, GenerationLifecycle::class) &&
				count($stubs_result['not_generated']) == 0
			) {
				$this->afterGenerateSuccessfully($template, __METHOD__);
			} elseif (is_subclass_of(
				$this,
				GenerationLifecycle::class
			)) {
				$this->afterGenerateFailed($template, __METHOD__);
			}

			if (is_subclass_of(
				$this,
				GenerationLifecycle::class
			)) {
				$this->afterGenerate($template, __METHOD__);
			}

			return count($stubs_result['not_generated']) == 0 ? true : $stubs_result;
		}

		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		)) {
			$this->afterGenerateFailed($template, __METHOD__);
		}

		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		)) {
			$this->afterGenerate($template, __METHOD__);
		}

		return false;
	}

	/**
	 * Generates all templates within the ecosystem.
	 *
	 * @param bool $reinit_stub Whether to reinitialize stubs.
	 *
	 * @return TEcosystemTemplatesGenerationResult
	 * An array of generation results for each template.
	 */
	public function generateTemplates(bool $reinit_stub = false): array
	{
		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		)) {
			$this->beforeGenerate($this, __METHOD__);
		}

		$generated = [];

		$error = false;

		foreach ($this->templates_instance as $template_name => $template_instance) {
			$result = $this->generateTemplate(template: $template_instance, reinit_stub: $reinit_stub);

			$generated[$template_name] = $result;

			if ($result !== true) {
				$error = true;

				// break;
			}
		}

		if ($error) {
			if (is_subclass_of(
				$this,
				GenerationLifecycle::class
			)) {
				$this->afterGenerateFailed($this, __METHOD__);
			}

			$this->cancelTemplatesGenerated(true);
		}

		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		) && !$error) {
			$this->afterGenerateSuccessfully($this, __METHOD__);
		}

		if (is_subclass_of(
			$this,
			GenerationLifecycle::class
		)) {
			$this->afterGenerate($this, __METHOD__);
		}

		return $generated;
	}

	/**
	 * Cancels the generation of stub files for all templates.
	 *
	 * @param bool $dueToError
	 *
	 * @return void
	 */
	public function cancelTemplatesGenerated(bool $dueToError = false): void
	{
		if (!$dueToError || ($dueToError && $this->cancelAllOnError())) {
			foreach (array_reverse($this->templates_instance, true) as $template_name => $template_instance) {

				$template_instance->cancelStubsFilesGenerated();
			}
		}
	}
}
