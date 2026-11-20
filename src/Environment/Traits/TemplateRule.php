<?php

namespace Crafteus\Environment\Traits;

use Crafteus\Contracts\TemplatingInterface;
use Crafteus\Environment\Support\Templating;
use Crafteus\Environment\Template;
use Crafteus\Exceptions\InvalidTemplateDataException;
use Crafteus\Exceptions\TemplateValidationException;
use Crafteus\Support\Helper;
use Crafteus\Support\Rule;

/**
 * @phpstan-import-type TTemplateDefaultRuleConfig from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateRule from \Crafteus\Environment\Template
 */
trait TemplateRule
{
	/**
	 * Get the template name.
	 *
	 * @return string Template name.
	 */
	abstract public function getTemplateName(): string;


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
	abstract public function getConfig(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null
	): mixed;


	/**
	 * Configuration Rule for the template.
	 *
	 * @var array<string,TTemplateRule>
	 */
	protected array $config_rule = [];


	/**
	 * Data Rule for the template.
	 *
	 * @var array<string,TTemplateRule>
	 */
	protected array $data_rule = [];


	/**
	 * Retrieves the configuration rule for the template.
	 *
	 * @return array The configuration rule.
	 */
	public function getConfigRule(): array
	{

		return $this->config_rule;
	}


	/**
	 * Retrieves the data rule for the template.
	 *
	 * @return array The data rule.
	 */
	public function getDataRule(): array
	{
		return $this->data_rule;
	}


	/**
	 * Retrieves all rules (configuration and data).
	 *
	 * @return array
	 *         An array containing
	 *         both 'config' and 'data' rules.
	 */
	public function getRules(): array
	{
		return [
			'config' => $this->getConfigRule(),
			'data' => $this->getDataRule(),
		];
	}


	/**
	 * Sets the configuration rule for the template.
	 *
	 * @param array $rule
	 *        The configuration rule to set.
	 *
	 * @return static
	 */
	public function setConfigRule(array $rule): static
	{
		$this->config_rule = $rule;
		return $this;
	}


	/**
	 * Sets the data rule for the template.
	 *
	 * @param array $rule
	 *        The data rule to set.
	 *
	 * @return static
	 */
	public function setDataRule(array $rule): static
	{
		$this->data_rule = $rule;
		return $this;
	}


	/**
	 * Provides default configuration for template rules.
	 *
	 * @param array $rule
	 *        Additional rule configuration.
	 *
	 * @return TTemplateDefaultRuleConfig
	 *         The merged default and provided rules.
	 */
	public static function defaultRuleConfig(array $rule = []): array
	{
		return [
			...$rule,
			'path' => [
				'required' => true,
				// 'empty' => false,
				'type' => 'string|array',
				'verify' => 'array<string>',
			],
			'extension' => [
				'required' => true,
				'empty' => true,
				'type' => 'string|array',
				'verify' => 'array<string>',
			],
			'stub_file' => [
				'required' => true,
				'empty' => true,
				'type' => 'string|array',
				'verify' => ['array<string>', function ($data, $data_exists, Rule $rule) {
					$check = null;
					if (!$rule->errorExists()) {

						if (is_array($data)) {
							$res = [];
							foreach ($data as $dt) {
								if (Helper::looksLikePath($dt) && !is_readable($dt)) {
									$res[] = 'The stub file `' . $dt . '` is not readable !';
								}
							}
							if (!empty($res)) $check = $res;
						} else $check = Helper::looksLikePath($data) && !is_readable($data)
							? 'The stub file `' . $data . '` is not readable !'
							: null;
					}
					return $check;
					// return !$rule->errorExists() &&
					// ;
				}],
			],
			'generate' => [
				'required' => true,
				// 'empty' => false,
				'type' => 'array|boolean',
			],
			'templating' => [
				'required' => false,
				'empty' => true,
				'type' => 'string|function|null|array',
				'verify' => ['array<string|function|null>', 'class-string<\Crafteus\Contracts\TemplatingInterface|\Crafteus\Environment\Support\Templating>', function ($data, $data_exists, Rule $rule) {
					$check = null;
					if (!$rule->errorExists() && $data_exists) {

						if (is_array($data)) {
							$res = [];
							foreach ($data as $dt) {
								if (self::checkTemplatingType($dt)) {
									$res[] = "The templating class `{$dt}` doesn\'t exists or is not class of `Crafteus\Environment\Support\Templating` or not implement `Crafteus\Contracts\TemplatingInterface` interface !";
								}
							}

							if (!empty($res)) $check = $res;
						} else $check = self::checkTemplatingType($data)
							? 'The templating class `' . $data . '` doesn\'t exists or is not class of `Crafteus\Environment\Support\Templating` or not implement `Crafteus\Contracts\TemplatingInterface` interface !'
							: null;
					}
					return $check;
					// return !$rule->errorExists() &&
					// ;
				}],
			],
		];
	}


	/**
	 * Check templating data type.
	 *
	 * @param mixed $data
	 *
	 * @return bool
	 */
	private static function checkTemplatingType(mixed $data): bool
	{
		return
			is_string($data) &&
			(
				!class_exists($data) ||
				(
					!is_subclass_of($data, TemplatingInterface::class) &&
					!is_subclass_of($data, Templating::class)
				)
			);
	}


	/**
	 * Check the compliance of the template data.
	 *
	 * @throws \Crafteus\Exceptions\InvalidTemplateDataException
	 *         If the data does not comply with the rules.
	 *
	 * @return void
	 */
	public function compliantData(): void
	{
		$compliant = Helper::compliantArray(
			rules: $this->getDataRule(),
			data: $this->current_data ?? []
		);

		if ($compliant->check()->errorExists()) {
			throw new InvalidTemplateDataException(
				$this->getTemplateName(),
				validation_errors: $compliant->getErrors(),
				code: 4402
			);
		}
	}


	/**
	 * Check the compliance of the template configuration.
	 *
	 * @return static
	 *
	 * @throws \Crafteus\Exceptions\TemplateValidationException
	 *         If the configuration does not comply with the rules.
	 */
	public function compliantConfig(): static
	{
		$compliant = Helper::compliantArray(
			rules: self::defaultRuleConfig($this->getConfigRule()),
			data: $this->getConfig()
		);

		if ($compliant->check(message: [
			'required' => 'The class property `:name` is required',
			'type' => 'The class property `:name` type is not available :available_type',
			'empty' => 'The class property `:name` is empty',
			'verify' => 'The class property `:name` has not available value',
			'verify.class-string' => 'The key `:name` class is not available :available_classes',
		])->errorExists()) {
			throw new TemplateValidationException(
				$compliant->getErrors(),
				"Please check the validity of the properties in the template class `"
					. get_class($this)
					. "`.",
				code: 4406
			);
		}

		return $this;
	}
}
