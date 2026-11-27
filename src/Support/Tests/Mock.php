<?php

namespace Crafteus\Support\Tests;

use Crafteus\Crafteus;
use Crafteus\Environment\App;
use Crafteus\Environment\Ecosystem;
use Crafteus\Support\Helper;
use Crafteus\Support\Tests\Traits\MockData;
use Crafteus\Support\Tests\Traits\MockTempDir;


/**
 * @phpstan-import-type TTemplateObjectConfig from \Crafteus\Environment\Template
 * @phpstan-import-type TFoundationOptions from \Crafteus\Environment\Foundation
 * @phpstan-import-type TTemplatesConfigPartial from \Crafteus\Environment\Template
 * @phpstan-import-type TEcosystemTemplateConfigKeysPartial from \Crafteus\Environment\Ecosystem
 *
 * @phpstan-import-type TTemplatePathProperty from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateExtensionProperty from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateStubFileProperty from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateGenerateProperty from \Crafteus\Environment\Template
 * @phpstan-import-type TTemplateTemplatingProperty from \Crafteus\Environment\Template
 *
 * @phpstan-type TTCConfigArg (array<mixed>|array{
 *   __multiplePath?: bool|array<string>|int|null,
 *   extension: TTemplateExtensionProperty,
 *   stub_file: TTemplateStubFileProperty,
 *   generate: TTemplateGenerateProperty,
 *   templating: TTemplateTemplatingProperty,
 * })
 *
 *
 */
class Mock
{

	use MockData, MockTempDir;

	public function __construct(?string $tempDir = null)
	{
		$this->setTempDir($tempDir);
	}

	/**
	 * Returns a mocked application instance.
	 *
	 * @param array<string>|int $foundation_names
	 * @param \Closure(string $foundation_name, string|int $index, array|bool $withConfig)|null $data_resolver
	 * @param \Closure(\Crafteus\Environment\Ecosystem)|array|null|null $template_config
	 * @param array|bool $withConfig
	 *
	 * @return App Mocked application instance.
	 *
	 */
	public function app(
		array|int $foundation_names = 1,
		?callable $data_resolver = null,
		\Closure|array|null $template_config = null,
		array|bool $withConfig = false,
	): App {

		if ($template_config !== null) {
			MockEcosystem::$_template_config = $template_config;
		}

		return Crafteus::make(
			MockEcosystem::class,
			$this->foundationsOptions($data_resolver, $foundation_names, $withConfig)
		);
	}

	// /**
	//  * Returns a mocked application instance.
	//  *
	//  * @return App Mocked application instance.
	//  */
	// public function app(?string $foundation_name) : App {

	// 	$app = $this->app();

	// 	$ecosystem = new class extends Ecosystem{public function template() : array { return []; }};

	// 	$app->addFoundation(
	// 		$foundation_name ?? "foundation",
	// 		$ecosystem::class
	// 	);

	// 	return $app;

	// }





	/**
	 * Returns mocked foundation options.
	 *
	 * @param \Closure(string $foundation_name, string|int $index, array|bool $withConfig)|null $data_resolver Optional resolver for foundation data.
	 * @param array<string>|int $foundation_names Foundation names to use.
	 * @param (array<mixed> & array{
	 *   *?: bool|array{
	 *     __count: int,
	 *     __config: array,
	 *     __templateNames?: array<string>,
	 *     *?: array
	 *   },
	 * })|bool $withConfig Whether to include foundation configuration.
	 *
	 * @return array<array{data?:array,config?:array{template?:TTemplatesConfigPartial}}> Mocked foundation options.
	 */
	public function foundationsOptions(
		?callable $data_resolver = null,
		array|int $foundation_names = 1,
		array|bool $withConfig = true
	): array {
		$arr = [];

		$count = is_array($foundation_names) ? count($foundation_names) : $foundation_names;

		$domains = is_int($foundation_names) ? $this->domainEntities($count) : $foundation_names;

		for ($i = 0; $i < $count; $i++) {
			$foundation_name = array_shift($domains);
			$arr = array_merge($arr, $this->foundationOption(
				$foundation_name,
				$data_resolver ? $data_resolver($foundation_name, $i, $withConfig) : null,
				is_bool($withConfig)
					? $withConfig
					: (
						$withConfig[$i] ?? (
							$withConfig[$foundation_name] ?? (
								$withConfig['*'] ?? true
							)
						)
					)
			));
		}

		return $arr;
	}

	/**
	 * Returns a mocked foundation option.
	 *
	 * @param string|null $foundation_name Foundation name.
	 * @param array|null $data Foundation data.
	 * @param array{
	 *   __count?: int,
	 *   __config?: array,
	 *   __templateNames?: array<string>,
	 * }|bool $withConfig Whether to include foundation configuration.
	 *
	 * @return array<array{data?:array,config?:array{template?:TTemplatesConfigPartial}}> Mocked foundation option.
	 */
	public function foundationOption(
		?string $foundation_name = null,
		?array $data = null,
		array|bool $withConfig = true
	): array {

		$foundation_name = $foundation_name ?? $this->domainEntity();

		$template_config = $this->templatesConfig(
			is_array($withConfig) && isset($withConfig['__count']) && is_int($withConfig['__count']) ? $withConfig['__count'] : 3,
			is_bool($withConfig) ? null : $withConfig,
			true
		);

		$option = [
			$foundation_name => [
				"data" => $data ?? [
					...$this->pathKeysContext([2, 6]),
					'attributes' => [
						'id',
						...$this->domainEntities([2, 12])
					],
				],
				...($withConfig ? ($withConfig['__config'] ?? [
					'config' => [
						'template' => $template_config
					]
				]) : []),
			]
		];

		return $option;
	}

	// public function foundation() : Foundation {
	// 	return
	// }





	public function ecosystemClass(): string
	{
		return MockEcosystem::class;
	}

	public function ecosystem(?array $withConfig = null): MockEcosystem
	{
		$c = $this->ecosystemClass();

		$res = function (Ecosystem $ecosystem) use ($withConfig): array {
			$template_config = Helper::mock()->templatesConfig(
				is_array($withConfig) ? $withConfig['__count'] : 3,
				$withConfig,
			);

			return $template_config;
		};

		return new $c($res);
	}





	/**
	 * Returns mocked template configurations.
	 *
	 * @param int $count Number of templates to return.
	 * @param (array<array>|array{
	 *   *?: TTCConfigArg|null,
	 *   __templateNames?: array<string>,
	 * })|null $config Template configuration overrides.
	 * @param bool $for_foundation Whether to use the foundation configuration format.
	 *
	 * @return array<TEcosystemTemplateConfigKeysPartial> Mocked template configurations.
	 */
	public function templatesConfig(
		int $count = 3,
		?array $config = null,
		bool $for_foundation = false
	): array {
		$arr = [];

		$names = $config['__templateNames'] ?? $this->templateNames($count);

		$count = count($names);

		for ($i = 0; $i < $count; $i++) {
			$name = $names[$i];
			$arr = array_merge($arr, $this->templateConfig($name, [...($config['*'] ?? []), ...($config[$i] ?? [])], $for_foundation));
		}

		return $arr;
	}

	/**
	 * Returns a mocked template configuration.
	 *
	 * @param string|null $template_name Template name.
	 * @param TTCConfigArg|null $config Template configuration overrides.
	 * @param bool $for_foundation Whether to use the foundation configuration format.
	 *
	 * @return array<array{config:TTemplateObjectConfig}|TTemplateObjectConfig> Mocked template configuration.
	 */
	public function templateConfig(
		?string $template_name = null,
		?array $config = null,
		bool $for_foundation = false
	): array {
		$template_name = $template_name ?? $this->templateName();

		$path_context = isset($config['__multiplePath'])
			? (is_array($config['__multiplePath'])
				? $config['__multiplePath']
				: $this->pathKeysContext(
					is_int($config['__multiplePath'])
						? $config['__multiplePath']
						: [2, 3]
				)
			)
			: null;

		$f_ = function () use ($path_context) {
			return $path_context;
		};

		$path = $path_context === null
			? $this->templatePath()
			: $this->templatePaths(count($path_context), naming_keys: $f_);

		$multiple_path = is_array($path);

		$count_path = is_array($path) ? count($path) : null;

		$between = $count_path !== null ? [$count_path, $count_path] : null;


		$extension = $this->resolve($config, 'extension', $this->resolveConfig(
			$config,
			$path,
			'extension',
			$multiple_path
				? $this->templateExtensions($between ?? [1, 3], $f_)
				: $this->templateExtension(),
		));

		$stub_file = $this->resolveConfig(
			$config,
			$path,
			'stub_file',
			$multiple_path ? $this->namingKeys($this->stubFiles($count_path), $f_) : $this->stubFile()
		);

		$generate = $this->resolveConfig(
			$config,
			$path,
			'generate',
			$multiple_path ? $this->namingKeys(array_fill(0, $count_path, true), $f_) : true
		);

		$templating = $this->resolveConfig(
			$config,
			$path,
			'templating',
			$multiple_path ? $this->namingKeys(array_fill(0, $count_path, []), $f_) : []
		);

		$conf = [
			"path" => $path,
			"extension" => $extension,
			"stub_file" => $stub_file,
			"generate" => $generate,

			"templating" => $templating,
		];

		$result = [
			$template_name => [
				"config" => $conf
			]
		];

		if ($for_foundation) {
			$result = [
				$template_name => $this->randomSubset($conf, random_int(1, count($conf))),
			];
		}

		return $result;
	}





	/**
	 * Returns mocked template paths.
	 *
	 * @param array<int,int>|int $between Minimum and maximum number of paths.
	 * @param callable|null $naming_keys Optional path key resolver.
	 *
	 * @return array Mocked template paths.
	 */
	public function templatePaths(array|int $between = [1, 3], ?callable $naming_keys = null): array
	{

		return $this->namingKeys($this->paths($between), $naming_keys);
	}

	/**
	 * Returns a mocked template path.
	 *
	 * @return string Mocked template path.
	 */
	public function templatePath(): string
	{

		return $this->path();
	}





	/**
	 * Returns mocked template extensions.
	 *
	 * @param array $between Minimum and maximum number of extensions.
	 * @param callable|null $naming_keys Optional key resolver.
	 *
	 * @return array Mocked template extensions.
	 */
	public function templateExtensions(array $between = [1, 3], ?callable $naming_keys = null): array
	{

		return $this->namingKeys($this->extensions($between), $naming_keys);
	}

	/**
	 * Returns a mocked template extension.
	 *
	 * @return string Mocked template extension.
	 */
	public function templateExtension(): string
	{

		return $this->extension();
	}





	public function templateStubFiles(array $between = [1, 3], ?callable $naming_keys = null): array
	{

		return $this->namingKeys($this->stubFiles($between), $naming_keys);
	}

	public function templateStubFile(?string $content = null, ?string $file_name = null, bool $scriptedContent = false): string
	{
		return $this->stubFile($content, $file_name, $scriptedContent);
	}





	public static function __callStatic(string $method, array $args): mixed
	{

		$mock = new Mock;

		return $mock->$method(...$args);
	}





	private function resolve(?array $data, string $k, mixed $defaultValue)
	{

		if ($data === null) return $defaultValue;

		return array_key_exists($k, $data)
			? (is_callable($data[$k])
				? $data[$k]($this, $defaultValue)
				: $data[$k]
			)
			: $defaultValue;
	}

	/**
	 * Resolves a configuration value for a single path or for multiple paths.
	 *
	 * When multiple paths are provided, configuration values are resolved
	 * independently for each path.
	 *
	 * @param array|null $withConfig Configuration overrides.
	 * @param string|array $dataPath Path or paths associated with the configuration.
	 * @param string $key Configuration key to resolve.
	 * @param mixed $defaultValue Default value used when no override is provided.
	 *
	 * @return mixed The resolved configuration value or values.
	 */
	private function resolveConfig(?array $withConfig, string|array $dataPath, string $key, mixed $defaultValue = null): mixed
	{

		if (is_string($dataPath)) {
			return is_array($withConfig) && array_key_exists($key, $withConfig)
				? (is_callable($withConfig[$key])
					? $withConfig[$key]($dataPath, $withConfig, $key, $defaultValue, null)
					: $withConfig[$key]
				)
				: $defaultValue;
		}

		$new_arr = [];

		foreach ($dataPath as $pathKey => $path) {

			$new_arr[$pathKey] = is_array($withConfig) && array_key_exists($key, $withConfig)
				? (is_array($withConfig[$key]) && array_key_exists($pathKey, $withConfig[$key])
					? (is_callable($withConfig[$key][$pathKey])
						? $withConfig[$key][$pathKey]($dataPath, $withConfig, $key, $defaultValue, $pathKey)
						: $withConfig[$key][$pathKey]
					)
					: (is_array($withConfig[$key]) ? current($withConfig[$key]) : $withConfig[$key])
				) : (array_key_exists($pathKey, $defaultValue)
					? $defaultValue[$pathKey]
					: $defaultValue
				);
		}

		return $new_arr;
	}

	/**
	 * Maps generated values to keys resolved by a callback.
	 *
	 * When no key resolver is provided, the original array is returned unchanged.
	 *
	 * @param array $data Values to map.
	 * @param callable|null $keyResolver Callback returning the keys to apply.
	 *
	 * @return array The values mapped to their resolved keys.
	 */
	private function namingKeys(array $data, ?callable $keyResolver)
	{
		if ($keyResolver) {
			$new_data = [];

			$keys = $keyResolver($data);

			foreach ($data as $path) {
				if (empty($keys))
					break;

				$new_data[array_shift($keys)] = $path;
			}

			return $new_data;
		}

		return $data;
	}
}

class MockEcosystem extends Ecosystem
{

	/**
	 * @var array<TEcosystemTemplateConfigKeysPartial>|\Closure(\Crafteus\Environment\Ecosystem $ecosystem): array<TEcosystemTemplateConfigKeysPartial>|null
	 */
	public static \CLosure|array|null $_template_config = null;

	private \CLosure|array|null $template_config_resolver = null;

	public function template(): array
	{

		$result = $this->getTemplateConfigResolver();

		return is_array($result) ? $result : ($result)($this);
	}

	/**
	 * @return array<TEcosystemTemplateConfigKeysPartial>|\Closure(\Crafteus\Environment\Ecosystem $ecosystem): array<TEcosystemTemplateConfigKeysPartial>
	 */
	private function getTemplateConfigResolver(): \CLosure|array
	{

		// if(static::$_template_config !== null){
		// 	$this->template_config_resolver = static::$_template_config;
		// }

		// if($this->template_config_resolver === null)
		// 	$this->template_config_resolver = Helper::mock()->templatesConfig();

		return static::$_template_config ?? Helper::mock()->templatesConfig();
	}
}
