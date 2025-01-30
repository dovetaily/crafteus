<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Template;

class InvalidTemplatePropertyException extends BaseException
{
	public function __construct(string|int $template_name, string|null $message = null, int $code = 4006, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The configuration of template `$template_name` contains an invalid key. Key names must follow PHP variable naming conventions.", $code, $previous);
	}
}
