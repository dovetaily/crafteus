<?php

namespace Crafteus\Environment\Support;

use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Stub;
use Crafteus\Support\Helper;
use Stringable;

/**
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 * @template T_Stub of \Crafteus\Environment\Stub
 */
class Templating
{

	/**
	 * Stub instance.
	 *
	 * @var T_Stub
	 */
	protected readonly Stub $stub;

	/**
	 * Holders values to replace.
	 *
	 * @var array<string,\Stringable|string>|null
	 */
	protected ?array $holders = null;

	/**
	 * Current stub content.
	 *
	 * @var string|null
	 */
	private ?string $current_content;

	/**
	 * Constructs Templating instance.
	 *
	 * @param T_Stub $stub
	 * @param string|null $current_content
	 *
	 */
	public function __construct(Stub $stub, ?string $current_content = null)
	{
		$this->stub = $stub;
		$this->current_content = $current_content ?? $stub->getCurrentContent();
	}





	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return T_Ecosystem|null Ecosystem instance.
	 *
	 */
	public function getEcosystem(): ?Ecosystem
	{
		return $this->stub->getEcosystem();
	}

	/**
	 * Get stub instance.
	 *
	 * @return T_Stub
	 *
	 */
	public function getStub(): Stub
	{
		return $this->stub;
	}

	/**
	 * Retrieves the configuration of the Stub.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param \Closure(mixed $config):bool|null $check_type
	 *
	 * @return mixed The Stub's configuration.
	 *
	 */
	public function getConfig(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null
	): mixed {
		return $this->stub->getConfig($key, $default_value, $check_type);
	}





	/**
	 * Get current content.
	 *
	 * @return string|null
	 *
	 */
	public function getCurrentContent(): ?string
	{
		return $this->current_content;
	}

	/**
	 * Set current content.
	 *
	 * @param string|null $current_content
	 *
	 * @return static
	 *
	 */
	public function setCurrentContent(?string $current_content): static
	{
		$this->current_content = $current_content;
		return $this;
	}





	/**
	 * Get an holder value.
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 *
	 */
	public function getHolder(string $key)
	{
		return $this->holders !== null && array_key_exists($key, $this->holders)
			? $this->holders[$key]
			: false;
	}

	/**
	 * Set an holder value.
	 *
	 * @param string|int $key
	 * @param \Stringable|string|null $value
	 *
	 * @return static
	 *
	 */
	public function setHolder(string|int $key, \Stringable|string|null $value): static
	{
		$this->holders[$key] = $value ?? '';

		return $this;
	}

	/**
	 * Get holders values.
	 *
	 * @return array<string,\Stringable|string>|null
	 *
	 */
	public function getHolders(): ?array
	{
		return $this->holders;
	}

	/**
	 * Set holders values.
	 *
	 * @param array|null $value
	 *
	 * @return static
	 *
	 */
	public function setHolders(array|null $value): static
	{
		$this->holders = $value;

		return $this;
	}





	/**
	 * Run script.
	 *
	 * @return void
	 *
	 */
	public function run(): void
	{
		if (!is_null($this->getHolders())) {
			$this->setCurrentContent(
				Templating::__replacer(
					$this->getHolders()
						?? [],
					$this->getCurrentContent()
				)
			);

			$this->applyContent();
		}
	}

	/**
	 * Apply current content to Stub content file.
	 *
	 * @return bool
	 *
	 */
	public function applyContent(): bool
	{
		return $this->stub
			->setCurrentContent($this->getCurrentContent())
			->applyContentFile()
		;
	}

	/**
	 * Replaces keys in the given array with their values in a text string.
	 *
	 * @param array<string,\Stringable|string> $data Associative array of replacements.
	 * @param string $text The text to modify.
	 *
	 * @return string The modified text.
	 */
	public static function __replacer(array $data, string $text): string
	{
		foreach ($data as $key => $value) {
			if (is_string($value) || $value instanceof Stringable) {
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
