<?php

namespace Crafteus\Exceptions;

class EcosystemMissingMethodException extends BaseException {
	public function __construct(string $class, string $method, int $code = 0, \Throwable $previous = null) {
		$message = "La classe `$class` ne possède pas la méthode `$method`.";
		parent::__construct($message, $code, $previous);
	}
}