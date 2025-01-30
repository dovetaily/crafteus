<?php

namespace Crafteus\Exceptions;

use Crafteus\Environment\Ecosystem;

class MissingConfigKeyException extends BaseException
{
	public function __construct(string|int $template_name, string|null $message = null, int $code = 4003, \Throwable|null $previous = null)
	{
		parent::__construct(
			$message ?? "Error in the Ecosystem `" . Ecosystem::class . "` class. The key `config` is absent in the `$template_name` template.",
			$code,
			$previous
		);
	}
}
