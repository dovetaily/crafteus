<?php

namespace Crafteus\Support\Tests\Traits;

use Crafteus\Environment\App;
use Crafteus\Support\Tests\Mock;

/**
 * @phpstan-import-type TEcosystemTemplateConfigKeysPartial from \Crafteus\Environment\Ecosystem
 */
trait MockApp
{
	abstract protected function mock(mixed ...$args): Mock;

	abstract public function name(): string;

	protected ?App $app = null;

	/**
	 * Mock application configuration.
	 *
	 * @var bool|array{
	 *   foundation_names?: array<string>|int,
	 *   data_resolver?: \Closure(string $foundation_name, string|int $index, array|bool $withConfig):mixed|null,
	 *   template_config?: (\Closure(\Crafteus\Environment\Ecosystem $ecosystem):array<TEcosystemTemplateConfigKeysPartial>)|array<TEcosystemTemplateConfigKeysPartial>|null,
	 *   withConfig       ?: array|bool
	 * }
	 */
	protected array|bool $mock_app_args = false;

	protected function mockAppSetup(): void
	{
		$mock_app_args = $this->getMockAppArgs();

		if (is_array($mock_app_args)) {

			$this->initMockApp($mock_app_args);
		}
	}

	/**
	 * @param  array{
	 *   foundation_names ?: array<string>|int,
	 *   data_resolver    ?: \Closure(string $foundation_name, string|int $index, array|bool $withConfig)|null,
	 *   template_config?: (\Closure(\Crafteus\Environment\Ecosystem $ecosystem):array<TEcosystemTemplateConfigKeysPartial>)|array<TEcosystemTemplateConfigKeysPartial>|null,
	 *   withConfig       ?: array|bool
	 * } $mock_app_args
	 */
	protected function initMockApp(array $mock_app_args = []): self
	{

		$this->app = $this->mock()->app(
			$mock_app_args['foundation_names'] ?? 1,
			$mock_app_args['data_resolver'] ?? null,
			$mock_app_args['template_config'] ?? null,
			$mock_app_args['withConfig'] ?? true
		);

		return $this;
	}

	protected function mockAppTearDown(): void
	{
		$this->app = null;
	}

	/**
	 * @return  false|array{
	 *   foundation_names ?: array<string>|int,
	 *   data_resolver    ?: \Closure(string $foundation_name, string|int $index, array|bool $withConfig)|null,
	 *   template_config?: (\Closure(\Crafteus\Environment\Ecosystem|null $ecosystem = null):array<TEcosystemTemplateConfigKeysPartial>)|array<TEcosystemTemplateConfigKeysPartial>|null,
	 *   withConfig       ?: array|bool
	 * }
	 */
	protected function getMockAppArgs(): false|array
	{

		return $this->mock_app_args === true
			? []
			: ($this->mock_app_args['#' . $this->name()] ?? $this->mock_app_args);
	}
}
