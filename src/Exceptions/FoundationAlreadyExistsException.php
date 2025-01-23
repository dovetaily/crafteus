<?php

namespace Crafteus\Exceptions;

class FoundationAlreadyExistsException extends BaseException
{
	private string $foundationName;

	public function __construct(string $foundationName, $code = 2000, \Throwable $previous = null)
	{
		$this->foundationName = $foundationName;
		parent::__construct(
			"The foundation App `" . $foundationName . "` is already used, create another app or change (key name).",
			$code,
			$previous
		);
	}

	public function getFoundationName(): string
	{
		return $this->foundationName;
	}

}
