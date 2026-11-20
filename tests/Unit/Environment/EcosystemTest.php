<?php

namespace Crafteus\Tests\Unit\Environment;

use Crafteus\Crafteus;
use Crafteus\Environment\App;
use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Foundation;
use Crafteus\Environment\Stub;
use Crafteus\Environment\Support\AnonymousTemplate;
use Crafteus\Environment\Template;
use Crafteus\Exceptions\EcosystemMissingMethodException;
use Crafteus\Exceptions\TemplateNotRecognizedException;
use Crafteus\Support\Helper;
use Crafteus\Support\MockEcosystem;
use Crafteus\Support\TestCase;

final class EcosystemTest extends TestCase
{

	/**
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [
		'test_generate_templates',
		'test_generate_templates_fails_when_file_already_exists',
		'test_generate_templates_with_disable_writing',

		'test_cancel_generated_templates',
	];

	protected array|bool $mock_app_args = [
		'foundation_names' => ["user", "person", "country"],
	];

	protected function getMockAppArgs(): false|array
	{
		$mock_app_args = array_merge($this->mock_app_args, [
			'template_config' => function (?Ecosystem $eco = null) {
				return $this->mock()->templatesConfig(config: [
					'__templateNames' => ['model', 'repository']
				]);
			}
		]);
		return $mock_app_args === true ? [] : ($mock_app_args['#' . $this->name()] ?? $mock_app_args);
	}

	public function test_template(): void
	{
		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$this->assertEquals(MockEcosystem::$_template_config, $ecosystem->template());
	}

	public function test_template_with_ecosystem_missing_method_exception(): void
	{
		$this->expectException(EcosystemMissingMethodException::class);

		(new Ecosystem)->template();
	}

	public function test_make(): void
	{
		$app = MockEcosystem::make($this->mock()->foundationsOptions(foundation_names: $this->getMockAppArgs()['foundation_names']));

		$this->assertInstanceOf(App::class, $app);
		$this->assertEquals($this->getMockAppArgs()['foundation_names'], array_keys($app->getFoundations()));
	}

	public function test_get_data(): void
	{
		$ecosystem = new MockEcosystem;

		$this->assertEmpty($ecosystem->getData());
	}

	public function test_get_data_with_data(): void
	{
		$data = ["a", "b" => 12];

		$ecosystem = (new Foundation(
			new App(),
			'user',
			MockEcosystem::class,
			$data
		))->ecosystemInstance();

		$this->assertNull($ecosystem->getCurrentData());

		$this->assertEquals($data, $ecosystem->getData());
	}

	public function test_set_foundation(): void
	{
		$ecosystem = new MockEcosystem;

		$this->assertNull($ecosystem->getFoundation());

		$foundation = new Foundation(
			new App(),
			'user',
			MockEcosystem::class
		);

		$ecosystem->setFoundation($foundation);

		$this->assertEquals($foundation, $ecosystem->getFoundation());
	}

	public function test_after_foundation_set(): void
	{
		$ecosystem = new Foundation(
			new App(),
			'user',
			get_class(new class extends Ecosystem {
				public ?string $value = null;
				public function afterFoundationSet(Foundation $foundtation): void
				{
					$this->value = "ok";
				}
			})
		)->ecosystemInstance();

		$this->assertEquals("ok", $ecosystem->{'value'});
	}

	public function test_get_template(): void
	{
		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$this->assertIsArray($ecosystem->getTemplate('model'));

		$this->assertEquals(MockEcosystem::$_template_config['model'], $ecosystem->getTemplate('model'));
	}

	public function test_get_template_with_false(): void
	{
		$ecosystem = new class extends Ecosystem {
			public function template(): array
			{
				return [];
			}
		};

		$this->assertFalse($ecosystem->getTemplate('templateName'));
	}

	public function test_get_template_with_string(): void
	{
		$template_class = get_class(new class extends Template {});

		$ecosystem = new class($template_class) extends Ecosystem {
			public function __construct(public string $template_class) {}
			public function template(): array
			{
				return [
					'anonymeTemplate' => $this->template_class,
				];
			}
		};

		$this->assertIsString($ecosystem->getTemplate('anonymeTemplate'));

		$this->assertEquals($template_class, $ecosystem->getTemplate('anonymeTemplate'));
	}

	public function test_template_instance(): void
	{
		MockEcosystem::$_template_config = array_map(function ($t) {
			// $t['config']['templating'] = [\StdClass::class];
			return $t;
		}, $this->getMockAppArgs()['template_config']());

		$ecosystem = new MockEcosystem;

		$this->assertInstanceOf(AnonymousTemplate::class, $ecosystem->templateInstance('model'));
	}

	// public function test_template_instance_with_invalid_template_exception() : void {
	// 	/* InvalidTemplateException */
	// }
	// public function test_template_instance_with_invalid_template_type_exception() : void {
	// 	/* InvalidTemplateTypeException */
	// }
	// public function test_template_instance_with_duplicate_template_instance_exception() : void {
	// 	/* DuplicateTemplateInstanceException */
	// }
	// public function test_template_instance_with_template_config_exception() : void {
	// 	/* TemplateConfigException */
	// }
	// public function test_template_instance_with_template_validation_exception() : void {
	// 	/* TemplateValidationException */
	// }
	// public function test_template_instance_with_invalid_template_property_exception() : void {
	// 	/* InvalidTemplatePropertyException */
	// }
	// public function test_template_instance_with_missing_configKey_exception() : void {
	// 	/* MissingConfigKeyException */
	// }

	public function test_get_template_instance(): void
	{
		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config'];

		$ecosystem = new MockEcosystem;

		$ecosystem->templateInstance('model');

		$this->assertInstanceOf(AnonymousTemplate::class, $ecosystem->getTemplateInstance('model'));
	}

	public function test_get_template_instance_with_null(): void
	{
		$ecosystem = new MockEcosystem;

		$this->assertNull($ecosystem->getTemplateInstance('model'));
	}

	public function test_clean_templates(): void
	{
		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config'];

		$ecosystem = new MockEcosystem;

		$ecosystem->cleanTemplates();

		$this->assertContainsOnlyInstancesOf(AnonymousTemplate::class, $ecosystem->getTemplatesInstance());
	}

	public function test_clean_templates_with_template_not_recognized_exception(): void
	{
		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']();

		$ecosystem = new class extends MockEcosystem {
			public function afterFoundationCleanTemplateEcosystem(): void
			{
				unset(static::$_template_config['repository']);
			}
		};

		$this->expectException(TemplateNotRecognizedException::class);

		$ecosystem->cleanTemplates();
	}

	public function test_generate_templates(): void
	{
		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$f = fn(string|int $key_path, Template $template)
		=> $template->getTemplateName() . '-' . $key_path . '-' . uniqid();

		foreach (array_keys($ecosystem->template()) as $template_name)
			MockEcosystem::$_template_config[$template_name]['config']['transformBasename'] = $f;

		$results = $ecosystem->cleanTemplates()->generateTemplates();

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
			$ecosystem->getTemplatesInstance()
		);
	}

	public function test_generate_templates_fails_when_file_already_exists(): void
	{
		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$f = fn(string|int $path, Template $template)
		=> $template->getTemplateName() . '-' . $path . '-' . uniqid();

		foreach (array_keys($ecosystem->template()) as $template_name)
			MockEcosystem::$_template_config[$template_name]['config']['transformBasename'] = $f;

		$ecosystem->cleanTemplates()->generateTemplates();

		ob_start();
		$results = $ecosystem->cleanTemplates()->generateTemplates();
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

	public function test_generate_templates_with_disable_writing(): void
	{
		Crafteus::disableFileWriting();

		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$f = fn(string|int $path, Template $template)
		=> $template->getTemplateName() . '-' . $path . '-' . uniqid();

		foreach (array_keys($ecosystem->template()) as $template_name)
			MockEcosystem::$_template_config[$template_name]['config']['transformBasename'] = $f;

		$ecosystem->cleanTemplates()->generateTemplates();

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
			$ecosystem->getTemplatesInstance()
		);
	}

	public function test_cancel_generated_templates(): void
	{
		$ecosystem = new MockEcosystem;

		MockEcosystem::$_template_config = $this->getMockAppArgs()['template_config']($ecosystem);

		$f = fn(string|int $path, Template $template)
		=> $template->getTemplateName() . '-' . $path . '-' . uniqid();

		foreach (array_keys($ecosystem->template()) as $template_name)
			MockEcosystem::$_template_config[$template_name]['config']['transformBasename'] = $f;

		$ecosystem->cleanTemplates()->generateTemplates();

		$ecosystem->cancelTemplatesGenerated();

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
			$ecosystem->getTemplatesInstance()
		);
	}
}
