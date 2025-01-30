<?php

namespace Crafteus\Exceptions;

use Crafteus\Support\Helper;

class FileDeletionException extends BaseException
{
	public function __construct(string $file, string|null $message = null, int $code = 9002, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "Failed to delete the file '$file'.", $code, $previous);
	}
}
