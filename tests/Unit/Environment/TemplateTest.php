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
use Crafteus\Exceptions\InvalidStubDefinitionException;
use Crafteus\Exceptions\InvalidTemplateDataException;
use Crafteus\Exceptions\StubAlreadyExistsException;
use Crafteus\Exceptions\TemplateNotRecognizedException;
use Crafteus\Exceptions\TemplateValidationException;
use Crafteus\Exceptions\UnableToResolveTemplateBasenameException;
use Crafteus\Support\Helper;
use Crafteus\Support\Tests\Mock;
use Crafteus\Support\Tests\MockEcosystem;
use Crafteus\Support\Tests\TestCase;

/**
 * @phpstan-import-type TTemplateObjectConfigPartial from \Crafteus\Environment\Template
 */
final class TemplateTest extends TestCase
{

	public Template $template;

	/**
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [
		'test_generate_stubs_file',
		'test_generate_stubs_file_fails_when_file_already_exists',
		'test_generate_stubs_file_with_disable_writing',

		'test_cancel_stubs_files_generated',
	];

	protected array|bool $mock_app_args = [
		'foundation_names' => ["user", "person", "country"],
	];

	protected function getMockAppArgs(): false|array
	{
		$mock_app_args = array_merge($this->mock_app_args, [
			'template_config' => function (mixed ...$args) {
				return $this->mock()->templatesConfig(config: [
					'__templateNames' => ['model', 'repository', 'seeder'],
					'*' => [
						'__multiplePath' => ['api', 'grpc'],
						'templating' => null
					],
				]);
			}
		]);
		return $mock_app_args === true ? [] : ($mock_app_args['#' . $this->name()] ?? $mock_app_args);
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->template = $this->getTemplate();
	}

	/**
	 * @param TTemplateObjectConfigPartial $conf
	 *
	 * @return Template
	 *
	 */
	protected function getTemplate(array $conf = []): Template
	{
		return new class($this->mock(), $conf) extends Template {
			public function __construct(Mock $mock, array $conf = [])
			{
				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				$conf = array_merge(current($temp_conf)['config'], $conf);

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}
		};
	}

	public function test_set_ecosystem_triggers_warning_from_external_class(): void
	{
		$ecosystem = new class extends Ecosystem {
			public function template(): array
			{
				return [];
			}
		};

		$warning = null;

		set_error_handler(
			static function (int $severity, string $message) use (&$warning): bool {
				if ($severity === E_USER_WARNING) {
					$warning = $message;

					return true;
				}

				return false;
			}
		);

		$this->template->setEcosystem(new MockEcosystem);

		restore_error_handler();

		$this->assertStringContainsString('[W001]', $warning ?? '');
	}

	public function test_get_data(): void
	{
		$this->assertEquals([], $this->template->getData());

		$data = [
			'1',
			'key' => 'a',
			"__template" => [
				'*' => [
					'2*'
				],
				'repository,model' => [
					'3,',
					'key' => '99',
				],
				'model' => [
					"4m",
					'key' => '00',
				],
				'repository' => [
					"4r",
				],
				'seeder' => [
					'key' => [
						'key1' => [
							'a' => 'lorem'
						]
					]
				],
			],
		];

		$data_model = ['1', '2*', '3,', '4m', 'key' => '00'];

		$data_repo = ['1', '2*', '3,', '4r', 'key' => '99'];

		$ecosystem = MockEcosystem::make([
			'user' => [
				'data' => $data
			],
		])->getFoundation('user')->getEcosystemInstance();

		$this->assertEquals(['model', 'repository', 'seeder'], array_keys($ecosystem->template()));

		$this->assertEquals(
			$data_model,
			$ecosystem->getTemplateInstance('model')->getData()
		);

		$this->assertEquals(
			$data_repo,
			$ecosystem->getTemplateInstance('repository')
				->getData()
		);

		$this->assertEquals(
			'lorem',
			$ecosystem->getTemplateInstance('seeder')
				->getData('key.key1.a')
		);

		$this->assertEquals(
			'x',
			$ecosystem->getTemplateInstance('seeder')
				->getData('key.key1.a.n', 'x')
		);

		$this->assertEquals(
			20,
			$ecosystem->getTemplateInstance('seeder')
				->getData('key.key1.a', 20, check_type: fn($data) => is_int($data))
		);
	}

	public function test_compliant_data_with_invalid_template_data_exception(): void
	{
		$this->template->setDataRule([
			'name' => [
				'required' => true
			]
		]);

		$this->expectException(InvalidTemplateDataException::class);

		$this->template->compliantData();
	}
	public function test_compliant_config(): void
	{
		$this->template->setConfigRule([
			'name' => [
				'required' => true
			]
		]);

		$this->expectException(TemplateValidationException::class);
		$this->expectExceptionCode(4406);

		$this->template->compliantConfig();
	}

	public function test_get_config(): void
	{
		$template = new class($this->mock()) extends Template {
			public array $my_conf = [];
			public function __construct(Mock $mock)
			{
				$conf = current($mock->templateConfig())['config'];
				$this->path = $conf['path'] . '/xy';
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];

				$this->my_conf = [
					'key' => [
						'key1' => [
							'a' => 'value'
						]
					]
				];
			}
		};

		$this->assertStringEndsWith('/xy', $template->getConfig('path'));

		$this->assertEquals(
			'value',
			$template->getConfig('my_conf.key.key1.a')
		);

		$s = $template->getConfig('key.not.exists', 100);

		$this->assertEquals(
			100,
			$s
		);

		$this->assertEquals(
			1.2,
			$template->getConfig('my_conf.key.key1.a', 1.2, fn($data) => is_float($data))
		);
	}

	public function test_init_stubs(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{

				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				// Helper::dd($temp_conf);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}

			public function transformBasename(string|int $key_path): string|array
			{
				return $key_path . '-' . uniqid() . '-basename-suffix';
			}
		};

		$this->assertNull($template->getStubs());

		$template->initStubs();

		$this->assertContainsOnlyInstancesOf(Stub::class, $template->getStubs());

		array_map(
			function (Stub $stub) {
				$this->assertStringEndsWith('-basename-suffix', $stub->getBasename());
				$this->assertContains($stub->key_id, ['api', 'grpc']);
			},
			$template->getStubs()
		);
	}

	public function test_init_stubs_with_unable_to_resolve_template_basename_exception(): void
	{
		$this->assertNull($this->template->getStubs());

		$this->expectException(UnableToResolveTemplateBasenameException::class);

		$this->template->initStubs();
	}

	public function test_init_stub_with_invalid_stub_definition_exception_code_5401(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{
				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}
			public function transformBasename(string|int $key_path)
			{
				return [[$key_path] => []];
			}
		};

		$this->expectException(InvalidStubDefinitionException::class);
		$this->expectExceptionCode(5401);

		$template->initStubs();
	}

	public function test_init_stub_with_invalid_stub_definition_exception_code_5402(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{
				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = '';
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}
			public function transformBasename()
			{
				return '';
			}
		};

		$this->expectException(InvalidStubDefinitionException::class);
		$this->expectExceptionCode(5402);

		$template->initStubs();
	}

	// public function test_init_stubs_with_stub_already_exists_exception() : void {

	// 	// Helper::dd($this->template->getConfig());

	// 	$this->template->addStub(
	// 		stub_file: $this->mock()->stubFile(),
	// 		directory:$this->mock()->templatePath(),
	// 		basename: "fname",
	// 		extension: $this->mock()->templatePath(),
	// 		key: "api"
	// 	);

	// 	Helper::dd($this->template->getStubs());

	// 	$this->expectException(StubAlreadyExistsException::class);

	// 	$this->template->initStubs();

	// }

	public function test_add_stub(): void
	{
		$this->assertEmpty($this->template->getStubs());

		$directory = $this->mock()->templatePath();

		$extension = $this->mock()->templateExtension();

		$file_path = Helper::normalizePath($directory . '/fname' . (!empty($extension) ? '.' . $extension : ''));

		$result = $this->template->addStub(
			stub_file: $this->mock()->stubFile(),
			directory: $directory,
			basename: "fname",
			extension: $extension,
			key: "api"
		);

		$this->assertInstanceOf(Stub::class, $result);

		$this->assertEquals($file_path, $result->getFilePath());

		$this->assertEquals('api', $result->key_id);
	}

	public function test_add_stub_with_null(): void
	{
		$result = $this->template->addStub(
			stub_file: $this->mock()->stubFile(),
			directory: $this->mock()->templatePath(),
			basename: "fname",
			extension: $this->mock()->templatePath(),
			key: "api"
		);

		$this->assertInstanceOf(Stub::class, $result);

		$result = $this->template->addStub(
			stub_file: $this->mock()->stubFile(),
			directory: $this->mock()->templatePath(),
			basename: "fname",
			extension: $this->mock()->templatePath(),
			key: "api"
		);

		$this->assertNull($result);
	}

	public function test_generate_stubs_file(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{

				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				// Helper::dd($temp_conf);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}

			public function transformBasename(string|int $key_path): string|array
			{
				return $key_path . '-' . uniqid() . '-basename-suffix';
			}
		};

		$results = $template->generateStubsFile();

		$this->assertContainsEquals(true, $results);

		array_map(
			function ($stub) {
				$this->assertInstanceOf(Stub::class, $stub);
				$this->assertTrue($stub->isGenerated());
				$this->assertFileExists($stub->getFilePath());
				$this->assertStringStartsWith($stub->key_id, $stub->getBasename());
				$this->assertStringEndsWith('-basename-suffix', $stub->getBasename());
			},
			$template->getStubs()
		);
	}

	public function test_generate_stubs_file_fails_when_file_already_exists(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{

				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				// Helper::dd($temp_conf);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}

			public function transformBasename(string|int $key_path): string|array
			{
				return $key_path . '-' . uniqid() . '-basename-suffix';
			}
		};

		$template->generateStubsFile();

		ob_start();
		$results = $template->generateStubsFile();
		ob_get_clean();

		$this->assertEquals(['generated', 'not_generated'], array_keys($results));

		array_map(
			function (Stub $stub) {
				$this->assertInstanceOf(Stub::class, $stub);
				$this->assertTrue($stub->errorExists());
				$this->assertEquals(current($stub->getErrors())->getMessage(), "File already exists : `" . $stub->getFilePath() . "`");
			},
			$results['not_generated']
		);
	}

	public function test_generate_stubs_file_with_disable_writing(): void
	{
		Crafteus::disableFileWriting();

		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{

				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				// Helper::dd($temp_conf);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}

			public function transformBasename(string|int $key_path): string|array
			{
				return $key_path . '-' . uniqid() . '-basename-suffix';
			}
		};

		$results = $template->generateStubsFile();

		$this->assertTrue(Crafteus::$disable_file_writing);

		array_map(
			function ($stub) {
				$this->assertInstanceOf(Stub::class, $stub);
				$this->assertTrue($stub->isGenerated());
				$this->assertFileDoesNotExist($stub->getFilePath());
			},
			$template->getStubs()
		);
	}

	public function test_cancel_stubs_files_generated(): void
	{
		$template = new class($this->mock()) extends Template {
			public function __construct(Mock $mock)
			{

				$temp_conf = $mock->templateConfig(template_name: "model", config: ['__multiplePath' => ['api', 'grpc']]);

				// Helper::dd($temp_conf);

				$conf = current($temp_conf)['config'];

				$this->setTemplateName(current(array_keys($temp_conf)));

				$this->path = $conf['path'];
				$this->extension = $conf['extension'];
				$this->stub_file = $conf['stub_file'];
				$this->generate = $conf['generate'];
				$this->templating = $conf['templating'];
			}

			public function transformBasename(string|int $key_path): string|array
			{
				return $key_path . '-' . uniqid() . '-basename-suffix';
			}
		};

		$template->generateStubsFile();

		$template->cancelStubsFilesGenerated();

		array_map(
			function ($stub) {
				$this->assertInstanceOf(Stub::class, $stub);
				$this->assertFalse($stub->isGenerated());
				$this->assertFileDoesNotExist($stub->getFilePath());
			},
			$template->getStubs()
		);
	}
}
