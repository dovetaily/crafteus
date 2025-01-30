<?php

namespace Crafteus\Exceptions;

class BaseException extends \Exception
{

	public function __construct(string $message = "", int $code = 90, \Throwable|null $previous = null)
	{
		parent::__construct("[" . $code . "] - " . $message, $code, $previous);
	}

}
