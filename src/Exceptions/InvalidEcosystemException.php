<?php

namespace Crafteus\Exceptions;

use Nucleft\Environment\Ecosystem;

class InvalidEcosystemException extends BaseException
{
	public function __construct(string $ecosystemClass, int $code = 3000, \Throwable $previous = null)
	{
		$message = "The ecosystem class `" . $ecosystemClass . "` is invalid. It must exist and extend the `" . Ecosystem::class . "` class.";
		parent::__construct($message, $code, $previous);
	}
}