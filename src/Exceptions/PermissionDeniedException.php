<?php

namespace Crafteus\Exceptions;

class PermissionDeniedException extends BaseException
{
	public function __construct(string $stub_file, string|null $message = null, int $code = 5001, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "Permission denied for read access on the stub file `$stub_file`.", $code, $previous);
	}
}
