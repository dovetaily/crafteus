<?php

namespace Crafteus\Exceptions;

class FileLoadStubException extends BaseException
{
	public function __construct(
		public readonly string $file_path,
		string|null $message = null,
		int $code = 5000,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			$message ?? "Unable to load stub file `{$this->file_path}`."
				. ($previous
					? "\n" . $previous->getMessage()
					: ''
				),
			$code,
			$previous
		);
	}
}
