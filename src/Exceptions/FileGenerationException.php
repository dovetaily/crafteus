<?php

namespace Crafteus\Exceptions;

use Crafteus\Support\Helper;

class FileGenerationException extends BaseException
{
	public function __construct(string $file, string $directory, string|null $message = null, int $code = 5002, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "An error occurred with the file `$file`. Please check the file name and your access permissions for the file or its parent directory `$directory`.", $code, $previous);
	}
}
