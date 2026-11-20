<?php

namespace Crafteus\Environment\Traits;

use Crafteus\Contracts\GenerationLifecycle;
use Crafteus\Environment\Ecosystem;
use Crafteus\Exceptions\StubAlreadyExistsException;
use Crafteus\Environment\Stub;
use Crafteus\Environment\Template;
use Crafteus\Exceptions\InvalidStubDefinitionException;
use Crafteus\Support\Helper;

/**
 * @phpstan-import-type TTemplateStubsGenerationResult from \Crafteus\Environment\Template
 *
 * @template T_Ecosystem of \Crafteus\Environment\Ecosystem
 * @template T_Templating of \Crafteus\Environment\Support\Templating|\Crafteus\Contracts\TemplatingInterface
 */
trait TemplateStub
{
	abstract protected function getExtension(string|int $key, bool $last = false): string;

	abstract protected function getPath(): array;

	abstract protected function getStubFile(string|int $key, bool $last = false): string;

	abstract protected function getGenerate(string|int $key, bool $last = false): bool;

	abstract private function getBaseName(string|int $key, bool $last = false): string;

	abstract protected function getTemplating(string|int $key, bool $last = false): string|\Closure|null;

	abstract public function getTemplateName(): string;

	abstract public function getEcosystem(): ?Ecosystem;

	/**
	 * All stub objects for the template.
	 *
	 * @var array<\Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating>>|null
	 */
	private array|null $stubs = null;

	/**
	 * Initializes the stubs files for the template.
	 *
	 * @param bool $generate_stub_content
	 *        Whether to generate content for the stubs.
	 * @param bool $force
	 *        If `true` it forces the initialization of stubs.
	 *
	 * @return static
	 *
	 * @throws \Crafteus\Exceptions\StubAlreadyExistsException
	 *         If Stub already exists.
	 * @throws \Crafteus\Exceptions\InvalidStubDefinitionException
	 *         If the stub definition is invalid.
	 * @throws \Crafteus\Exceptions\UnableToResolveTemplateBasenameException
	 *         If it fails to resolve the template base name.
	 */
	public function initStubs(bool $generate_stub_content = true, bool $force = false): static
	{
		if (is_null($this->stubs) || $force) {

			$this->stubs = [];

			foreach ($this->getPath() as $key => $path) {

				$extension = $this->getExtension($key);

				$stub_file = $this->getStubFile($key);

				$generate = $this->getGenerate($key);

				try {
					$basename = $this->getBaseName($key);
				} catch (\TypeError $th) {
					throw new InvalidStubDefinitionException($key, $this->getTemplateName(), "The stub for path key {$key} in template {$this->getTemplateName()} must define a valid basename (string).", 5401, $th);
				}

				// $file_name = $name . (empty($extension) ? '' : ('.' . $extension));

				// $file_path = $path . '/' . $file_name;

				$templating = $this->getTemplating($key);

				if (
					empty($basename) &&
					empty($extension)
				) {
					throw new InvalidStubDefinitionException(
						$key,
						$this->getTemplateName(),
						null,
						5402
					);
				}

				$stub = $this->addStub(
					stub_file: $stub_file,
					directory: $path,
					basename: $basename,
					extension: $extension,
					generate: $generate,
					key: $key
				);


				if (is_null($stub)) {
					throw new StubAlreadyExistsException(
						$key,
						$this->getTemplateName(),
						code: 5400
					);
				} else {
					$stub->setTemplating($templating);

					if ($generate_stub_content) {
						$this->applyStubContent($stub);
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Retrieves a specific stub object by its key.
	 *
	 * @param string|int $key The key of the stub.
	 *
	 * @return \Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating>|null
	 *         The stub object or null if not found.
	 */
	public function getStub(string|int $key): ?Stub
	{
		return $this->stubs[$key] ?? null;
	}

	/**
	 * Retrieves all stub objects.
	 *
	 * @return array<\Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating>>|null
	 *         List of stub objects.
	 */
	public function getStubs(): array|null
	{
		return $this->stubs;
	}

	/**
	 * Adds a stub file to the template.
	 *
	 * @param string $stub_file
	 *        The stub file (url or string structure) path.
	 * @param string $directory
	 * @param string $basename
	 * @param string $extension
	 * @param bool $generate
	 *        Whether the stub should be generated.
	 * @param string|int|null $key
	 *        Optional key for the stub.
	 *
	 * @return \Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating>|null
	 *         The created Stub object or null if it already exists.
	 */
	public function addStub(
		string $stub_file,
		string $directory,
		string $basename,
		string $extension = '',
		bool $generate = true,
		string|int|null $key = null
	): ?Stub {

		if (is_null($key) || !isset($this->stubs[$key])) {
			$stub = new Stub(
				stub: Helper::looksLikePath($stub_file)
					? Helper::normalizePath($stub_file)
					: $stub_file,
				directory: Helper::normalizePath($directory),
				basename: $basename,
				extension: $extension,
				template: $this,
				generate: $generate,
				key_id: is_null($key) ? count($this->stubs) : $key
			);

			if (!is_null($key)) $this->stubs[$key] = $stub;
			else $this->stubs[] = $stub;

			return $stub;
		}

		return null;
	}

	/**
	 * Generates the content for a stub.
	 *
	 * @deprecated use `applyStubContent` method
	 *
	 * @param string|int|\Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating> $stub
	 *        The stub object or its key.
	 *
	 * @return bool
	 *         True if content was successfully
	 *         generated, false otherwise.
	 */
	protected function generateStubContent(string|int|Stub $stub): bool
	{
		return $this->applyStubContent($stub);
	}

	/**
	 * Apply the content for a stub.
	 *
	 * @param string|int|\Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating> $stub
	 *        The stub object or its key.
	 *
	 * @return bool
	 *         True if content was successfully
	 *         generated, false otherwise.
	 */
	protected function applyStubContent(string|int|Stub $stub): bool
	{
		$stub = is_string($stub) || is_numeric($stub)
			? $this->getStub($stub)
			: $stub;

		if ($stub) {
			if (
				$stub->getOriginType() == 'file' &&
				preg_match(
					'/.php.stub$/i',
					$stub->getOriginStub()
				)
			) {
				$stub->phpStub();
			}

			$stub->applyContentWithTemplating();

			return true;
		}

		return false;
	}

	/**
	 * Generates the file for a specific stub.
	 *
	 * @param string|int|\Crafteus\Environment\Stub<T_Ecosystem,static,T_Templating> $stub
	 *        The stub object or its key.
	 * @param bool $generate_stub_content
	 *        Whether to generate the content of the stub.
	 *
	 * @return bool
	 *         True if the file was generated
	 *         successfully, false otherwise.
	 */
	protected function generateStubFile(string|int|Stub $stub, bool $generate_stub_content = true): bool
	{
		$stub = is_string($stub) || is_numeric($stub)
			? $this->getStub($stub)
			: $stub;

		if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->beforeGenerate($stub, __METHOD__);
		}

		if (
			!is_null($stub) &&
			$stub->generateFile((bool) $this?->getEcosystem()?->replace_exist_file)
		) {
			if ($generate_stub_content) $this->applyStubContent($stub);

			if (is_subclass_of($this, GenerationLifecycle::class)) {
				$this->afterGenerateSuccessfully($stub, __METHOD__);
			}

			if (is_subclass_of($this, GenerationLifecycle::class)) {
				$this->afterGenerate($stub, __METHOD__);
			}

			return true;
		}

		if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->afterGenerateFailed($stub, __METHOD__);
		}

		if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->afterGenerate($stub, __METHOD__);
		}

		return false;
	}

	/**
	 * Generates all stub files for the template.
	 *
	 * @param bool|null $generate_stub_content
	 *        Whether to generate content for all
	 *        stubs (default verification if `null`).
	 * @param bool $cancel_all_on_error
	 *        Whether to cancel all generation on error.
	 * @param bool $reinit_stub
	 *        Whether to reinitialize stubs.
	 *
	 * @return TTemplateStubsGenerationResult
	 *         Result of the generation process,
	 *         including generated and not generated stubs.
	 */
	public function generateStubsFile(
		bool|null $generate_stub_content = null,
		bool $cancel_all_on_error = true,
		bool $reinit_stub = false
	): array {
		if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->beforeGenerate($this, __METHOD__);
		}

		$result = [
			'generated' => [],
			'not_generated' => [],
		];

		$verif = $this->stubs === null || $reinit_stub;

		if ($verif) {
			$this->initStubs(false, $reinit_stub);
		}

		foreach ($this->stubs as $key => $stub) {
			try {
				$file_generated = $this->generateStubFile(
					stub: $stub,
					generate_stub_content: is_null($generate_stub_content)
						? $verif
						: $generate_stub_content
				);
			} catch (\Throwable $th) {

				$file_generated = false;

				echo Helper::redText(' > ')
					. Helper::redText($th->getMessage())
					. "\n  "
					. Helper::redText(
						' [File]: '
							. $th->getFile()
							. ':'
							. $th->getLine()
					)
					. "\n    "
					. str_replace("\n", "\n    ", $th->getTraceAsString())
					. (
						!is_null($prev = $th->getPrevious())
						? (
							Helper::redText("\n    > ")
							. Helper::redText($prev->getMessage())
							. "\n     "
							. Helper::redText(
								' [File]: '
									. $prev->getFile()
									. ':'
									. $prev->getLine()
							)
							. "\n        "
							. str_replace("\n", "\n        ", $prev->getTraceAsString())
						)
						: ''
					)
					. "\n";

				// break;

			}

			$result[$file_generated ? 'generated' : 'not_generated'][$key] = $stub;
		}

		if (
			$cancel_all_on_error &&
			count($result['not_generated']) > 0
		) {
			array_map(
				fn(Stub $stub) => $stub->cancelGenerateFile(),
				[
					...($this?->getEcosystem()?->cancelAllOnError()
						? $result['generated']
						: []
					),
					...$result['not_generated']
				]
			);
		}

		if (is_subclass_of($this, GenerationLifecycle::class) && count($result['not_generated']) > 0) {
			$this->afterGenerateFailed($this, __METHOD__);
		} else if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->afterGenerateSuccessfully($this, __METHOD__);
		}

		if (is_subclass_of($this, GenerationLifecycle::class)) {
			$this->afterGenerate($this, __METHOD__);
		}

		return $result;
	}

	/**
	 * Cancels all generated stub files.
	 *
	 * @return void
	 */
	public function cancelStubsFilesGenerated(): void
	{
		$this->initStubs(false);
		if (!empty($this->stubs)) {
			foreach (array_reverse($this->stubs) as $stub) {
				if ($stub->isGenerated())
					$stub->cancelGenerateFile();
			}
		}
	}
}
