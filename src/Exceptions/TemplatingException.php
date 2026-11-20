<?php

namespace Crafteus\Exceptions;

class TemplatingException extends BaseException
{

	public function __construct(
		string $template_name,
		string $template_class,
		string|int $path_key,
		?string $templating_class = null,
		string|null $message = null,
		int $code = 6000,
		\Throwable|null $previous = null
	) {
		parent::__construct(
			$message ?? "Stub Error: An error occurred while templating the {$template_name} template. Template class: {$template_class}. Stub path key: "
				. $path_key
				. "."
				. ($templating_class
					? "Templating Class: {$templating_class}"
					: ""),
			$code,
			$previous
		);
	}
}
