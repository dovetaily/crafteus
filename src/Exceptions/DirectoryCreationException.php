<?php

namespace Crafteus\Exceptions;

use Crafteus\Support\Helper;

class DirectoryCreationException extends BaseException
{
	public function __construct(string $directory, string|null $message = null, int $code = 9001, \Throwable|null $previous = null)
	{
		parent::__construct($message ?? "An error occurred while creating the directory " . Helper::redText('"' . $directory . '"', true) . ". Please check if write permissions are granted and if the directory name is valid.", $code, $previous);
	}
}
