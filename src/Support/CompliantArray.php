<?php
namespace Crafteus\Support;

use Crafteus\Support\Rule;
use Crafteus\Support\Traits\Errors;

class CompliantArray
{

	use Errors;

	public array $rules = [];

	public array $default_data;

	public array $data;

	protected array $errors = [];

	/**
	 * [Description for __construct]
	 *
	 * @param array $rules
	 * @param array $data
	 * @param array|callable $default_data
	 * 
	 */
	public function __construct(array $rules, array $data, array|callable $default_data = [])
	{
		$this->rules = $rules;
		$this->data = $data;
		$this->default_data = $default_data;
	}

	public function check(?string $prefix = null, ?array $message = []) {

		$data = $this->getData();

		foreach ($this->rules as $key => $value) {

			$more_compliant = null;

			if(isset($value['data'])){
				$more_compliant = $value['data'];
				unset($value['data']);
			}

			$rule = new Rule(...['name' => $key, ...$value, 'message' => $message ?? []]);
			
			$this->rules[$key] = $rule;

			$data_exist = array_key_exists($key, $data);

			$current_data = array_key_exists($key, $data) ? $data[$key] : null;

			$rule->check($current_data, $data_exist, null, $key);

			$key_error = (!is_null($prefix) ? $prefix . '.' : '') . $key;

			if($rule->errorExists())
				$this->addError($rule->getErrors(), $key_error);
				// $this->errors[$key_error] = $rule->errors;

			if(is_array($more_compliant) && !empty($more_compliant)){

				$compliant = (new CompliantArray(
					rules : $more_compliant,
					data : $current_data ?? []
				))->check($key_error);

				$this->setErrors(array_merge($this->errors, $compliant->errors));
				// $this->errors = array_merge($this->errors, $compliant->errors);

			}

		}
		return $this;
	}

	public function getData() : array {
		$default_data = $this->default_data;
		if(is_callable($default_data)){
			$result = $default_data(...['data' => $this->data]);
			if(is_array($result))
				$default_data = $result;
			return $default_data;
		}
		return array_merge($default_data, $this->data);
	}

}