<?php

namespace Crafteus\Support;

use Crafteus\Support\Traits\Errors;
use Crafteus\Support\Traits\RuleOverload;
use Crafteus\Support\Traits\RuleVerify;
use Crafteus\Support\Traits\RuleCheck;

/**
 * @phpstan-type TRuleTypes (
 *   'string'|
 *   'integer'|'int'|'numeric'|'double'|
 *   'object'|
 *   'function'|'callable'|
 *   'boolean'|'bool'
 * ) | string
 *
 * @phpstan-type TRuleVerifyCallable \Closure(
 *   mixed $data,
 *   bool $data_exists,
 *   \Crafteus\Support\Rule $rule,
 *   array<\Closure|object> $verification_method
 * ) : string|null|array
 *
 * A rule class for validating data based on various criteria
 * such as required status, type, emptiness, and verification.
 */
class Rule
{
	use Errors, RuleOverload, RuleVerify, RuleCheck;

	/**
	 * The name of the rule.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Whether the rule is required.
	 */
	protected bool $required;

	/**
	 * The type(s) that the data must conform to.
	 *
	 * @var array<TRuleTypes>|null
	 */
	protected array|null $type = null;

	/**
	 * Whether the rule checks for empty values.
	 *
	 * @var bool
	 */
	protected bool $empty;

	/**
	 * The verification method(s) to apply.
	 *
	 * @var array<string|TRuleVerifyCallable>|null
	 */
	protected array|null $verify = null;

	/**
	 * A message template for error reporting.
	 *
	 * @var array<string,string>
	 */
	public $message = [];

	/**
	 * Rule constructor.
	 *
	 * @param string $name
	 * @param bool $required
	 * @param TRuleTypes|array<TRuleTypes>|null $type
	 * @param bool $empty
	 * @param string|TRuleVerifyCallable|array<string|TRuleVerifyCallable>|null $verify
	 * @param array $message
	 */
	public function __construct(
		string $name,
		bool $required = false,
		array|string|null $type = null,
		bool $empty = false,
		array|string|\Closure|null $verify = null,
		array $message = []
	) {

		$this->name = $name;

		$this->setRequired($required);
		$this->setType($type);
		$this->setEmpty($empty);
		$this->setVerify($verify);

		$this->message = array_merge(static::defaultMessage(), $message);
	}

	/**
	 * Returns a default error message template for various
	 * rule types.
	 */
	public static function defaultMessage()
	{
		return [
			'required' => 'The key `:name` is required',
			'type' => 'The key `:name` type is not available :available_type',
			'empty' => 'The key `:name` is empty',
			'verify' => 'The key `:name` has not available value',
			'verify.class-string' => 'The key `:name` class object is not available :available_classes',
		];
	}

	/**
	 * Adds an error to the rule's error list.
	 *
	 * @param string|int $key Key for organizing errors (e.g., 'required', 'type').
	 * @param mixed $message The error message or an object of errors.
	 *
	 * @return void
	 */
	protected function addError(string|int $key, mixed $message): void
	{
		if (isset($this->errors[$key])) {
			if (is_array($this->errors[$key]))
				$this->errors[$key][] = $message;
			else
				$this->errors[$key] = [$this->errors[$key], $message];
		} else $this->errors[$key] = $message;
	}

	/**
	 * Formats a message by replacing placeholders with provided values.
	 *
	 * @param string|int $key The message key (e.g., 'required', 'type').
	 * @param string|null $message Optional custom message to use instead of the default.
	 * @param array $replace_values Array containing replacement values for placeholders like `:name`.
	 *
	 * @return string The formatted message string.
	 */
	public function formatMessage(string|int $key, ?string $message = null, array $replace_values = []): string
	{
		$replace_values = [':name' => $this->name, ...$replace_values];

		if (is_null($message) && isset($this->message[$key])) {
			$message = $this->message[$key];
		}

		if (is_string($message)) {
			foreach ($replace_values as $key_ => $value)
				$message = str_replace($key_, $value, $message);

			return $message;
		} else
			throw new \Exception("Error Processing Request", 1);
	}

	public static function baseTemplateRule()
	{
		return [
			'path' => [
				'type' => 'string|array',
				'required' => true,
				'empty' => false,
			],
			'stub_file' => [
				'type' => 'string|array',
				'required' => true,
				'empty' => false,
			],
			'generate' => [
				'type' => 'bool',
				'required' => false,
			],
			'keyword_class' => [
				'type' => 'string',
				'required' => true,
			],
			'keywords' => [
				'type' => 'array',
			]
		];
	}
}
