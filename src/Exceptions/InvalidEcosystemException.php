<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Ecosystem;

class InvalidEcosystemException extends BaseException
{
	public function __construct(string $ecosystemClass, int $code = 2000, \Throwable|null $previous = null)
	{
		$message = "The ecosystem class `" . $ecosystemClass . "` is invalid. It must exist and extend the `" . Ecosystem::class . "` class.";
		parent::__construct($message, $code, $previous);
	}
}
