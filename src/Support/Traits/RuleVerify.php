<?php

namespace Crafteus\Support\Traits;

use Crafteus\Support\Rule;

/**
 * @phpstan-import-type TRuleVerifyCallable from \Crafteus\Support\Rule
 *
 * @phpstan-type TRuleVerifyMethod
 *   'file_exists'
 *   |'class-string'
 *   |'array<file_exists>'
 *   |'array<is_readable>'
 *   |'array'
 *   |'regexp'
 *
 * @phpstan-type TRuleVerificationClosure \Closure(
 *   mixed $data,
 *   bool $data_exists,
 *   \Crafteus\Support\Rule $rule,
 *   string $verify,
 *   string $method
 * ): array<string>|string|null
 *
 * @phpstan-type TVerificationMethodRegistry array<string,TRuleVerificationClosure|array{
 *   detect: \Closure(string): bool,
 *   call: TRuleVerificationClosure
 * }>
 */
trait RuleVerify
{

	/**
	 * @var TVerificationMethodRegistry|null
	 */
	protected static ?array $verification_method = null;

	/**
	 * Gets the required status of the rule.
	 *
	 * @return bool
	 */
	abstract public function getRequired(): bool;

	/**
	 * Adds an error to the rule's error list.
	 *
	 * @param string|int $key
	 *        Key for organizing errors
	 *        (e.g., 'required', 'type').
	 * @param mixed $message
	 *        The error message or an object of errors.
	 *
	 * @return void
	 */
	abstract protected function addError(string|int $key, mixed $message): void;

	/**
	 * Gets the verification method(s)
	 * configured for this rule.
	 *
	 * @return string
	 *         |TRuleVerifyCallable
	 *         |array<string|TRuleVerifyCallable>
	 *         |null
	 */
	abstract public function getVerify(): array|\Closure|null;

	/**
	 * Formats a message by replacing placeholders with
	 * provided values.
	 *
	 * @param string|int $key
	 *        The message key (e.g., 'required', 'type').
	 * @param string|null $message
	 *        Optional custom message to use
	 *        instead of the default.
	 * @param array $replace_values
	 *        Array containing replacement values for
	 *        placeholders like `:name`.
	 *
	 * @return string The formatted message string.
	 */
	abstract public function formatMessage(string|int $key, ?string $message = null, array $replace_values = []): string;

	/**
	 * Checks if the provided data matches
	 * any of the specified type(s).
	 *
	 * @param mixed $data The data to validate.
	 * @param array $types Array of types to check against.
	 *
	 * @return bool True if the data matches at least one type.
	 */
	abstract public static function checkTypes(mixed $data, array $types): bool;

	/**
	 * Performs verification checks on the provided data
	 * using configured verification methods.
	 *
	 * @param mixed $data The data to validate.
	 * @param mixed $data_exists Whether the data exists.
	 *
	 * @return array{
	 *   state:bool,
	 *   errors?:array<string|\Stringable>|string|\Stringable
	 * } An array containing the verification state and any
	 *   errors encountered.
	 *
	 * @throws \Exception
	 */
	public function checkVerify(mixed $data, bool $data_exists = true/* , $key = null */)
	{
		if ($data_exists || ($this->getRequired() && !$data_exists)) {

			if (!is_null($verify_values = $this->getVerify())) {

				$check = ['state' => true, 'errors' => []];

				foreach ($verify_values as $verify) {

					if (!is_string($verify) && is_callable($verify)) {

						$error = $verify($data, $data_exists, $this, self::verficationMethods());

						if (!empty($error)) {
							$check = [
								'state' => false,
								'errors' => is_array($error)
									? $error
									: [$error]
							];
						}
					} elseif (is_string($verify)) {

						$errors = [];

						$recognized = false;

						foreach (self::verficationMethods() as $method => $val) {

							$error = null;

							if ($verify == $method && is_callable($val)) {

								$error = $val($data, $data_exists, $this, $verify, $method/* , $key */);

								$recognized = true;
							} else if (
								is_array($val) &&
								isset($val['detect']) &&
								isset($val['call']) &&
								is_callable($val['detect']) &&
								is_callable($val['call']) &&
								$val['detect']($verify)
							) {
								$recognized = true;
								$error = $val['call']($data, $data_exists, $this, $verify, $method/* , $key */);
							}


							if (!empty($error)) {
								$errors = array_merge($errors, is_array($error)
									? $error
									: [$error]);
							}

							if (!empty($error)) break;
						}

						if (!$recognized) {
							throw new \Exception("The verification of `{$verify}` is not recognized", 1);
						}

						$check = ['state' => empty($errors), 'errors' => empty($errors) ? [] : $errors];
					}

					if (!array_key_exists('state', $check))
						throw new \Exception("Error Processing Request", 1);

					if (!$check['state'])
						break;
				}

				return $check;
			}
		}

		return ['state' => true];
	}

	/**
	 * Retrieves the registry of verification methods
	 * available for validation.
	 *
	 * @param bool $forceInitialization
	 *        If true, forces re-initialization
	 *        of the verification methods registry.
	 *
	 * @return TVerificationMethodRegistry
	 */
	public static function verficationMethods(bool $forceInitialization = false): array|null
	{
		if (self::$verification_method === null || $forceInitialization) self::$verification_method = [

			'file_exists' => function ($data, $data_exists, $rule, $verify, $method) {

				if ($rule->errorExists()) return null;

				$verif = file_exists($data);

				return !$verif ? 'The data key `:name` file `' . $data . '` doesn\'t exists !' : null;
			},

			'class-string' => [
				'detect' => fn($v)
				=> !in_array(
					$v,
					array_filter(
						array_keys(self::$verification_method ?? []),
						fn($method) => (bool) preg_match('/^class\\-string.*/i', $method)
					)
				) && preg_match('/^class\\-string<[^\\>\\<]+>$/i', $v),
				'call' => function ($data, $data_exists, Rule $rule, $verify) {

					$check = null;

					if ($data_exists && is_string($data)) {

						preg_match('/^class\\-string<([^\\>\\<]+)>$/i', $verify, $m);

						$class_names = explode('|', end($m));

						$check = $rule->formatMessage('verify.class-string', null, [':available_classes' => end($m)]);

						foreach ($class_names as $class_name) {
							// if(trim($data, '\\ \n\r\t\v\0') == trim($class_name, '\\ \n\r\t\v\0')){
							if (is_subclass_of($data, $class_name)) {
								$check = null;
								break;
							}
						}
					}

					return $check;
				}
			],

			'array<file_exists>' => function ($data, $data_exists, $rule, $verify, $method) {

				$check = null;

				if (!$rule->errorExists()) {

					if (is_array($data)) {

						$res = [];

						foreach ($data as $dt) {

							if (!file_exists($dt)) {
								$res[] = "The file `{$dt}` doesn\'t exists!";
							}
						}

						if (!empty($res)) $check = $res;
					}

					// else $check = !file_exists($data)
					// 	? 'The stub file `' . $data . '` doesn\'t exists !'
					// 	: null
					// ;
				}

				return $check;
			},

			'array<is_readable>' => function ($data, $data_exists, $rule, $verify, $method) {

				$check = null;

				if (!$rule->errorExists()) {

					if (is_array($data)) {

						$res = [];

						foreach ($data as $dt) {
							if (!is_readable($dt))
								$res[] = "The file `{$dt}` is not readable!";
						}

						if (!empty($res)) $check = $res;
					}
				}

				return $check;
			},
			'array' => [

				'detect' => fn($v)
				=> !in_array(
					$v,
					array_filter(
						array_keys(self::$verification_method ?? []),
						fn($method) => (bool) preg_match('/^array.*/i', $method)
					)
				) && preg_match('/^array<[^\\>\\<]+>$/i', $v),

				'call' => function ($data, $data_exists, $rule, $verify) {

					$check = null;

					// if($data_exists){

					if ($data_exists && is_array($data)) {

						// if(!is_array($data))

						// 	$check = 'The key `:name` type is not an array !';

						// else{

						preg_match('/^array<([^\\>\\<]+)>$/i', $verify, $m);

						$types = explode('|', end($m));

						foreach ($data as $value) {

							// if(!in_array(
							// 	(!is_string($value) && is_callable($value) && gettype($value) == 'object')
							// 		? 'function'
							// 		: gettype($value)
							// 	,
							// 	$types
							// )){

							if (!self::checkTypes($value, $types)) {

								// if(!in_array(gettype($value), $types)){

								$check = 'The key `:name` values has not only types : ' . implode(',', $types);
								break;
							}

							# code...

						}

						// }

					}

					return $check;
				}

			],

			'regexp' => [

				'detect' => fn($v) => preg_match('/^regexp\\:.*$/i', $v),

				'call' => function ($data, $data_exists, $rule, $verify) {

					$check = null;

					if ($data_exists) {

						preg_match('/^regexp\\:(.*)$/i', $verify, $m);

						$pattern = end($m);

						if (!empty($pattern)) {
							if (!((bool) preg_match($pattern, $data))) {
								$check = 'The key `:name` value has not respect regexp : ' . $pattern;
							}
						}
					}

					return $check;
				}

			],

			// 'sort' => function($v){},

		];

		return self::$verification_method;
	}

	/**
	 * Initializes the verification check for this rule.
	 *
	 * @param mixed $data The data to validate.
	 * @param bool $data_exists Whether the data exists.
	 *
	 * @return void
	 */
	public function initCheckVerify(mixed $data, bool $data_exists = true)
	{
		$res = $this->checkVerify($data, $data_exists);

		if (!$res['state']) {

			$this->addError('verify', $this->formatMessage('verify'));

			if (isset($res['errors']) && !empty($res['errors'])) {

				$err = is_string($res['errors']) ? [$res['errors']] : (is_array($res['errors']) ? $res['errors'] : []);

				foreach ($err as $error) {
					$this->addError('verify', $this->formatMessage('verify', message: $error));
				}
			}
		}
	}
}
