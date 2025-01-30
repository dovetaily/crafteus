<?php

namespace Crafteus\Exceptions;

class StubAlreadyExistsException extends BaseException
{
	public function __construct(string|int $key_path, string|int $template_name, string|null $message = null, int $code = 5000, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The Stub for path key \"$key_path\" is already exists in template `$template_name` !", $code, $previous);
	}
}
