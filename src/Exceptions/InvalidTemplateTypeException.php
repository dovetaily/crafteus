<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Template;

class InvalidTemplateTypeException extends BaseException
{
	public function __construct(string|int $template_name, string $ecosystem, string|null $message = null, int $code = 4004, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The `$template_name` template of the `" . $ecosystem . "` ecosystem must be a class inherited from `" . Template::class . "` or a table containing the configurations of a template according to Crafteus.", $code, $previous);
	}
}
