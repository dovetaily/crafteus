<?php
namespace Crafteus\Exceptions;

class TemplateConfigException extends BaseException
{
	public function __construct(string $class, string $key, string $template, int $code = 4001, \Throwable|null $previous = null)
	{
		$message = "Error in class `$class`: The key `$key` is invalid for template `$template`." . (!is_null($previous) ? " Details: " . $previous->getMessage() : '');
		parent::__construct($message, $code, $previous);
	}
}
