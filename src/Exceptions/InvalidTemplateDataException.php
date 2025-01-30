<?php

namespace Crafteus\Exceptions;

class InvalidTemplateDataException extends BaseException
{
	public function __construct(string|null $message = null, int $code = 4002, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? 'Template data does not comply.', $code, $previous);
	}
}
