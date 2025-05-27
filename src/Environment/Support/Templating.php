<?php

namespace Crafteus\Environment\Support;

use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Stub;
use Crafteus\Support\Helper;

class Templating
{

	/**
	 * Current stub content.
	 *
	 * @var string|null
	 */
	private ?string $current_content;

	/**
	 * Stub instance.
	 *
	 * @var Stub
	 */
	protected readonly ?Stub $stub;

	/**
	 * Holders values to replace.
	 *
	 * @var array|null
	 */
	protected ?array $holders = null;

	/**
	 * Constructs Templating instance.
	 *
	 * @param Stub $stub
	 * @param string|null $current_content
	 * 
	 */
	public function __construct(Stub $stub, ?string $current_content = null) {
		$this->stub = $stub;
		$this->current_content = $current_content ?? $stub->getCurrentContent();
	}

	/**
	 * Get stub instance.
	 *
	 * @return Stub
	 * 
	 */
	public function getStub() : Stub {
		return $this->stub;
	}

	/**
	 * Set current content.
	 *
	 * @param string|null $current_content
	 * 
	 * @return Templating
	 * 
	 */
	public function setCurrentContent(?string $current_content) : Templating {
		$this->current_content = $current_content;
		return $this;
	}

	/**
	 * Get current content.
	 *
	 * @return string|null
	 * 
	 */
	public function getCurrentContent() : ?string {
		return $this->current_content;
	}

	/**
	 * Get an holder value.
	 *
	 * @param string|int $key
	 * 
	 * @return bool|mixed
	 * 
	 */
	public function getHolder(string|int $key) {
		return !is_null($this->holders) && isset($this->holders[$key])
			? $this->holders[$key]
			: false
		;
	}

	/**
	 * Get holders values.
	 *
	 * @return array|null
	 * 
	 */
	public function getHolders() : ?array {
		return $this->holders;
	}
	
	/**
	 * Run script.
	 *
	 * @return void
	 * 
	 */
	public function run() : void {
		if(!is_null($this->getHolders())){
			$this->setCurrentContent(Templating::__replacer($this->getHolders() ?? [], $this->getCurrentContent()));
			$this->applyContent();
		}
	}

	/**
	 * Apply current content to Stub content file.
	 *
	 * @return bool
	 * 
	 */
	public function applyContent() : bool {
		return $this->stub->setCurrentContent($this->getCurrentContent())->generateContentFile();
	}

	/**
	 * Replaces keys in the given array with their values in a text string.
	 *
	 * @param array<string, string> $data Associative array of replacements.
	 * @param string $text The text to modify.
	 * 
	 * @return string The modified text.
	 * 
	 */
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

	/**
	 * Retrieves the configuration of the Stub.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * 
	 * @return mixed The Stub's configuration.
	 * 
	 */
	public function getConfig(string|null $key = null, $default_value = null) {
		return $this->stub->getConfig($key, $default_value);
	}

	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return Ecosystem Ecosystem instance.
	 * 
	 */
	public function getEcosystem() : Ecosystem {
		return $this->stub->getEcosystem();
	}

}
