<?php

namespace Crafteus\Exceptions;

class TemplateValidationException extends BaseException
{

	public readonly array $validation_errors; 

	public function __construct(array $validation_errors, string|null $message = null, int $code = 4005, \Throwable|null $previous = null)
	{
		$this->validation_errors = $validation_errors;

		parent::__construct($message . "\n\n\033[31m" . print_r($validation_errors, true) . "\033[0m\n\n", $code, $previous);
	}
}
