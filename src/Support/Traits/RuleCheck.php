<?php

namespace Crafteus\Support\Traits;

trait RuleCheck
{

	/**
	 * Returns the list of checks to
	 * perform (required, type, empty, verify).
	 */
	public static function checkList()
	{
		return [
			'required',
			'type',
			'empty',
			'verify',
		];
	}

	/**
	 * Executes all configured checks on the provided data.
	 *
	 * @param mixed|null $data The data to validate.
	 * @param bool $data_exists Whether the data exists (important for required checks).
	 * @param array|null $list Optional list of checks to run; defaults to all checks.
	 * @param mixed ...$args Additional arguments passed to each check method.
	 *
	 * @return static
	 */
	public function check(mixed $data = null, bool $data_exists = true, ?array $list = null, mixed ...$args): static
	{
		foreach ($list ?? self::checkList() as $method) {
			if (is_callable($method)) $method($data, $this);
			else {
				$method = 'initCheck' . ucfirst($method);
				if (method_exists($this, $method)) {
					$this->$method(...[$data, $data_exists, ...$args]);
				} else throw new \Exception("Error Processing Request ====> \$this->" . $method, 1);
			}
		}

		return $this;
	}


	/**
	 * Checks if the rule's required condition is met.
	 *
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return bool True if the rule is not required or
	 * if data exists and the rule is required.
	 */
	public function checkRequired(bool $data_exists = true): bool
	{
		return !$this->getRequired() || ($this->getRequired() && $data_exists);
	}

	/**
	 * Initializes the required check for this rule.
	 *
	 * @param mixed $data The data to check.
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return void
	 */
	public function initCheckRequired($data, bool $data_exists = true): void
	{
		if (!$this->checkRequired($data_exists)) {
			$this->addError('required', $this->formatMessage('required'));
		}
	}

	/**
	 * Checks if the provided data matches the specified type(s).
	 *
	 * @param mixed $data The data to validate.
	 * @param string $type The type to check against (used internally).
	 * @param string|null $data_type
	 *
	 * @return bool True if the data matches the type.
	 */
	public static function checkSingleType(mixed $data, string $type, ?string &$data_type = null): bool
	{
		$data_type ??= strtolower(gettype($data));

		$type = strtolower($type);

		return
			$data_type == $type ||
			(
				$type == 'function' &&
				!is_string($data) &&
				is_callable($data) &&
				$data_type == 'object'
			) ||
			(
				$type == 'int' &&
				$data_type == 'intenger'
			) ||
			(
				$type == 'float' &&
				$data_type == 'double'
			) ||
			(
				$type == 'bool' &&
				$data_type == 'boolean'
			) ||
			(
				$type == 'callable' &&
				is_callable($data)
			) ||
			(
				$type == 'numeric' &&
				in_array(
					$data_type,
					['string', 'double', 'integer']
				) &&
				(
					is_numeric($data) || (
						is_string($data) &&
						((bool) preg_match('/^0x[0-9a-f]+$/i', $data))
					)
				)
			);
	}

	/**
	 * Checks if the provided data matches
	 * any of the specified type(s).
	 *
	 * @param mixed $data The data to validate.
	 * @param array $types Array of types to check against.
	 *
	 * @return bool True if the data matches at least one type.
	 */
	public static function checkTypes(mixed $data, array $types): bool
	{
		$check = false;

		$data_type = strtolower(gettype($data));

		foreach ($types as $type) {

			if (self::checkSingleType($data, $type, $data_type)) {
				$check = true;
				break;
			}

			// $type = strtolower($type);

			// if(
			// 	$data_type == $type ||
			// 	(
			// 		$type == 'function' &&
			// 		!is_string($data) &&
			// 		is_callable($data) &&
			// 		$data_type == 'object'
			// 	) ||
			// 	(
			// 		$type == 'int' &&
			// 		$data_type == 'intenger'
			// 	) ||
			// 	(
			// 		$type == 'float' &&
			// 		$data_type == 'double'
			// 	) ||
			// 	(
			// 		$type == 'bool' &&
			// 		$data_type == 'boolean'
			// 	) ||
			// 	(
			// 		$type == 'callable' &&
			// 		is_callable($data)
			// 	) ||
			// 	(
			// 		$type == 'numeric' && in_array($data_type, ['string', 'double', 'integer']) &&
			// 		(
			// 			is_numeric($data) || (
			// 				is_string($data) &&
			// 				((bool) preg_match('/^0x[0-9a-f]+$/i', $data))
			// 			)
			// 		)
			// 	)
			// ){
			// 	$check = true;
			// 	break;
			// }
		}

		return $check;
	}

	/**
	 * Checks if the provided data matches the rule's type(s).
	 *
	 * @param mixed $data The data to validate.
	 * @param bool $data_exists Whether the data exists (important for required checks).
	 *
	 * @return bool True if the data matches the type(s) or if no type check is configured.
	 */
	public function checkType(mixed $data, bool $data_exists = true): bool
	{
		if (!is_null($types = $this->getType()) && ($data_exists || ($this->getRequired() && !$data_exists))) {

			return self::checkTypes($data, is_string($types) ? [$types] : $types);

			// return !$check ? $this->getEmpty() && !$data_exists : true;
			// return !$check ? $this->checkEmpty($data, $data_exists) : true;
		}
		return true;
	}

	/**
	 * Initializes the type check for this rule.
	 *
	 * @param mixed $data The data to validate.
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return void
	 */
	public function initCheckType(mixed $data, bool $data_exists = true): void
	{
		if (!$this->checkType($data, $data_exists)) {
			$type = $this->getType();
			$this->addError('type', $this->formatMessage('type', replace_values: [':available_type' => '(' . implode(', ', $type) . ')']));
		}
	}


	/**
	 * Checks if the provided data is empty
	 * based on configured criteria.
	 *
	 * @param mixed $data The data to validate.
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return bool True if the data is considered empty.
	 */
	public function checkEmpty(mixed $data, bool $data_exists): bool
	{
		if ($data_exists || ($this->getRequired() && !$data_exists)) {
			if (!$this->getEmpty() && !$data_exists) {
				return false;
			} else if (!$this->getEmpty()) {
				if (is_callable($empty_value = $this->getEmpty())) {
					return $empty_value($data)
						? true
						: false;
				} else {
					$empty_values = [
						'',
						null,
						fn($v) => is_array($v) && empty($v)
					];

					$check = true;

					foreach ($empty_values as $empty_value) {
						if (is_callable($empty_value)) {
							$res = $empty_value($data);

							if ($res) {
								$check = false;
								break;
							}
						} else if ($data === $empty_value) {
							$check = false;
							break;
						}
					}

					return $check;
				}
			}
		}
		return true;
	}

	/**
	 * Initializes the empty check for this rule.
	 *
	 * @param mixed $data The data to validate.
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return void
	 */
	public function initCheckEmpty(mixed $data, bool $data_exists = true): void
	{
		if (!$this->checkEmpty($data, $data_exists)) {
			$this->addError('empty', $this->formatMessage('empty'));
		}
	}
}
