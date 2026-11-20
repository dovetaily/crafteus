<?php

namespace Crafteus\Exceptions;

class InvalidStubDefinitionException extends BaseException
{
	public function __construct(
		string|int $key_path,
		string|int $template_name,
		string|null $message = null,
		int $code = 5004,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			$message ?? "The Stub for path key '{$key_path}' in template '{$template_name}' must define at least a basename or an extension.\n" . (!is_null($previous)
				? $previous->getMessage()
				: ""
			),
			$code,
			$previous
		);
	}
}
