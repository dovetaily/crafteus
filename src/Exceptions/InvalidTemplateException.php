<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Template;

class InvalidTemplateException extends BaseException
{
	public function __construct(string|int $template_name, string|null $message = null, int $code = 3001, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The template `$template_name` does not inherit `". Template :: class."`.", $code, $previous);
	}
}
