<?php

namespace Crafteus\Exceptions;

use Crafteus\Support\Helper;

class InvalidTemplateDataException extends BaseException
{
	public function __construct(
		string $template_name,
		public readonly array|null $validation_errors = null,
		string|null $message = null,
		int $code = 4002,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			($message ?? "The foundation data does not meet the requirements of the `{$template_name}` template.") . ($validation_errors
				? "\n" . Helper::redText(print_r($validation_errors, true))
				: ''
			),
			$code,
			$previous
		);
	}
}
