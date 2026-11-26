<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Traits\TemplateRule;
use Crafteus\Environment\Traits\TemplateStub;
use Crafteus\Exceptions\UnableToResolveTemplateBasenameException;
use Crafteus\Support\Helper;

/**
 * @phpstan-import-type TRuleTypes from \Crafteus\Support\Rule
 * @phpstan-import-type TRuleVerifyCallable from \Crafteus\Support\Rule
 * @phpstan-import-type TRuleVerifyMethod from \Crafteus\Support\Traits\RuleVerify
 *
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 * @template T_Templating of \Crafteus\Environment\Support\Templating|\Crafteus\Contracts\TemplatingInterface
 *
 * @phpstan-type TTemplateRule array{
 *   required: bool,
 *   empty?: bool,
 *   type?: TRuleTypes|array<TRuleTypes>,
 *   verify?: TRuleVerifyMethod|TRuleVerifyCallable|array<TRuleVerifyMethod|TRuleVerifyCallable>
 * }
 *
 * @phpstan-type TTemplateRulePartial array{
 *   required?: bool,
 *   empty?: bool,
 *   type?: TRuleTypes|array<TRuleTypes>,
 *   verify?: TRuleVerifyMethod|TRuleVerifyCallable|array<TRuleVerifyMethod|TRuleVerifyCallable>
 * }
 *
 * @phpstan-type TTemplateDefaultRuleConfig array{
 *   path: TTemplateRulePartial,
 *   extension: TTemplateRulePartial,
 *   stub_file: TTemplateRulePartial,
 *   generate: TTemplateRulePartial,
 *   templating: TTemplateRulePartial
 * } & array<string, TTemplateRulePartial>
 *
 * @phpstan-type TTemplateStubsGenerationResult array{
 *   generated: array<\Crafteus\Environment\Stub>,
 *   not_generated: array<\Crafteus\Environment\Stub>,
 * }
 *
 * @phpstan-type TTemplateStubsGenerationResultPartial array{
 *   generated?: array<\Crafteus\Environment\Stub>,
 *   not_generated?: array<\Crafteus\Environment\Stub>,
 * }
 *
 * @phpstan-type TTemplateTemplatingClosure
 *   \Closure(\Crafteus\Environment\Stub $stub): \Crafteus\Environment\Support\Templating|\Crafteus\Contracts\TemplatingInterface|array<mixed>|null
 * @phpstan-type TTemplateTemplatingValue
 *   TTemplateTemplatingClosure|class-string<\Crafteus\Environment\Support\Templating|\Crafteus\Contracts\TemplatingInterface>|null
 * @phpstan-type TTemplatePathProperty string|array<string>
 * @phpstan-type TTemplateExtensionProperty string|array<string>
 * @phpstan-type TTemplateStubFileProperty string|array<string>
 * @phpstan-type TTemplateGenerateProperty bool|array<bool>
 * @phpstan-type TTemplateTemplatingProperty
 *   TTemplateTemplatingValue|array<TTemplateTemplatingValue>
 *
 * @phpstan-type TTemplateObjectConfig array{
 *   path: TTemplatePathProperty,
 *   extension: TTemplateExtensionProperty,
 *   stub_file: TTemplateStubFileProperty,
 *   generate: TTemplateGenerateProperty,
 *   templating?: TTemplateTemplatingProperty,
 *   transformBasename?: \Closure(string $pathKey, \Crafteus\Environment\Support\AnonymousTemplate $anonymous_template): string
 * }
 *
 * @phpstan-type TTemplateObjectConfigPartial array{
 *   path?: TTemplatePathProperty,
 *
 *   extension?: TTemplateExtensionProperty,
 *
 *   stub_file?: TTemplateStubFileProperty,
 *
 *   generate?: TTemplateGenerateProperty,
 *
 *   templating?: TTemplateTemplatingProperty,
 *
 *   transformBasename?: \Closure(string $pathKey, \Crafteus\Environment\Template $anonymous_template): string
 * }
 *
 * @phpstan-type TTemplatesConfig array<TTemplateObjectConfig>
 *
 * @phpstan-type TTemplatesConfigPartial array<TTemplateObjectConfigPartial>
 *
 * @phpstan-type TTemplateTransformBasename \Closure(string $path_key, \Crafteus\Environment\Template $template): string | array<string>
 *
 * @method string|array transformBaseName(string|int $key_path) Retrieves the file name for the template.
 * @method void afterConfigurationChanged(array $old_config, T_Ecosystem $ecosystem) Called after the template's configuration has been changed.
 */
class Template
{
	/**
	 * @use \Crafteus\Environment\Traits\TemplateRule<T_Ecosystem>
	 */
	use TemplateRule;

	/**
	 * @use \Crafteus\Environment\Traits\TemplateStub<T_Ecosystem,T_Templating>
	 */
	use TemplateStub;

	/**
	 * Path where the file will be generated.
	 *
	 * @var TTemplatePathProperty
	 */
	public string|array $path;

	/**
	 * Extension of the generated file.
	 *
	 * @var TTemplateExtensionProperty
	 */
	public string|array $extension = '';

	/**
	 * Stub file model for the template.
	 *
	 * @var TTemplateStubFileProperty
	 */
	public string|array $stub_file;

	/**
	 * Determines whether the file should be generated.
	 *
	 * @var TTemplateGenerateProperty
	 */
	public bool|array $generate;

	/**
	 * Templating class or function.
	 *
	 * @var TTemplateTemplatingProperty
	 */
	public string|\Closure|null|array $templating = null;

	/**
	 * List of default public properties that should
	 * return a single value for the Stub if their
	 * values are arrays, based on the Stub's key ID.
	 *
	 * @var array
	 */
	public const UNIQUE_STUB_CONFIG_PROPERTIES = ['path', 'extension', 'stub_file', 'generate', 'templating'];

	/**
	 * Current template data.
	 *
	 * @var array|null
	 */
	protected ?array $current_data = null;

	/**
	 * List of public properties that should
	 * return a single value for the Stub if their
	 * values are arrays, based on the Stub's key ID.
	 *
	 * @var array
	 */
	protected array $unique_stub_config_properties = [];

	/**
	 * Name of the template.
	 *
	 * @var string|null
	 */
	private ?string $template_name = null;

	/**
	 * Ecosystem instance associated with this template.
	 *
	 * @var T_Ecosystem|null
	 */
	private ?Ecosystem $ecosystem = null;





	/**
	 * Retrieves the base name of the template file.
	 *
	 * @param string|int $key
	 *        Key to retrieve a specific name.
	 * @param bool $last
	 *        Whether to return the last element
	 *        if the key is not found.
	 *
	 * @return string The base name(s) of the template.
	 *
	 * @throws \Crafteus\Exceptions\UnableToResolveTemplateBasenameException
	 *         If it fails to resolve the template base name.
	 */
	private function getBaseName(string|int $key, bool $last = false): string
	{
		$foundation_name = $this->getFoundationName();

		if (
			// $this->isAnonymousTemplate() &&
			empty($foundation_name) &&
			!$this->transformBasenameExists()
		) {
			throw new UnableToResolveTemplateBasenameException($this->getTemplateName(), 4407);
		}

		$names = [$key => $foundation_name];

		if (method_exists($this, $m = 'transformBasename')) {
			$response = $this->$m($key);

			if (is_array($response) || is_string($response)) {
				$names = !is_array($response)
					? [$key => $response]
					: $response;
			}
		}

		return array_key_exists($key, $names)
			? $names[$key]
			: ($last
				? end($names)
				: current($names)
			);
	}

	/**
	 * Retrieves the foundation name from
	 * the ecosystem's foundation.
	 *
	 * @return string Foundation name.
	 */
	public function getFoundationName(): string
	{
		return $this->getEcosystem()
			?->getFoundation()
			?->getName() ?? '';
	}

	// /**
	//  * Checks if the class instance is an `AnonymousTemplate` class.
	//  *
	//  * @return bool
	//  */
	// private function isAnonymousTemplate() : bool {
	// 	return $this instanceof \Crafteus\Environment\Support\AnonymousTemplate;
	// }

	/**
	 * Check if transform basename closure exists.
	 *
	 * @return bool
	 */
	private function transformBasenameExists(): bool
	{
		return (
			isset($this->{'transform_basename'}) &&
			is_callable($this->{'transform_basename'})
		) || method_exists($this, 'transformBasename');
	}





	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return T_Ecosystem|null Ecosystem instance.
	 */
	public function getEcosystem(): ?Ecosystem
	{
		return $this->ecosystem;
	}

	/**
	 * Sets the ecosystem instance for this template.
	 *
	 * @param T_Ecosystem $ecosystem Ecosystem instance.
	 *
	 * @return static
	 */
	public function setEcosystem(Ecosystem $ecosystem): static
	{

		$this->ecosystem = $ecosystem;

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		if (!in_array($backtrace[1]['class'], [Ecosystem::class])) {
			trigger_error("[W001] - The `setEcosystem` method must be used only in Crafteus classes. So be careful by calling it in your processes.", E_USER_WARNING);
		}

		return $this;
	}





	/**
	 * Retrieves the template's data.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param \Closure(mixed $data):bool|null $check_type
	 * @param bool $checkCompliantData
	 * @param bool $forceInitData
	 *
	 * @return mixed Validated template data.
	 */
	public function getData(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null,
		bool $checkCompliantData = true,
		bool $forceInitData = false
	): mixed {

		$this->initData($forceInitData);

		if ($checkCompliantData) $this->compliantData();

		if ($key === null) {
			return $this->current_data;
		}

		$data = Helper::getNestedArrayValue(
			$key,
			$this->current_data,
			$default_value
		);

		if (
			$data['found'] &&
			$check_type !== null &&
			!$check_type($data['result'])
		) {
			return $default_value;
		}

		return $data['result'];
	}

	/**
	 * Set the template's data
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
	 * Initializes the template's data from
	 * the ecosystem's foundation.
	 *
	 * @param bool $force
	 *        Forces the initialization
	 *        of the data if it is true.
	 *
	 * @return static
	 */
	protected function initData(bool $force = false): static
	{
		if (is_null($this->current_data) || $force) {

			$data = [...($this->getEcosystem()?->getData() ?? [])];

			if (isset($data['__template'])) {

				$template_data = [];

				if (is_array($data['__template'])) {
					$template_data =  array_merge(
						$data['__template']['*']
							?? [],
						Helper::filterAndMergeByKey(
							$this->getTemplateName(),
							$data['__template']
						),
						$data['__template'][$this->getTemplateName()]
							?? []
					);
				}

				unset($data['__template']);

				$data = false
					? array_merge_recursive($data, $template_data)
					: array_merge($data, $template_data);
			}

			$this->setCurrentData($data);
		}

		return $this;
	}





	/**
	 * Get the template name.
	 *
	 * @return string Template name.
	 */
	public function getTemplateName(): string
	{
		return $this->template_name ?? Helper::getClassName(static::class);
	}

	/**
	 * Sets the template name.
	 *
	 * @param string $template_name
	 *        Name of the template.
	 *
	 * @return static
	 */
	public function setTemplateName(string $template_name): static
	{
		$this->template_name = $template_name;

		return $this;
	}





	/**
	 * Retrieves the configuration of the Template
	 * from its public properties.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param \Closure(mixed $config):bool|null $check_type
	 *
	 * @return mixed The template's configuration.
	 */
	public function getConfig(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null
	): mixed {
		$result = Helper::extractObjectVars($this);

		if ($key === null) {
			return $result;
		}

		$config = Helper::getNestedArrayValue(
			$key,
			$result,
			$default_value,
		);

		if (
			$config['found'] &&
			$check_type !== null &&
			!$check_type($config['result'])
		) {
			return $default_value;
		}

		return $config['result'];
	}

	/**
	 * Retrieves public properties that should
	 * return a single value for the Stub.
	 *
	 * @return array
	 */
	public function getUniqueStubConfigProperties(): array
	{
		return $this->unique_stub_config_properties;
	}

	/**
	 * Set public properties that should
	 * return a single value for the Stub.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	protected function setUniqueStubConfigProperties(array $data): void
	{
		$this->unique_stub_config_properties = $data;
	}

	/**
	 * Retrieves a unique configuration value from an array or a single value.
	 *
	 * If the provided data is not an array, it wraps it in an array.
	 * It then retrieves a specific element by its key, or returns
	 * the first or last element based on the `last` flag.
	 *
	 * @param mixed $data
	 *        The data to retrieve the configuration from.
	 *        Can be an array or a single value.
	 * @param string|int|null $key
	 *        Optional key to retrieve a
	 *        specific value from the array.
	 * @param bool $last
	 *        Whether to return the last element if the key
	 *        is not found. Defaults to false.
	 * @param bool $authorize_array
	 * @param mixed $default_value
	 * @param ?string $config_key
	 *
	 * @return mixed
	 *         The requested configuration value,
	 *         either from the key or first/last element.
	 */
	public static function getUniqueConfig(
		$data,
		string|int|null $key = null,
		bool $last = false,
		bool $authorize_array = true,
		mixed $default_value = null,
		?string $config_key = null
	): mixed {
		$result = !is_array($data) ? [$data] : $data;

		return !is_null($key)
			? (array_key_exists($key, $result)
				? (!$authorize_array && is_array($result[$key])
					? $default_value
					: $result[$key]
				)
				: (empty($result)
					? $default_value
					: ($last
						? end($result)
						: current($result)
					)
				)
			)
			: $result;
	}





	/**
	 * Retrieves the file path(s) for the template.
	 *
	 * @return array List of paths.
	 */
	protected function getPath(): array
	{
		return is_string($this->path) ? [$this->path] : $this->path;
	}

	/**
	 * Retrieves the file extension(s) for the template.
	 *
	 * @param string|int $key
	 *        Key to retrieve a specific extension.
	 * @param bool $last
	 *        Whether to return the last element
	 *        if the key is not found.
	 *
	 * @return string File extension(s).
	 */
	protected function getExtension(string|int $key, bool $last = false): string
	{
		return static::getUniqueConfig($this->extension, $key, $last, false, "");
	}

	/**
	 * Retrieves the stub file(s) for the template.
	 *
	 * @param string|int $key
	 *        Key to retrieve a specific stub file.
	 * @param bool $last
	 *        Whether to return the last element if
	 *        the key is not found.
	 *
	 * @return string Stub file(s).
	 */
	protected function getStubFile(string|int $key, bool $last = false): string
	{
		return static::getUniqueConfig($this->stub_file, $key, $last, false, "");
	}

	/**
	 * Retrieves the generate flag(s) for the template.
	 *
	 * @param string|int $key
	 *        Key to retrieve a specific flag.
	 * @param bool $last
	 *        Whether to return the last element
	 *        if the key is not found.
	 *
	 * @return bool Generate flag(s).
	 */
	protected function getGenerate(string|int $key, bool $last = false): bool
	{
		return static::getUniqueConfig($this->generate, $key, $last);
	}

	/**
	 * Retrieves the templating method(s) for the template.
	 *
	 * @param string|int $key
	 *        Key to retrieve a specific method.
	 * @param bool $last
	 *        Whether to return the last element if the
	 *        key is not found.
	 *
	 * @return string|\Closure
	 *         Templating method(s).
	 */
	protected function getTemplating(string|int $key, bool $last = false): string|\Closure|null
	{
		return static::getUniqueConfig(
			$this->templating,
			$key,
			$last,
			false,
			null
		);
	}
}
