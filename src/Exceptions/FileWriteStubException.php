<?php

namespace Crafteus\Exceptions;

class FileWriteStubException extends BaseException
{
	public function __construct(
		public readonly string $file_path,
		?string $message = null,
		int $code = 0,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			$message ?? "Unable to write stub file `{$this->file_path}`.",
			$code,
			$previous
		);
	}
}
