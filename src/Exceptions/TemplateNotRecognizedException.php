<?php

namespace Crafteus\Exceptions;

use Exception;

class TemplateNotRecognizedException extends BaseException
{
	public function __construct(string $templateName, string $ecosystemClass, int $code = 4000, \Throwable $previous = null)
	{
		$message = "The template `" . $templateName . "` is not recognized in the ecosystem `" . $ecosystemClass . "`.";
		parent::__construct($message, $code, $previous);
	}
}