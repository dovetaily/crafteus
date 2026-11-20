<?php

namespace Crafteus\Tests\Unit\Environment;

use Crafteus\Crafteus;
use Crafteus\Environment\App;
use Crafteus\Environment\Ecosystem;
use Crafteus\Environment\Foundation;
use Crafteus\Environment\Stub;
use Crafteus\Environment\Support\AnonymousTemplate;
use Crafteus\Environment\Support\Templating;
use Crafteus\Environment\Template;
use Crafteus\Exceptions\BaseException;
use Crafteus\Exceptions\EcosystemMissingMethodException;
use Crafteus\Exceptions\InvalidStubDefinitionException;
use Crafteus\Exceptions\InvalidTemplateDataException;
use Crafteus\Exceptions\StubAlreadyExistsException;
use Crafteus\Exceptions\TemplateNotRecognizedException;
use Crafteus\Exceptions\TemplateValidationException;
use Crafteus\Exceptions\UnableToResolveTemplateBasenameException;
use Crafteus\Support\Helper;
use Crafteus\Support\Mock;
use Crafteus\Support\MockEcosystem;
use Crafteus\Support\TestCase;

/**
 * @phpstan-import-type TTemplateObjectConfigPartial from \Crafteus\Environment\Template
 */
final class StubTest extends TestCase
{

	public Stub $stub;

	/**
	 * @var array<string>
	 */
	protected array $methods_require_temp_files = [
		'test_get_stub_content',
		'test_get_stub_content_base_exception_no_such_file_or_directory',
		'test_make_path_directory',
		'test_delete_file',
		'test_php_stub',
	];

	// protected array|bool $mock_app_args = [
	// 	'foundation_names' => ["user", "person", "country"],
	// ];

	// protected function getMockAppArgs(): false|array {
	// 	$mock_app_args = array_merge( $this->mock_app_args, [
	// 		'template_config' => function(mixed ...$args){
	// 			return $this->mock()->templatesConfig(config: [
	// 				'__templateNames' => ['model', 'repository', 'seeder'],
	// 				'*' => [
	// 					'__multiplePath' => ['api', 'grpc'],
	// 					'templating' => null
	// 				],
	// 			]);
	// 		}
	// 	]);
	// 	return $mock_app_args === true ? [] : ($mock_app_args['#'.$this->name()] ?? $mock_app_args);

	// }

	protected function setUp(): void
	{
		parent::setUp();

		$this->stub = $this->getStub();
	}

	/**
	 * @param array $conf
	 *
	 * @return Stub
	 *
	 */
	protected function getStub(array $conf = []): Stub
	{
		return new class($this->mock(), $conf) extends Stub {
			public function __construct(Mock $mock, array $conf = [])
			{
				$data = [
					"stub" => $mock->templateStubFile(),
					"template" => null,
					"directory" => $mock->path(),
					"basename" => uniqid() . '-basename-suffix',
					"extension" => $mock->templateExtension(),
					"generate" => true,
					"key_id" => "api",
					...$conf
				];
				parent::__construct(...$data);
			}
		};
	}

	public function test_get_config(): void
	{

		$this->assertIsArray($this->stub->getConfig());

		$this->assertEmpty($this->stub->getConfig());

		$this->assertEquals([], $this->stub->getConfig('key.not.exists', []));
	}

	public function test_get_stub_content(): void
	{
		$stub = $this->getStub(["stub" => "@content"]);
		$this->assertEquals('content', $stub->getOriginType());
		$this->assertEquals("@content", $stub->getStubContent());

		$stub = $this->getStub(["stub" => 'http://localhost:8082/url.stub']);
		$this->assertEquals('url', $stub->getOriginType());
		$this->assertEquals('{{url-content}}', $stub->getStubContent());


		$stub = $this->getStub(["stub" => $this->mock()->templateStubFile()]);
		$this->assertEquals('file', $stub->getOriginType());
		$this->assertEquals('{{content}}', $stub->getStubContent());
	}

	public function test_get_stub_content_base_exception_no_such_file_or_directory(): void
	{

		$this->expectException(BaseException::class);
		$this->expectExceptionCode(5504);
		$this->expectExceptionMessageMatches('/Failed to open stream: No such file or directory[\s]*$/i');

		$stub_file = $this->mock()->templateStubFile();
		$stub = $this->getStub(["stub" => $stub_file]);
		$this->assertEquals('file', $stub->getOriginType());

		unlink($stub_file);

		$stub->getStubContent();
	}

	public function test_get_stub_content_base_exception_request_failed_404(): void
	{
		$this->expectException(BaseException::class);
		$this->expectExceptionCode(5504);
		$this->expectExceptionMessageMatches('/404 Not Found[\s]*$/i');

		$stub_url = 'http://localhost:8082/url-not-exists.stub';
		$stub = $this->getStub(["stub" => $stub_url]);

		$this->assertEquals('url', $stub->getOriginType());

		self::catchIfMessageContains(
			function () use ($stub) {
				Helper::dump('--');
				$stub->getStubContent();
			},
			'Failed to open stream',
			'lorem ipsum'
		);
	}

	/**
	 * Executes a callback and handles exceptions matching a message.
	 *
	 * @param \Closure():void $call
	 * @param string $searchText
	 * @param string|\Closure(\Throwable):\Throwable|null $error
	 */
	public static function catchIfMessageContains(\Closure $call, string $searchText, string|\Closure|null $error = null): void
	{
		try {
			$call();
		} catch (\Throwable $th) {
			if (str_contains($th->getMessage(), $searchText)) {
				if (is_string($error) || $error === null)
					throw new BaseException($error ?? $th->getMessage(), 1, $th);

				throw $error($th);
			}
		}
	}

	public function test_get_stub_content_base_exception_unknown_host(): void
	{

		$this->expectException(BaseException::class);
		$this->expectExceptionCode(5504);
		$this->expectExceptionMessageMatches('/file_get_contents\\(\\)\\: php_network_getaddresses\\: getaddrinfo/i');

		$stub_url = 'http://www.domain-not-exists-e8he2ne2-e28e28ne2-e82n.com';
		$stub = $this->getStub(["stub" => $stub_url]);
		$this->assertEquals('url', $stub->getOriginType());

		$stub->getStubContent();
	}

	public function test_make_path_directory(): void
	{
		// Helper::dd($this->stub->getOriginStub(), $this->stub->getPath());
		$this->stub->makePathDirectory();
		$this->assertDirectoryExists($this->stub->getPath());
	}

	public function test_delete_file(): void
	{
		$this->stub->makePathDirectory();
		$this->assertTrue($this->stub->generateFile());
		$this->assertFileExists($this->stub->getFilePath());
		$this->stub->deleteFile();
		$this->assertFileDoesNotExist($this->stub->getFilePath());
	}

	public function test_add_current_content(): void
	{
		$this->stub->addCurrentContent("-suffix");
		$this->assertStringEndsWith(PHP_EOL . "-suffix", $this->stub->getCurrentContent());
		$this->stub->addCurrentContent("--suffix0", true);
		$this->assertStringEndsWith("-suffix--suffix0", $this->stub->getCurrentContent());
	}

	public function test_add_deferred_content(): void
	{
		$this->stub->addDeferredContent("-suffix");
		$this->assertStringEndsWith(PHP_EOL . "-suffix", $this->stub->getDeferredContent());
		$this->stub->addDeferredContent("--suffix0", true);
		$this->assertStringEndsWith("-suffix--suffix0", $this->stub->getDeferredContent());
	}

	public function test_get_templating_instance(): void
	{
		// $stub = $this->getStub([
		// 	'templating' => get_class(new class($this->stub) extends Templating {})
		// ]);
		// $stub->getTemplatingInstance();

		$templating_class = get_class(new class($this->stub) extends Templating {});

		$this->assertInstanceOf($templating_class, $this->stub->setTemplating($templating_class)->getTemplatingInstance());
	}

	public function test_get_templating_instance_with_null(): void
	{
		$this->assertNull($this->stub->setTemplating(null)->getTemplatingInstance());
	}

	public function test_apply_content_with_templating(): void
	{
		$templating_class = get_class(new class($this->stub) extends Templating {
			public function __construct(mixed ...$args)
			{
				$this->setHolder('{{content}}', 'value');
				parent::__construct(...$args);
			}
		});


		$this->stub->setTemplating($templating_class)->getTemplatingInstance();

		$this->assertEquals('{{content}}', $this->stub->getCurrentContent());

		$this->stub->applyContentWithTemplating();

		$this->assertEquals('value', $this->stub->getCurrentContent());
	}

	public function test_php_stub(): void
	{
		$templating_class = get_class(new class($this->stub) extends Templating {
			public function __construct(mixed ...$args)
			{
				$this->setHolder('{{content}}', 'value');
				parent::__construct(...$args);
			}
		});

		$stub_file = $this->mock()->templateStubFile(scriptedContent: true);

		$stub = $this->getStub([
			'stub' => $stub_file
		]);

		$stub->setTemplating($templating_class);
		$this->stub->phpStub();
	}
}
