<?php

namespace Crafteus\Support\Traits;

trait Errors
{
	/**
	 * Errors accumulated during rule checking.
	 *
	 * @var array<string|\Stringable>
	 */
	protected array $errors = [];

	/**
	 * Checks if any errors have been recorded for this rule.
	 */
	public function errorExists(): bool
	{
		return !empty($this->getErrors());
	}

	/**
	 * Retrieves all accumulated errors for this rule.
	 *
	 * @return array<string|\Stringable>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Sets the errors object for this rule.
	 *
	 * @param array $errors The errors to set.
	 *
	 * @return void
	 */
	protected function setErrors(array $errors): void
	{
		$this->errors = $errors;
	}

	/**
	 * Adds an error to the rule's error list.
	 *
	 * @param mixed $error
	 * @param string|int|null|null $key
	 *
	 * @return void
	 */
	protected function addError(mixed $error, string|int|null $key = null): void
	{
		if ($key) $this->errors[$key] = $error;
		else $this->errors[] = $error;
	}
}
