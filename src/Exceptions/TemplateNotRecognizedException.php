<?php

namespace Crafteus\Exceptions;

class TemplateNotRecognizedException extends BaseException
{
	public function __construct(string $templateName, string $ecosystemClass, int $code = 4000, \Throwable|null $previous = null)
	{
		$message = "The template `" . $templateName . "` is not recognized in the ecosystem `" . $ecosystemClass . "`.";
		parent::__construct($message, $code, $previous);
	}
}
