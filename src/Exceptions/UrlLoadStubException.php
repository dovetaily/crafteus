<?php

namespace Crafteus\Exceptions;

class UrlLoadStubException extends BaseException
{
	public function __construct(
		public readonly string $url,
		string|null $message = null,
		int $code = 5000,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			$message ?? "Unable to retrieve stub resource from URL `{$this->url}`."
				. ($previous
					? "\n" . $previous->getMessage()
					: ''
				),
			$code,
			$previous
		);
	}
}
