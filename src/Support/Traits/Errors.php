<?php
namespace Crafteus\Support\Traits;

trait Errors
{
	protected array $errors = [];

	public function errorExists() : bool {
		return !empty($this->getErrors());
	}

	public function getErrors() : array {
		return $this->errors;
	}

	protected function setErrors(array $errors) : void {
		$this->errors = $errors;
	}

	protected function addError($error, string|int|null $key = null) : void {
		if($key) $this->errors[$key] = $error;
		else $this->errors[] = $error;
	}
}
