<?php

namespace Crafteus\Exceptions;

use Crafteus\Support\Helper;

class InvalidTemplateDataException extends BaseException
{
	public readonly array|null $validation_errors;
 
	public function __construct(array|null $validation_errors = null, string|null $message = null, int $code = 4002, \Throwable|null $previous = null)
	{
		$this->validation_errors = $validation_errors;

		parent::__construct(($message ?? 'Template data does not comply.') . ($validation_errors ? "\n" . Helper::redText(print_r($validation_errors, true)) : ''), $code, $previous);
	}
}
