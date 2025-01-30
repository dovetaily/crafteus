<?php

namespace Crafteus\Exceptions;


class DuplicateTemplateInstanceException extends BaseException
{
	public function __construct(string|int $template_name, string $ecosystem, string $fondation_name, string|null $message = null, int $code = 3002, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The `$template_name` template instance already exists in the `$ecosystem` ecosystem of the `$fondation_name` foundation.", $code, $previous);
	}
}
