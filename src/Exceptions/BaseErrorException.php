<?php

namespace Crafteus\Exceptions;

class BaseErrorException extends \ErrorException
{

	public function __construct(string|null $error_class = null, string $message = "", int $code = 91, int $severity = 1, string|null $filename = null, int|null $line = null, \Throwable|null $previous = null)
	{
		$message = "[" . $code . "]" . ($error_class 
			? (' | ' . $error_class) 
			: ''
		) . " - " . $message;
		parent::__construct($message, $code, $severity, $filename, $line, $previous);
	}

}
