<?php

namespace Crafteus\Tests\Unit\Environment;

use Crafteus\Crafteus;
use Crafteus\Environment\App;
use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Foundation;
use Crafteus\Environment\Stub;
use Crafteus\Environment\Template;
use Crafteus\Exceptions\InvalidEcosystemException;
use Crafteus\Support\Helper;
use Crafteus\Support\Tests\MockEcosystem;
use Crafteus\Support\Tests\TestCase;

final class FoundationTest extends TestCase
{

	/**
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [
		'test_generate_ecosystem',
		'test_generate_ecosystem_fails_when_file_already_exists',
		'test_generate_ecosystem_with_disable_writing',

		'test_cancel_generated_ecosystem',
	];

	protected array|bool $mock_app_args = [
		'foundation_names' => ["user", "person", "country"],
	];

	public function test_clean_ecosystem(): void
	{
		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);


		$this->assertNull($foundation->getEcosystemInstance());
		$this->assertFalse($foundation->ecosystemIsClean());

		$foundation->cleanEcosystem();

		$this->assertInstanceOf(Ecosystem::class, $foundation->getEcosystemInstance());
		$this->assertTrue($foundation->ecosystemIsClean());
	}

	public function test_clean_class_ecosystem_with_invalid_ecosystem_exception(): void
	{
		$foundation = new Foundation(
			new App(),
			'user',
			get_class(new class {}),
		);

		$this->expectException(InvalidEcosystemException::class);

		$foundation->cleanClassEcosystem();
	}

	public function test_clean_template_ecosystem(): void
	{
		MockEcosystem::$_template_config = $this->mock()->templatesConfig(config: [
			'__templateNames' => ['model', 'repository']
		]);

		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);

		$this->assertNull($foundation->getEcosystemInstance());

		$foundation->cleanTemplateEcosystem();

		$this->assertInstanceOf(Ecosystem::class, $foundation->getEcosystemInstance());

		$this->assertInstanceOf(Template::class, $foundation->getEcosystemInstance()->getTemplatesInstance()['model'] ?? null);

		$this->assertInstanceOf(Template::class, $foundation->getEcosystemInstance()->getTemplatesInstance()['repository'] ?? null);
	}

	public function test_ecosystem_instance(): void
	{
		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);

		$this->assertInstanceOf(Ecosystem::class, $foundation->ecosystemInstance());

		$this->assertEquals($foundation, $foundation->ecosystemInstance()->getFoundation());
	}

	public function test_generate_ecosystem(): void
	{
		MockEcosystem::$_template_config = $this->mock()->templatesConfig(config: [
			'__templateNames' => ['model', 'repository']
		]);

		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);

		$results = $foundation->generateEcosystem();

		$this->assertTrue($foundation->ecosystemIsClean());

		$this->assertContainsEquals(true, $results);

		$this->assertEquals(['model', 'repository'], array_keys($results));

		array_map(
			fn($template)
			=> array_map(
				function ($stub) {
					$this->assertInstanceOf(Stub::class, $stub);
					$this->assertTrue($stub->isGenerated());
					$this->assertFileExists($stub->getFilePath());
				},
				$template->getStubs()
			),
			$foundation->ecosystemInstance()->getTemplatesInstance()
		);
	}

	public function test_generate_ecosystem_fails_when_file_already_exists(): void
	{
		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);

		$foundation->generateEcosystem();

		ob_start();
		$results = $foundation->generateEcosystem();
		ob_get_clean();

		array_map(
			function ($response) {
				$this->assertEquals(['generated', 'not_generated'], array_keys($response));
				array_map(
					function (Stub $stub) {
						$this->assertInstanceOf(Stub::class, $stub);
						$this->assertTrue($stub->errorExists());
						$this->assertEquals(current($stub->getErrors())->getMessage(), "File already exists : `" . $stub->getFilePath() . "`");
					},
					$response['not_generated']
				);
			},
			$results
		);
	}

	public function test_generate_ecosystem_with_disable_writing(): void
	{
		Crafteus::disableFileWriting();

		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
		);

		$foundation->generateEcosystem();

		$this->assertTrue(Crafteus::$disable_file_writing);

		array_map(
			fn($template)
			=> array_map(
				function ($stub) {
					$this->assertInstanceOf(Stub::class, $stub);
					$this->assertTrue($stub->isGenerated());
					$this->assertFileDoesNotExist($stub->getFilePath());
				},
				$template->getStubs()
			),
			$foundation->ecosystemInstance()->getTemplatesInstance()
		);

		Crafteus::enableFileWriting();
	}

	public function test_cancel_generated_ecosystem(): void
	{
		$foundation = $this
			->initMockApp(['foundation_names' => ['user']])
			->app->getFoundation('user');

		$foundation->generateEcosystem();

		$foundation->cancelGeneratedEcosystem();

		array_map(
			fn($template)
			=> array_map(
				function ($stub) {
					$this->assertInstanceOf(Stub::class, $stub);
					$this->assertFalse($stub->isGenerated());
					$this->assertFileDoesNotExist($stub->getFilePath());
				},
				$template->getStubs()
			),
			$foundation->ecosystemInstance()->getTemplatesInstance()
		);
	}
}
