<?php

namespace Crafteus\Exceptions;

class PhpStubException extends BaseException
{
	public function __construct(string $origin_stub, string|null $message = null, int $code = 5003, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "An error occurred in the PHP stub file $origin_stub. Check its syntax and execution.", $code, $previous);
	}
}
