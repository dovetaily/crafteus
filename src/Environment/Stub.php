<?php

namespace Crafteus\Environment;

use Crafteus\Contracts\TemplatingInterface;
use Crafteus\Crafteus;
use Crafteus\Environment\Support\Templating;
use Crafteus\Exceptions\BaseErrorException;
use Crafteus\Exceptions\BaseException;
use Crafteus\Exceptions\DirectoryCreationException;
use Crafteus\Exceptions\FileDeletionException;
use Crafteus\Exceptions\FileGenerationException;
use Crafteus\Exceptions\FileLoadStubException;
use Crafteus\Exceptions\FileNotReadableException;
use Crafteus\Exceptions\FileWriteStubException;
use Crafteus\Exceptions\PermissionDeniedException;
use Crafteus\Exceptions\PhpStubException;
use Crafteus\Exceptions\TemplatingException;
use Crafteus\Exceptions\UrlLoadStubException;
use SplFileInfo;
use Crafteus\Support\Helper;
use Exception;
use Stringable;

/**
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 * @template T_Template of \Crafteus\Environment\Template
 * @template T_Templating of \Crafteus\Environment\Support\Templating|\Crafteus\Contracts\TemplatingInterface
 */
class Stub extends SplFileInfo
{
	/**
	 * Unique identifier for the stub.
	 *
	 * @var string
	 */
	public readonly string $key_id;

	/**
	 * Determines if the file should be generated.
	 * If set to false, the stub file will not be created.
	 *
	 * @var bool
	 */
	public readonly bool $state_generate;

	/**
	 * The file, URL, or content source of the stub.
	 *
	 * @var string
	 */
	protected string $origin_stub;

	/**
	 * The type of the stub source.
	 *
	 * @var 'file'|'url'|'content'
	 */
	protected string $origin_type;

	/**
	 * The current content of the stub.
	 *
	 * @var string|null
	 */
	protected string|null $current_content = null;

	/**
	 * Indicates whether the stub file has been generated.
	 *
	 * @var bool
	 */
	protected bool $is_generated = false;

	/**
	 * Indicates whether the file already exists.
	 *
	 * @var bool
	 */
	protected bool $already_exists = false;

	/**
	 * File extension.
	 *
	 * @var string
	 */
	protected readonly string $extension;

	/**
	 * File basename.
	 *
	 * @var string
	 */
	protected readonly string $basename;

	/**
	 * The templating mechanism used.
	 *
	 * @var class-string<T_Templating|\Crafteus\Contracts\TemplatingInterface<static>>
	 *      |(
	 *        \Closure(\Crafteus\Environment\Stub<T_Ecosystem,T_Template> $stub): T_Templating
	 *          |\Crafteus\Contracts\TemplatingInterface<static>
	 *          |array<mixed>
	 *          |null
	 *      )
	 *      |null
	 */
	protected string|\Closure|null $templating;

	/**
	 * Stores the last templating result.
	 *
	 * @var array
	 *      |T_Templating
	 *      |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *      |\Crafteus\Contracts\TemplatingInterface<static>
	 *      |null
	 */
	protected array|Templating|TemplatingInterface|null $last_templating = null;

	/**
	 * Stores content that cannot be written to a file
	 * due to write restrictions.
	 *
	 * When file writing is disabled, new content is stored in this property
	 * until writing is allowed again.
	 *
	 * @var string|null $deferred_content
	 *      The deferred content awaiting file write.
	 */
	protected $deferred_content;

	/**
	 * Template used for this stub.
	 *
	 * @var T_Template|null
	 */
	private Template|null $template = null;

	/**
	 * The path of the generated file.
	 *
	 * @var string
	 */
	private string $file_path;

	/**
	 * Stores the previous content of the stub file.
	 *
	 * @var string|null
	 */
	private string|null $old_content = null;

	/**
	 * Stores errors encountered during stub processing.
	 *
	 * @var array
	 */
	private array $errors = [];

	/**
	 * Stub constructor.
	 *
	 * @param string $stub
	 *        The stub source (file, URL, or content).
	 * @param T_Template|null $template
	 *        The template associated with the stub.
	 * @param string $directory
	 *        The directory path.
	 * @param string $basename
	 *        Base name of file.
	 * @param string|null $extension File extension.
	 * @param bool $generate
	 *        Whether the stub should be generated.
	 * @param string|int|null|null $key_id
	 *        Unique identifier for the stub.
	 */
	public function __construct(
		string $stub,
		?Template $template,
		string $directory,
		string $basename,
		string|null $extension = '',
		bool $generate = true,
		string|int|null $key_id = null
	) {
		$file_path = Helper::normalizePath($directory . '/'  . $basename . (!empty($extension) ? '.' . $extension : ''));

		parent::__construct($file_path);

		$this->basename = $basename;

		$this->extension = $extension;

		$this->state_generate = $generate;

		$this->template = $template;

		$this->setFilePath($file_path);

		$this->key_id = $key_id;

		$this->already_exists = $this->isFile();

		$this->initOldContent();

		$this->setOriginStub($stub);

		$this->setCurrentContent($this->getStubContent());
	}





	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return T_Ecosystem Ecosystem instance.
	 */
	public function getEcosystem(): ?Ecosystem
	{
		return $this->getTemplate()?->getEcosystem();
	}

	/**
	 * Gets the associated template.
	 *
	 * @return T_Template|null
	 */
	public function getTemplate(): ?Template
	{
		return $this->template;
	}

	/**
	 * Retrieves the configuration filtered by Stub
	 * from the Template's public properties.
	 * For certain properties, it applies a key-based
	 * filtering using the `getUniqueConfig` method.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param \Closure(mixed $config):bool|null $check_type
	 *
	 * @return mixed
	 *         The filtered configuration of the Template.
	 */
	public function getConfig(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null
	): mixed {
		$result = $this->getTemplate()?->getConfig() ?? [];

		foreach ($result as $key_ => $conf) {
			if (in_array(
				$key_,
				array_unique(
					array_merge(
						Template::UNIQUE_STUB_CONFIG_PROPERTIES,
						$this->getTemplate()?->getUniqueStubConfigProperties() ?? []
					)
				)
			)) {
				$result[$key_] = $this->getTemplate()
					? $this->getTemplate()::getUniqueConfig($conf, $this->key_id, config_key: $key_)
					: Template::getUniqueConfig($conf, $this->key_id);
			}
		}

		if ($key === null)
			return $result;

		$config = Helper::getNestedArrayValue($key, $result, $default_value);

		if (
			$config['found'] &&
			$check_type !== null &&
			!$check_type($config['result'])
		)
			return $default_value;

		return $config['result'];
	}

	/**
	 * Retrieves the template data.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * @param \Closure(mixed $data):bool|null $check_type
	 *
	 * @return mixed
	 */
	public function getData(
		string|null $key = null,
		$default_value = null,
		\Closure|null $check_type = null
	): mixed {
		return $this->getTemplate()
			? $this->getTemplate()->getData(
				$key,
				$default_value,
				check_type: $check_type
			)
			: null;
	}





	/**
	 * Gets the origin stub source.
	 *
	 * @return string
	 */
	public function getOriginStub(): string
	{
		return $this->origin_stub;
	}

	/**
	 * Gets the origin stub type.
	 *
	 * @return 'file'|'url'|'content'
	 */
	public function getOriginType(): string
	{
		return $this->origin_type;
	}

	/**
	 * Retrieves the content of the stub.
	 *
	 * @return string
	 *
	 * @throws \Crafteus\Exceptions\UrlLoadStubException
	 *         If the remote resource cannot be retrieved.
	 * @throws \Crafteus\Exceptions\FileLoadStubException
	 *         If the local file cannot be loaded.
	 */
	public function getStubContent(): string
	{
		$result = in_array($this->getOriginType(), ['file', 'url'])
			? $this->getFileContent($this->getOriginStub(), $this->getOriginType() === 'url')
			: $this->getOriginStub();

		return $result;
	}

	/**
	 * Sets the origin stub.
	 *
	 * @param string $stub
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\PermissionDeniedException
	 *         If the file exists but is not readable.
	 */
	protected function setOriginStub(string $stub): void
	{
		if (Helper::isUrl($stub)) {
			$this->origin_type = 'url';
		} elseif (is_file($stub)) {
			if (!is_readable($stub)) {
				$this->addErrors(new PermissionDeniedException(
					$stub,
					code: 5501
				));
			}

			$this->origin_type = 'file';
		} else {
			$this->origin_type = 'content';
		}

		$this->origin_stub = $stub;
	}

	/**
	 * Gets the file path of the stub.
	 *
	 * @return string
	 */
	public function getFilePath(): string
	{
		return $this->file_path;
	}

	/**
	 * Get the relative directory path.
	 *
	 * @return string The relative directory path.
	 */
	public function getRelativePath(): string
	{
		return Helper::relativePath($this->getPath());
	}

	/**
	 * Get the relative file path.
	 *
	 * @return string The relative file path.
	 */
	public function getRelativeFilePath(): string
	{
		return Helper::relativePath($this->getFilePath());
	}

	/**
	 * Get file extension.
	 *
	 * @return string
	 */
	public function getExtension(): string
	{
		return $this->extension;
	}

	/**
	 * Gets the base name of the file
	 *
	 * @return string
	 *         The base name without path information.
	 */
	public function getBasename(string $suffix = ""): string
	{
		return $this->basename . $suffix;
	}

	/**
	 * Sets the file path of the stub.
	 *
	 * @param string $file_path
	 *
	 * @return void
	 */
	private function setFilePath(string $file_path): void
	{
		$this->file_path = $file_path;
	}

	/**
	 * Creates the directory path if it does not exist.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\DirectoryCreationException
	 *         If directory creation fails.
	 */
	public function makePathDirectory($permissions = 0777): void
	{
		$path = $this->getPath();

		set_error_handler(
			fn($severity, $message, $file, $line) => $this->addErrors(new DirectoryCreationException(
				$path,
				code: 9501,
				previous: new BaseErrorException(DirectoryCreationException::class, $message, 9501, $severity, $file, $line)
			)),
			E_WARNING
		);

		if (!is_dir($path))
			mkdir($path, $permissions, true);

		restore_error_handler();
	}

	/**
	 * Deletes the generated file.
	 *
	 * @param bool $remove_empty_parents
	 *        Whether to remove empty parent directories.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\FileDeletionException
	 *         If file deletion fails.
	 */
	public function deleteFile(bool $remove_empty_parents = false): void
	{
		if (!$this->isFile()) return;

		$path = $this->getFilePath();

		set_error_handler(
			fn($severity, $message, $file, $line) => $this->addErrors(new FileDeletionException(
				$path,
				code: 9502,
				previous: new BaseErrorException(FileDeletionException::class, $message, 9502, $severity, $file, $line)
			)),
			E_WARNING
		);

		unlink($path);

		if ($remove_empty_parents)
			Helper::removeEmptyParentDirectories($this->getPath());

		restore_error_handler();
	}






	/**
	 * Retrieves the current content of the stub.
	 *
	 * @return string|null
	 */
	public function getCurrentContent(): ?string
	{
		return Crafteus::$disable_file_writing ? $this->getDeferredContent() : $this->current_content;
	}

	/**
	 * Sets the current content of the stub.
	 *
	 * @param string|null $content
	 *
	 * @return static
	 */
	public function setCurrentContent(string|null $content = null): static
	{
		if (Crafteus::$disable_file_writing) $this->setDeferredContent($content);
		$this->current_content = $content;
		return $this;
	}

	/**
	 * Appends content to the current content.
	 *
	 * @param string|null $content The content to append.
	 * @param bool $inline
	 * If `true`, the content is appended without inserting a line break first.
	 *
	 * @return static
	 */
	public function addCurrentContent(string $content, bool $inline = false): static
	{
		if (Crafteus::$disable_file_writing) $this->addDeferredContent($content, $inline);

		if (
			is_string($this->current_content) &&
			$this->current_content !== ""
		) {
			$this->current_content .= ($inline === true
				? ""
				: PHP_EOL
			) . $content;
		} else {
			$this->setCurrentContent($content);
		}

		return $this;
	}

	public function getDeferredContent(): ?string
	{
		return $this->deferred_content;
	}

	/**
	 * [Description for setDeferredContent]
	 *
	 * @param string|null $content
	 *
	 * @return static
	 */
	public function setDeferredContent(string|null $content): static
	{
		$this->deferred_content = $content;

		return $this;
	}

	/**
	 * [Description for addDeferredContent]
	 *
	 * @param string $content
	 * @param bool $inline
	 *
	 * @return static
	 */
	public function addDeferredContent(string $content, bool $inline = false): static
	{

		if (
			is_string($this->deferred_content) &&
			$this->deferred_content !== ""
		) {
			$this->deferred_content .= ($inline ? "" : PHP_EOL) . $content;
		} else {
			$this->setDeferredContent($content);
		}

		return $this;
	}

	/**
	 * Initializes the old content of the file if it already exists.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\FileNotReadableException
	 *         If the file is not readable.
	 * @throws UrlLoadStubException
	 *         If the remote resource cannot be retrieved.
	 * @throws FileLoadStubException
	 *         If the local file cannot be loaded.
	 */
	private function initOldContent(): void
	{
		if ($this->isFile()) {

			if (!is_readable($f = $this->getFilePath())) {
				$this->addErrors(new FileNotReadableException(
					$f,
					code: 9500
				));
			}

			$this->setOldContent($this->getFileContent($this->getFilePath()));
		}
	}

	/**
	 * Retrieves the old content of the file.
	 *
	 * @return string|null
	 */
	public function getOldContent(): ?string
	{
		return $this->old_content;
	}

	/**
	 * Sets the old content of the file.
	 *
	 * @param string $content
	 *
	 * @return void
	 */
	public function setOldContent(string $content): void
	{
		$this->old_content = $content;
	}






	/**
	 * Gets the templating mechanism.
	 *
	 * @return class-string<
	 *     T_Templating
	 *     |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *     |\Crafteus\Contracts\TemplatingInterface<static>
	 *   >
	 *   |(
	 *     \Closure(\Crafteus\Environment\Stub<T_Ecosystem,T_Template> $stub): T_Templating
	 *       |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *       |\Crafteus\Contracts\TemplatingInterface<static>
	 *       |array<mixed>
	 *       |null
	 *   )
	 *   |null
	 */
	public function getTemplating(): string|\Closure|null
	{
		return $this->templating;
	}

	/**
	 * Sets the templating mechanism.
	 *
	 * @param class-string<
	 *     T_Templating
	 *     |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *     |\Crafteus\Contracts\TemplatingInterface<static>
	 *   >
	 *   |(
	 *     \Closure(\Crafteus\Environment\Stub<T_Ecosystem,T_Template> $stub): T_Templating
	 *       |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *       |\Crafteus\Contracts\TemplatingInterface<static>
	 *       |array<mixed>
	 *       |null
	 *   )
	 *   |null $templating
	 *
	 * @return static
	 */
	public function setTemplating(string|\Closure|null $templating): static
	{
		$this->templating = $templating;
		return $this;
	}

	/**
	 * Retrieves the previous templating result.
	 *
	 * @return array
	 *         |T_Templating
	 *         |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *         |\Crafteus\Contracts\TemplatingInterface<static>
	 *         |null
	 */
	public function getOldTemplating(): array|Templating|TemplatingInterface|null
	{
		return $this->last_templating;
	}

	/**
	 * Returns an instance of the templating engine.
	 *
	 * If a previous instance exists and `$force_new` is false, that instance is reused.
	 * Otherwise, a new instance is created using the provided class name or the one returned by `getTemplating()`.
	 *
	 * If the class does not exist or is not a string, `null` is returned.
	 *
	 * If `$update_current_content` is true, the current content of the stub is retrieved
	 * and set on the new instance via `setCurrentContent()`.
	 *
	 * If `$set_last` is true, the created or reused instance is stored via `setLastTemplating()`.
	 *
	 * @param string|null $templating
	 *        The fully qualified class name
	 *        of the templating engine to instantiate.
	 *        Falls back to `getTemplating()` if null.
	 * @param bool $set_last
	 *        Whether to store the instance
	 *        as the last used templating engine.
	 * @param bool $force_new
	 *        Whether to force creation of a new instance
	 *        even if one already exists.
	 * @param bool $update_current_content
	 *        Whether to update the new instance
	 *        with the current stub content.
	 *
	 * @return T_Templating
	 *         |\Crafteus\Environment\Support\Templating<T_Ecosystem,static>
	 *         |\Crafteus\Contracts\TemplatingInterface<static>
	 *         |null
	 *         The templating engine instance,
	 *         or null if instantiation failed.
	 */
	public function getTemplatingInstance(
		?string $templating = null,
		bool $set_last = false,
		bool $force_new = false,
		bool $update_current_content = true
	): Templating|TemplatingInterface|null {
		$templating = $templating ?? $this->getTemplating();

		if (
			!is_string($templating) ||
			!class_exists($templating)
		) {
			return null;
		}

		$instance = is_object($this->getOldTemplating()) && !$force_new
			? $this->getOldTemplating()
			: new $templating(...[$this]);

		if ($update_current_content) {
			$instance->setCurrentContent($instance->getStub()->getCurrentContent());
		}

		if ($set_last) {
			$this->setLastTemplating($instance);
		}

		return $instance;
	}

	/**
	 * Sets the last templating result.
	 *
	 * @param mixed $value
	 *
	 * @return void
	 */
	private function setLastTemplating($value): void
	{
		if ($value !== $this->last_templating) {
			$this->last_templating = $value;
		}
	}

	/**
	 * Generates content using the templating mechanism.
	 *
	 * @return bool
	 *
	 * @deprecated use `applyContentWithTemplating`
	 */
	public function generateContentWithTemplating(): bool
	{
		return $this->applyContentWithTemplating();
	}

	/**
	 * Apply content using the templating mechanism.
	 *
	 * @return bool
	 *
	 * @throws TemplatingException
	 */
	public function applyContentWithTemplating(): bool
	{
		$result = false;

		$templating = $this->getTemplating();

		try {

			if ((is_string($templating) && class_exists($templating)) || is_callable($templating)) {

				if (is_string($templating) && class_exists($templating)) {

					$_templating = $this->getTemplatingInstance();

					if (method_exists($_templating, 'run'))
						$_templating->run();

					// if(!is_object($this->getOldTemplating()))

					$this->setLastTemplating($_templating);

					$result = true;
				} else if (!is_string($templating)) {

					$res = ($templating)(...[$this]);

					if (is_array($res)) {
						$this->setLastTemplating($res);
					}

					$result = true;
				}
			} else if ($this->getTemplate() !== null && method_exists($this->getTemplate(), $m = 'templatingRun')) {

				$res = $this->getTemplate()->$m(...[$this]);

				if (is_array($res)) {
					$this->setLastTemplating($res);
				}

				$result = true;
			}
		} catch (\Throwable $th) {
			$this->addErrors(new TemplatingException(
				$this->getTemplate()?->getTemplateName() ?? 'unknown-template-name',
				$this->getTemplate()
					? get_class($this->getTemplate())
					: 'unknown-template-class',
				$this->key_id,
				is_string($templating)
					? $templating
					: (is_callable($templating)
						? (new \ReflectionFunction($templating))->__toString()
						: null
					),
				code: 6501,
				previous: $th
			));
		}

		return $result;
	}

	/**
	 * Executes the PHP stub and captures the output.
	 *
	 * @return void
	 *
	 * @throws \Crafteus\Exceptions\PhpStubException
	 *         If an error occurred in the PHP stub file.
	 */
	public function phpStub(): void
	{
		try {

			$c = (function ($stub) {

				ob_start();

				include $stub->getOriginStub();

				return ob_get_clean();
			})($this);

			$this->setCurrentContent($c)->applyContentFile();
		} catch (\Throwable $th) {

			$this->addErrors(new PhpStubException(
				$this->getOriginStub(),
				code: 5503,
				previous: $th
			));
		}
	}






	/**
	 * Generates the stub file content.
	 *
	 * @param string|null $content
	 *
	 * @return bool
	 *
	 * @deprecated use `applyContentFile`
	 */
	public function generateContentFile(?string $content = null): bool
	{
		return $this->applyContentFile($content);
	}

	/**
	 * Cancels the content generate in file.
	 *
	 * @return bool
	 *
	 * @deprecated use `cancelContentApply` method.
	 */
	public function cancelGenerateContent(): bool
	{
		return $this->cancelContentApply();
	}

	/**
	 * Apply the stub file content.
	 *
	 * @param string|null $content
	 *
	 * @return bool
	 *
	 * @throws \Crafteus\Exceptions\FileWriteStubException
	 *         If the file cannot be written.
	 */
	public function applyContentFile(?string $content = null): bool
	{
		if ($this->isWritable()) {
			if (Crafteus::$disable_file_writing) {
				$this->setDeferredContent($content ?? $this->getCurrentContent());
			} else {
				$this->putContentFile($this->getFilePath(), $content ?? $this->getCurrentContent());
			}

			return true;
		}

		return false;
	}

	/**
	 * Cancels the content applied in file.
	 *
	 * @return bool
	 */
	public function cancelContentApply(): bool
	{
		return $this->applyContentFile($this->getOldContent());
	}

	/**
	 * Generates the stub file.
	 *
	 * @param bool $force
	 * @param bool $is_generated
	 *
	 * @return bool
	 *
	 * @throws \Crafteus\Exceptions\FileGenerationException
	 *         If file generation fails.
	 */
	public function generateFile(bool $force = true, bool $is_generated = true): bool
	{
		if ($this->state_generate && (!$this->isFile() || ($this->isFile() && $force))) {
			if (!Crafteus::$disable_file_writing) {
				$this->makePathDirectory();
			}

			try {
				if ($this->getOriginType() === 'file') {
					if (Crafteus::$disable_file_writing) {
						$this->setDeferredContent(
							file_get_contents($this->getOriginStub())
						);
					} else {
						copy($this->getOriginStub(), $this->getFilePath());
					}
				} else {
					if (Crafteus::$disable_file_writing) {
						$this->setDeferredContent($this->getStubContent());
					} else {
						$this->putContentFile($this->getFilePath(), $this->getStubContent());
					}
				}
			} catch (\Throwable $th) {
				new FileGenerationException(
					$this->getFilePath(),
					$this->getPath(),
					code: 5502,
					previous: $th
				);
			}

			$this->is_generated = $is_generated;

			$this->applyContentFile();

			return true;
		} elseif ($this->state_generate && $this->isFile()) {
			$this->addErrors(new Exception("File already exists : `{$this->getFilePath()}`"));
		}

		return false;
	}

	/**
	 * Cancels the file generation and restores previous
	 * content or deletes the file.
	 *
	 * @return bool
	 */
	public function cancelGenerateFile(): bool
	{
		$this->is_generated = false;

		if ($this->already_exists) {
			try {
				if (!$this->isFile()) {
					$this->generateFile(is_generated: false);
				}

				$this->cancelContentApply();
			} catch (\Error $th) {
				return false;
			}
		} else {
			try {
				$this->deleteFile(true);
			} catch (\Throwable $th) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the file has been generated.
	 *
	 * @return bool
	 */
	public function isGenerated(): bool
	{
		return $this->is_generated;
	}






	/**
	 * Checks if any errors exist.
	 *
	 * @return bool
	 */
	public function errorExists(): bool
	{
		return count($this->errors) > 0;
	}

	/**
	 * Retrieves the list of errors.
	 *
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Adds an error to the error list.
	 *
	 * @param \Throwable|string $err The error to add.
	 *
	 * @return static
	 *
	 * @throws \Throwable
	 *         If the provided error is an exception,
	 *         it is thrown immediately.
	 */
	private function addErrors(\Throwable|string $err): static
	{

		$this->errors[] = $err;

		if (!is_string($err))
			throw $err;


		return $this;
	}

	/**
	 * Retrieve the contents of a local file or remote URL.
	 *
	 * Automatically detects whether the provided path is a URL
	 * when `$is_url` is not explicitly specified.
	 *
	 * @param string $file_path
	 *        The file path or URL to retrieve
	 *        contents from.
	 * @param bool|null $is_url
	 *        Whether the source is a URL.
	 *        Automatically detected when null.
	 *
	 * @return string
	 *         The retrieved contents, or an empty
	 *         string if retrieval returns false.
	 *
	 * @throws UrlLoadStubException
	 *         If the remote resource cannot be retrieved.
	 * @throws FileLoadStubException
	 *         If the local file cannot be loaded.
	 */
	private function getFileContent(string $file_path, ?bool $is_url = null): string
	{
		$is_url ??= Helper::isUrl($file_path);

		set_error_handler(
			function ($severity, $message, $file, $line) use ($is_url, $file_path) {
				$previous = new BaseErrorException(
					BaseException::class,
					$message,
					5504,
					$severity,
					line: $line
				);

				if ($is_url) {
					$this->addErrors(new UrlLoadStubException(
						$file_path,
						code: 5505,
						previous: $previous
					));
				} else {
					$this->addErrors(new FileLoadStubException(
						$file_path,
						code: 5506,
						previous: $previous
					));
				}
			},
			E_WARNING
		);

		$content = file_get_contents($file_path);

		restore_error_handler();

		return $content ?: '';
	}

	/**
	 * Write content to a local stub file.
	 *
	 * Creates the file if it does not exist or overwrites its existing contents.
	 *
	 * @param string $file_path
	 *        The destination file path.
	 * @param string|Stringable $content
	 *        The content to write.
	 *
	 * @return int|false
	 *         The number of bytes written,
	 *         or false on failure.
	 *
	 * @throws \Crafteus\Exceptions\FileWriteStubException
	 *         If the file cannot be written.
	 */
	private function putContentFile(string $file_path, string|Stringable $content): int|false
	{
		set_error_handler(
			function ($severity, $message, $file, $line) use ($file_path) {
				$previous = new BaseErrorException(
					$message,
					5507,
					$severity,
					$file,
					$line
				);
				$this->addErrors(new FileWriteStubException(
					$file_path,
					code: 5508,
					previous: $previous
				));
			},
			E_WARNING
		);

		$result = file_put_contents($file_path, $content);

		restore_error_handler();

		return $result;
	}
}
