<?php

namespace Crafteus\Exceptions;

class FileNotReadableException extends BaseException
{
	public function __construct(string $file, string|null $message = null, int $code = 9000, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "The file `$file` already exists, but read permissions are not granted.", $code, $previous);
	}
}
