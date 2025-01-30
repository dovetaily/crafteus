<?php

namespace Crafteus\Exceptions;

class ProcessingRequestException extends BaseException
{
	public function __construct(string|null $message = null, int $code = 0, \Throwable $previous = null)
	{
		parent::__construct($message ?? "", $code, $previous);
	}
}
