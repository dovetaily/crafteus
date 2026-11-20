<?php

namespace Crafteus\Support\Traits;

/**
 * @phpstan-import-type TRuleTypes from \Crafteus\Support\Rule
 * @phpstan-import-type TRuleVerifyCallable from \Crafteus\Support\Rule
 */
trait RuleOverload
{
	/**
	 * Gets the required status of the rule.
	 *
	 * @return bool
	 */
	public function getRequired(): bool
	{
		return $this->required;
	}

	/**
	 * Sets the required status of the rule.
	 *
	 * @param bool $required Whether the rule is required.
	 *
	 * @return void
	 */
	public function setRequired(bool $required): void
	{
		$this->required = $required;
	}



	/**
	 * Gets the type(s) that the data must conform to.
	 *
	 * @return array<TRuleTypes>|null
	 */
	public function getType(): array|null
	{
		return $this->type;
	}

	/**
	 * Sets the type(s) that the data must conform to.
	 * Supports multiple types separated by `|`.
	 *
	 * @param TRuleTypes|array<TRuleTypes>|null $type The type or array of
	 * types (e.g., 'string', 'number', 'array').
	 *
	 * @return void
	 */
	public function setType(array|string|null $type): void
	{
		if (!is_null($type)) {
			$this->type = [];
			$types = is_array($type)
				? $type
				: (is_string($type)
					? explode('|', trim($type))
					: [$type]
				);
			foreach ($types as $type_) {
				$this->type = array_merge(
					$this->type,
					is_string($type_)
						? ($type_ == 'number' || $type_ == 'num' || $type_ == 'numeric'
							? ['intenger', 'double']
							: [$type_]
						)
						: [$type_]
				);
			}
		}
	}



	/**
	 * Gets the empty check status of the rule.
	 *
	 * @return bool
	 */
	public function getEmpty(): bool
	{
		return $this->empty;
	}

	/**
	 * Sets the empty check status of the rule.
	 *
	 * @param bool $empty Whether to check for empty values.
	 *
	 * @return void
	 */
	public function setEmpty(bool $empty): void
	{
		$this->empty = $empty;
	}



	/**
	 * Gets the verification method(s)
	 * configured for this rule.
	 *
	 * @return array<string|TRuleVerifyCallable>|null
	 */
	public function getVerify(): array|\Closure|null
	{
		return $this->verify;
	}

	/**
	 * Sets the verification method(s) for this rule.
	 * Supports multiple methods separated by `|`.
	 *
	 * @param string|TRuleVerifyCallable|array<string|TRuleVerifyCallable>|null $verify The verification method or
	 * array of methods (e.g., 'file_exists', 'regexp:pattern').
	 *
	 * @return void
	 */
	public function setVerify(array|string|\Closure|null $verify): void
	{
		if (!is_null($verify)) {
			$this->verify = is_array($verify)
				? $verify
				: (is_string($verify)
					// ? explode('|', trim($verify))
					? array_map(
						fn($v) => str_replace(',', '|', $v),
						explode(
							'|',
							preg_replace_callback(
								'/<[^<>]+>/',
								fn($value)
								=> str_replace('|', ',', $value[0]),
								$verify
							)
						)
					)
					: [$verify]
				);
		}
	}
}
