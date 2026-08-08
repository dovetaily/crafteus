<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Template;

class InvalidTemplatePropertyException extends BaseException
{
	public function __construct(string|int $template_name, ?array $unauthorized_keys = null, string|null $message = null, int $code = 4006, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The configuration of template `$template_name` contains an invalid key. Key names must follow PHP variable naming conventions." 
			. ($unauthorized_keys 
				? "\n Unauthorized Keys : " . implode(", ", $unauthorized_keys) 
				: ""
			)
			. ($previous ? "\n" . $previous->getMessage() : "")
		, $code, $previous);
	}
}
