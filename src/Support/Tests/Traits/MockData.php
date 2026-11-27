<?php

namespace Crafteus\Support\Tests\Traits;

use Crafteus\Support\Helper;

trait MockData
{
	/**
	 * The path prefix to prepend to generated paths.
	 *
	 * @var ?string
	 */
	public ?string $path_prefix = null;

	/**
	 * Returns the temporary directory where stubs and
	 * mock data are stored.
	 *
	 * @return ?string Temporary directory path.
	 */
	abstract public function getTempDir(): ?string;

	/**
	 * Checks if the temporary directory exists.
	 *
	 * @return bool Whether the temporary directory exists.
	 */
	abstract public function tempDirExists(): bool;

	/**
	 * Sets the path prefix to prepend to generated paths.
	 *
	 * @param ?string $path_prefix The path prefix to set.
	 *
	 * @return static The trait instance for method chaining.
	 */
	public function setPathPrefix(?string $path_prefix): static
	{
		$this->path_prefix = $path_prefix;

		return $this;
	}


	/**
	 * List of mocked template names.
	 *
	 * @return list<string> Mocked template names.
	 */
	private function mockTemplateNames(): array
	{
		return [
			'model',
			'repository',
			'observer',
			'migration',
			'controller',
			'service',
			'factory',
			'seeder',
			'middleware',
			'request',
			'response',
			'resource',
			'transformer',
			'validator',
			'policy',
			'provider',
			'listener',
			'subscriber',
			'event',
			'command',
			'handler',
			'job',
			'scheduler',
			'notification',
			'mail',
			'rule',
			'exception',
			'interface',
			'trait',
			'contract',
			'entity',
			'value-object',
			'dto',
			'vo',
			'aggregate',
			'adapter',
			'gateway',
			'client',
			'factory',
			'builder',
			'parser',
			'serializer',
			'normalizer',
			'resolver',
			'dispatcher',
			'decorator',
			'strategy',
			'specification',
			'query',
			'query-handler',
			'command-handler',
			'use-case',
			'action',
			'workflow',
			'pipeline',
			'hook',
			'subscriber',
			'schema',
			'fixture',
			'prototype',
			'definition',
			'configuration',
			'bootstrap',
			'kernel',
			'route',
			'resource',
			'view',
			'presenter',
			'exporter',
			'importer',
			'indexer',
			'searcher',
			'calculator',
			'formatter',
			'converter',
			'hydrator',
			'persister',
			'registry',
			'resolver',
			'locator',
			'manager',
			'coordinator',
			'orchestrator',
		];
	}


	/**
	 * Returns a random subset of mocked template names.
	 *
	 * The original array is shuffled before the requested
	 * number of elements is retained.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of names.
	 *
	 * @return list<string> Mocked template names.
	 */
	public function templateNames(array|int $between = [1, 3]): array
	{
		return $this->randomSubset(
			$this->mockTemplateNames(),
			is_int($between)
				? $between
				: random_int($between[0] ?? 1, $between[1] ?? 3)
		);
	}


	/**
	 * Returns a single mocked template name.
	 *
	 * @return string Mocked template name.
	 */
	public function templateName(): string
	{
		return current($this->randomSubset($this->mockTemplateNames(), 1));
	}


	/**
	 * List of mocked relative paths.
	 *
	 * @return list<string>
	 *         Mocked relative paths.
	 */
	private function mockRelativePaths(): array
	{
		return [
			'templates/dashboard',
			'templates/errors',
			'templates/notifications',
			'templates/reports',
			'templates/account',
			'templates/orders',
			'templates/billing',
			'templates/security',
			'resources/views',
			'resources/views/emails',
			'resources/views/pages',
			'resources/views/components',
			'resources/views/layouts',
			'resources/views/partials',
			'resources/views/admin',
			'resources/views/auth',
			'resources/views/dashboard',
			'src/Resources/views',
			'src/Resources/templates',
			'app/Views',
			'app/Views/layouts',
			'app/Views/components',
			'app/Templates',
			'app/Templates/emails',
			'app/Templates/pages',
			'app/Templates/shared',
			'modules/User/templates',
			'modules/Order/templates',
			'modules/Admin/templates',
			'modules/Notification/templates',
			'shared/templates',
			'vendor/package/templates',
			'templates',
			'templates/emails',
			'templates/pages',
			'templates/components',
			'templates/partials',
			'templates/forms',
			'templates/admin',
			'templates/auth',
			'resources',
			'resources/templates',
			'src',
			'src/templates',
			'src/views',
			'src/Resources',
			'app',
			'app/views',
			'app/templates',
			'modules',
			'modules/User',
			'modules/Admin',
			'modules/Order',
			'shared',
			'storage/templates',
			'var/templates',
			'public/templates',
			'config/templates',
			'data/templates',
			'tests/fixtures/templates',
			'tests/resources',
			'tests/resources/templates',
		];
	}


	/**
	 * Returns a random subset of mocked relative paths.
	 *
	 * If the temporary directory exists, the prefix
	 * is prepended to each path.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of paths.
	 *
	 * @return list<string> Mocked relative paths.
	 */
	public function paths(array|int $between = [1, 3]): array
	{
		$paths = $this->randomSubset(
			$this->mockRelativePaths(),
			is_int($between)
				? $between
				: random_int(
					$between[0] ?? 1,
					$between[1] ?? 3
				)
		);

		if ($this->tempDirExists()) {
			$paths = array_map(
				fn(string $path) => $this->getTempDir()
					. DIRECTORY_SEPARATOR
					. $this->pathPrefix($path),
				$paths
			);
		}

		return $paths;
	}


	/**
	 * Returns a single mocked relative path.
	 *
	 * If the temporary directory exists, the full path
	 * is constructed with the temp directory prefix.
	 *
	 * @return string Mocked relative path.
	 */
	public function path(): string
	{
		$path = $this->pathPrefix(
			current($this->randomSubset(
				$this->mockRelativePaths(),
				1
			))
		);

		if ($this->tempDirExists()) {
			$path = $this->getTempDir()
				. DIRECTORY_SEPARATOR
				. $path;
		}

		return $path;
	}


	/**
	 * Prepends the path prefix to the given path.
	 *
	 * @param string $path The path to prepend the prefix to.
	 *
	 * @return string The path with the prefix prepended.
	 */
	public function pathPrefix(string $path = ''): string
	{
		return ($this->path_prefix
			? $this->path_prefix . ($path !== ''
				? DIRECTORY_SEPARATOR
				: ''
			) : ''
		) . $path;
	}


	// public function getPathPrefix() : ?string {
	// 	return $this->path_prefix;
	// }


	/**
	 * List of mocked file extensions.
	 *
	 * @return list<string> Mocked file extensions.
	 */
	private function mockExtensions(): array
	{
		return [
			'html',
			'htm',
			'twig',
			'blade.php',
			'php',
			'phtml',
			'tpl',
			'mustache',
			'hbs',
			'handlebars',
			'ejs',
			'njk',
			'nunjucks',
			'jinja',
			'jinja2',
			'liquid',
			'latte',
			'smarty',
			'xhtml',
			'xml',
			'svg',
			'md',
			'mdx',
			'txt',
			'text',
			'json',
			'yaml',
			'yml',
			'css',
			'scss',
			'sass',
			'less',
			'js',
			'mjs',
			'cjs',
			'jsx',
			'tsx',
			'vue',
			'svelte',
		];
	}


	/**
	 * Returns a random subset of mocked file extensions.
	 *
	 * The original array is shuffled before the requested number of elements
	 * is retained.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of extensions.
	 *
	 * @return list<string> Mocked file extensions.
	 */
	public function extensions(array|int $between = [1, 3]): array
	{
		return $this->randomSubset(
			$this->mockExtensions(),
			is_int($between)
				? $between
				: random_int(
					$between[0] ?? 1,
					$between[1] ?? 3
				)
		);
	}


	/**
	 * Returns a single mocked file extension.
	 *
	 * @return string
	 *         Mocked file extension.
	 */
	public function extension(): string
	{
		return current($this->randomSubset($this->mockExtensions(), 1));
	}


	/**
	 * Generates a random subset of stub files.
	 *
	 * The original array is shuffled before
	 * the requested number of elements is retained.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of stub files.
	 *
	 * @return list<string> Mocked stub file paths or content.
	 */
	public function stubFiles(array|int $between = [1, 3]): array
	{
		$length = is_int($between)
			? $between
			: random_int($between[0] ?? 1, $between[1] ?? 3);

		$arr = [];

		for ($i = 0; $i < $length; $i++) {
			$arr[] = $this->stubFile();
		}

		return $arr;
	}


	/**
	 * Generates a stub file with optional content.
	 *
	 * If no content is provided, a default placeholder is used.
	 * If the temporary directory exists, the stub is saved to disk.
	 *
	 * @param ?string $content
	 *        Optional content for the stub file.
	 * @param ?string $file_name
	 *        Optional filename for the stub file.
	 * @param bool $scriptedContent
	 *        Whether the content should be PHP
	 *        script code.
	 *
	 * @return string The path to the stub file or the content itself.
	 */
	public function stubFile(?string $content = null, ?string $file_name = null, bool $scriptedContent = false): string
	{
		if ($content === null) {
			$content = "{{content}}";

			if ($scriptedContent) {
				$content = "<?php\n\t echo \"scripted-\" . uniqid(); ?>{$content}";
			}
		}

		if ($this->tempDirExists()) {
			$this->makeStubDirectory();

			$file_path = $this->resolveStubDirectory(
				($file_name ?? $this->templateName())
					. ($scriptedContent
						? ".stub.php"
						: ".stub"
					)
			);

			file_put_contents($file_path, $content);

			return $file_path;
		}

		return $content;
	}


	/**
	 * Resolves the stub directory path.
	 *
	 * @param ?string $path
	 *        Optional additional path within
	 *        the stub directory.
	 *
	 * @return string The resolved stub directory path.
	 */
	protected function resolveStubDirectory(?string $path = null): string
	{
		return Helper::normalizePath($this->getTempDir()
			. DIRECTORY_SEPARATOR
			. 'stub'
			. ($path ? DIRECTORY_SEPARATOR . $path : ""));
	}


	/**
	 * Creates the stub directory if it does not exist.
	 *
	 * @return void
	 *
	 * @throws \Error if the directory is not writable.
	 */
	private function makeStubDirectory(): void
	{
		$stub_directory = $this->resolveStubDirectory();

		if (!is_dir($stub_directory)) {
			mkdir($stub_directory);
		}

		if (!is_writable($stub_directory)) {
			throw new \Error("Error: The temporary directory `{$this->tempDir}` is not writable.");
		}
	}





	/**
	 * List of mocked domain entities.
	 *
	 * @return list<string> Mocked domain entity names.
	 */
	private function mockDomainEntities(): array
	{
		return [
			'user',
			'person',
			'customer',
			'account',
			'profile',
			'address',
			'company',
			'organization',
			'team',
			'member',
			'role',
			'permission',
			'order',
			'product',
			'item',
			'cart',
			'invoice',
			'payment',
			'transaction',
			'subscription',
			'plan',
			'subscription',
			'invoice',
			'refund',
			'currency',
			'category',
			'tag',
			'comment',
			'post',
			'article',
			'document',
			'file',
			'media',
			'image',
			'notification',
			'message',
			'conversation',
			'session',
			'token',
			'credential',
			'api-key',
			'device',
			'login',
			'audit',
			'event',
			'activity',
			'setting',
			'preference',
			'configuration',
			'location',
			'country',
			'region',
			'department',
			'project',
			'task',
			'ticket',
			'issue',
			'attachment',
			'review',
			'rating',
			'coupon',
			'discount',
			'shipment',
			'delivery',
			'warehouse',
			'inventory',
			'supplier',
			'employee',
			'contract',
			'appointment',
			'schedule',
			'calendar',
			'session',
			'report',
			'log',
			'webhook',
			'integration',
		];
	}


	/**
	 * Returns a random subset of mocked domain entities.
	 *
	 * The original array is shuffled before the requested
	 * number of elements is retained.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of entities.
	 *
	 * @return list<string> Mocked domain entity names.
	 */
	public function domainEntities(array|int $between = [1, 10]): array
	{
		return $this->randomSubset(
			$this->mockDomainEntities(),
			is_int($between)
				? $between
				: random_int($between[0] ?? 1, $between[1] ?? 10)
		);
	}


	/**
	 * Returns a single mocked domain entity.
	 *
	 * @return string Mocked domain entity name.
	 */
	public function domainEntity(): string
	{
		return current($this->randomSubset($this->mockDomainEntities(), 1));
	}





	/**
	 * List of mocked path keys for context.
	 *
	 * @return list<string> Mocked path key names.
	 */
	private function mockPathKeysContext(): array
	{
		return [
			'web',
			'api',
			'auth',
			'admin',
			'cli',
			'console',
			'mobile',
			'frontend',
			'backend',
			'internal',
			'external',
			'public',
			'private',
			'secure',
			'oauth',
			'graphql',
			'rest',
			'rpc',
			'webhook',
			'gateway',
			'integration',
			'system',
			'client',
			'server',
			'worker',
			'queue',
			'cron',
			'scheduler',
			'command',
			'event',
			'notification',
			'sms',
			'storage',
			'cache',
			'database',
			'search',
			'reporting',
			'analytics',
			'account',
			'identity',
			'security',
			'configuration',
			'health',
			'monitoring',
			'debug',
		];
	}


	/**
	 * Returns a random subset of mocked path keys for context.
	 *
	 * The original array is shuffled before the requested number
	 * of elements is retained.
	 *
	 * @param array<int,int>|int $between
	 *        Minimum and maximum number of keys.
	 *
	 * @return list<string> Mocked path key names.
	 */
	public function pathKeysContext(array|int $between = [1, 3]): array
	{
		return $this->randomSubset(
			$this->mockPathKeysContext(),
			is_int($between)
				? $between
				: random_int($between[0] ?? 1, $between[1] ?? 3)
		);
	}


	/**
	 * Returns a single mocked path key for context.
	 *
	 * @return string Mocked path key name.
	 */
	public function pathKeyContext(): string
	{
		return current($this->randomSubset($this->mockPathKeysContext(), 1));
	}


	/**
	 * Returns a random subset of the provided array.
	 *
	 * The original array is shuffled before the requested
	 * number of elements is retained.
	 *
	 * @param array $data Source data.
	 * @param int $count
	 *        Maximum number of elements to retain.
	 *
	 * @return array
	 *         A randomly selected subset of the source
	 *         data.
	 */
	private function randomSubset(array $data, int $count = 3): array
	{
		$length = count($data);

		$fndts = Helper::shuffleAssoc($data);

		array_splice($fndts, abs($count));

		return $fndts;
	}
}
