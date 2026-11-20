<?php

namespace Crafteus\Tests\Unit\Environment;

use Crafteus\Crafteus;
use Crafteus\Environment\App;
use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Foundation;
use Crafteus\Environment\Stub;
use Crafteus\Exceptions\FoundationAlreadyExistsException;
use Crafteus\Support\Helper;
use Crafteus\Support\MockEcosystem;
use Crafteus\Support\TestCase;

final class AppTest extends TestCase
{

	/**
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [
		'test_generate',
		'test_generate_fails_when_file_already_exists',
		'test_generate_with_disable_writing',

		'test_cancel_generated',
	];

	protected array|bool $mock_app_args = [
		'foundation_names' => ["user", "person", "country"],
	];

	public function test_make(): void
	{
		$foundations_options = $this->mock()->foundationsOptions(
			foundation_names: $this->getMockAppArgs()['foundation_names']
		);

		$anonym_ecosystem = new class extends Ecosystem {
			public static ?array $tmp = null;

			public function template(): array
			{
				return $this->tmp_();
			}

			public function tmp_(): array
			{
				return (self::$tmp = self::$tmp === null ? Helper::mock()->templatesConfig() : self::$tmp);
			}
		};

		$anonym_ecosystem_class = get_class($anonym_ecosystem);

		unset($anonym_ecosystem);

		$foundations_options['country']['ecosystem'] = $anonym_ecosystem_class;

		$app = (new App())->make(MockEcosystem::class, $foundations_options);

		foreach ($this->getMockAppArgs()['foundation_names'] as $foundation_name) {
			$this->assertArrayHasKey($foundation_name, $app->getFoundations());
			$this->assertInstanceOf(Foundation::class, $app->getFoundation($foundation_name));
		}

		$this->assertInstanceOf($anonym_ecosystem_class, $app->country);
	}

	public function test_add_foundation(): void
	{
		$app = new App();

		$anonym_ecosystem = new class extends Ecosystem {
			public static ?array $tmp = null;
			public function template(): array
			{
				return $this->tmp_();
			}
			public function tmp_(): array
			{
				return (self::$tmp = self::$tmp === null ? Helper::mock()->templatesConfig() : self::$tmp);
			}
		};

		$anonym_ecosystem_class = get_class($anonym_ecosystem);

		unset($anonym_ecosystem);

		$app->addFoundation('user', MockEcosystem::class);

		$app->addFoundation('person', $anonym_ecosystem_class);


		$this->assertArrayHasKey('user', $app->getFoundations());

		$this->assertInstanceOf(Foundation::class, $app->getFoundation('user'));

		$this->assertInstanceOf(MockEcosystem::class, $app->user);
		$this->assertInstanceOf($anonym_ecosystem_class, $app->person);

		$this->assertNotInstanceOf(MockEcosystem::class, $app->person);
	}

	public function test_add_foundation_with_foundation_already_exists_exception(): void
	{
		$app = new App();

		$app->addFoundation('user', MockEcosystem::class);

		$this->expectException(FoundationAlreadyExistsException::class);

		$app->addFoundation('user', MockEcosystem::class);
	}

	public function test___get(): void
	{
		$this->assertInstanceOf(Ecosystem::class, $this->app->user);

		$this->assertEquals("user", $this->app->user->getFoundation()->getName());

		$this->assertEquals("person", $this->app->person->getFoundation()->getName());
	}

	public function test___get_with_null(): void
	{
		$app = new App();

		$this->assertNull($app->foundationName);
	}

	public function test_get_foundation(): void
	{
		$this->assertInstanceOf(Foundation::class, $this->app->getFoundation("user"));

		$this->assertEquals("user", $this->app->getFoundation("user")->getName());
	}

	public function test_get_foundation_with_null(): void
	{
		$app = new App();

		$this->assertNull($app->getFoundation("foundationName"));
	}

	public function test_foundation_exists_with_false(): void
	{
		$result = (new App())->foundationExists("foundationName");

		$this->assertFalse($result);
	}

	public function test_foundation_exists_with_true(): void
	{
		$this->assertTrue($this->app->foundationExists("user"));
	}

	public function test_generate(): void
	{
		$results = $this->app->generate();

		array_map(
			fn($foundation_name) => array_map(
				fn($template)
				=> array_map(
					function ($stub) {
						$this->assertInstanceOf(Stub::class, $stub);
						$this->assertTrue($stub->isGenerated());
						$this->assertFileExists($stub->getFilePath());
					},
					$template->getStubs()
				),
				$this->app->getFoundation($foundation_name)->ecosystemInstance()->getTemplatesInstance()
			),
			array_keys($results)
		);
	}

	public function test_generate_fails_when_file_already_exists(): void
	{
		$this->app->generate();

		ob_start();
		$results = $this->app->generate();
		ob_get_clean();

		array_map(
			function ($templates) use ($results) {
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
					$templates
				);
			},
			$results
		);
	}

	public function test_generate_with_disable_writing(): void
	{
		Crafteus::disableFileWriting();

		$results = $this->app->generate();

		$this->assertTrue(Crafteus::$disable_file_writing);

		array_map(
			fn($foundation_name) => array_map(
				fn($template)
				=> array_map(
					function ($stub) {
						$this->assertInstanceOf(Stub::class, $stub);
						$this->assertTrue($stub->isGenerated());
						$this->assertFileDoesNotExist($stub->getFilePath());
					},
					$template->getStubs()
				),
				$this->app->getFoundation($foundation_name)->ecosystemInstance()->getTemplatesInstance()
			),
			array_keys($results)
		);

		Crafteus::enableFileWriting();
	}

	public function test_cancel_generated(): void
	{
		$this->app->generate();
		$this->app->cancelGenerated();

		array_map(
			fn($foundation)
			=> array_map(
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
			),
			$this->app->getFoundations()
		);
	}

	public function test_remove_foundation(): void
	{
		$app = $this->mock()->app(foundation_names: ["user"]);

		$this->assertArrayNotHasKey("user", $app->removeFoundation('user')->getFoundations());
	}
}
