<?php

namespace Crafteus\Exceptions;

class FoundationAlreadyExistsException extends BaseException
{
	public function __construct(
		public readonly string $foundationName,
		$code = 2000,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			"The foundation App `{$foundationName}` is already used, create another app or change (key name).",
			$code,
			$previous
		);
	}
}
