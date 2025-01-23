<?php
namespace Crafteus\Exceptions;

class TemplateConfigException extends BaseException {
	public function __construct(string $class, string $key, string $template, int $code = 4001, \Throwable $previous = null) {
		$message = "Erreur dans la classe `$class`. Clé `$key` invalide pour le template `$template`." . (!is_null($previous) ? "Détails : " . $previous->getMessage() : '');
		parent::__construct($message, $code, $previous);
	}
}
