<?php

namespace Crafteus\Environment\Support;

use Crafteus\Environment\Stub;
use Crafteus\Support\Helper;

class Templating
{

	private ?string $current_content;

	public readonly ?Stub $stub;

	protected ?array $holders = null;

	public function __construct(Stub $stub, ?string $current_content = null) {
		$this->stub = $stub;
		$this->current_content = $current_content ?? $stub->getCurrentContent();
	}

	public function getStub() : Stub {
		return $this->stub;
	}

	public function setCurrentContent(?string $current_content) : Templating {
		$this->current_content = $current_content;
		return $this;
	}

	public function getCurrentContent() : ?string {
		return $this->current_content;
	}

	public function getHolder(string|int $key) {
		return !is_null($this->holders) && isset($this->holders[$key])
			? $this->holders[$key]
			: false
		;
	}

	public function getHolders() : ?array {
		return $this->holders;
	}
	
	public function run() : void {
		if(!is_null($this->getHolders())){
			$this->setCurrentContent(Templating::__replacer($this->getHolders() ?? [], $this->getCurrentContent()));
			$this->applyContent();
		}
	}

	public function applyContent() : bool {
		return $this->stub->setCurrentContent($this->getCurrentContent())->generateContentFile();
	}

	public static function __replacer(array $data, string $text) : string {
		foreach ($data as $key => $value) {
			if(is_string($value)){
				$text = str_replace(
					$key,
					$value,
					$text
				);
			}
		}
		return $text;
	}

}
