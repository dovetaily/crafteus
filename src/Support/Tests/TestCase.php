<?php

namespace Crafteus\Support\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

use Crafteus\Crafteus;
use Crafteus\Support\Helper;
use Crafteus\Support\Tests\Mock;
use Crafteus\Support\Tests\MockEcosystem;
use Crafteus\Support\Tests\Traits\MockApp;

/**
 * @phpstan-import-type TEcosystemTemplateConfigKeysPartial from \Crafteus\Environment\Ecosystem
 */
class TestCase extends BaseTestCase
{
	use MockApp;

	protected ?Mock $mock_instance = null;

	/**
	 * Test methods requiring actual temporary files.
	 *
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [];

	/**
	 * Original file-writing state.
	 *
	 * @var bool|null
	 */
	protected ?bool $tmp_crafteus_disable_file_writing = null;

	/**
	 * Returns the shared mock instance.
	 *
	 * @param mixed ...$args
	 *
	 * @return Mock
	 */
	protected function mock(mixed ...$args): Mock
	{
		return $this->mock_instance = $this->mock_instance ?? Helper::mock(...$args);
	}

	/**
	 * Initializes the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		$this->tempDirSetUp();

		MockEcosystem::$_template_config = $this->mock()->templatesConfig();

		$this->mockAppSetUp();
	}

	/**
	 * Restores the test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$this->tempDirTearDown();

		MockEcosystem::$_template_config = null;

		$this->mockAppTearDown();
	}

	/**
	 * Generates a unique temporary directory path
	 * for the current test.
	 *
	 * @return string
	 */
	protected function resolveTempDir(): string
	{
		preg_match(
			'/[^\\\]*([a-z0-9_]+)$/i',
			strtolower(static::class),
			$class_
		);

		return sys_get_temp_dir()
			. "/php-crafteus-{$class_[0]}-{$this->name()}-"
			. uniqid();
	}

	/**
	 * Checks whether the current test requires
	 * actual temporary files.
	 *
	 * @return bool
	 */
	protected function requireTempFiles(): bool
	{
		return in_array($this->name(), $this->methods_require_temp_files);
	}

	/**
	 * Configures temporary directory and
	 * file-writing settings.
	 *
	 * @return void
	 */
	protected function tempDirSetUp(): void
	{
		$verif = $this->requireTempFiles();

		$this
			->mock($verif ? $this->resolveTempDir() : null)
			->setPathPrefix('craft')
		;

		$this->tmp_crafteus_disable_file_writing = Crafteus::$disable_file_writing;

		if ($verif) {
			Crafteus::enableFileWriting();

			$this->mock()->setPathPrefix('')->makeTempDir();
		} else
			Crafteus::disableFileWriting();
	}

	/**
	 * Removes temporary files and restores
	 * the original file-writing state.
	 *
	 * @return void
	 */
	protected function tempDirTearDown(): void
	{
		if ($this->requireTempFiles()) {
			$this->mock()->removeTempDir();
		}

		if ($this->tmp_crafteus_disable_file_writing !== null) {
			Crafteus::$disable_file_writing = $this->tmp_crafteus_disable_file_writing;
		}

		$this->tmp_crafteus_disable_file_writing = null;
	}
}
