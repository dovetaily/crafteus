<?php

namespace Crafteus\Exceptions;

class UnableToResolveTemplateBasenameException extends BaseException
{
	public function __construct(
		string $template_name,
		int $code = 4007,
		\Throwable|null $previous = null
	) {
		$message = "Unable to resolve the basename for template "
			. $template_name
			. ": either a foundation name or a basename transformation closure must be defined.";

		parent::__construct(
			$message,
			$code,
			$previous
		);
	}
}
