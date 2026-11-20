<?php

namespace Crafteus\Exceptions;

class EcosystemMissingMethodException extends BaseException
{
	public function __construct(
		string $class,
		string $method,
		int $code = 3000,
		\Throwable|null $previous = null
	) {
		// $message = "La classe `$class` ne possède pas la méthode `$method`.";
		parent::__construct(
			"Class {$class} must therefore be implement the remaining method `{$class}::{$method}`",
			$code,
			$previous
		);
	}
}
