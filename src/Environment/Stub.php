<?php

namespace Crafteus\Environment;

use Crafteus\Crafteus;
use Crafteus\Environment\Support\Templating;
use Crafteus\Exceptions\BaseErrorException;
use Crafteus\Exceptions\DirectoryCreationException;
use Crafteus\Exceptions\FileDeletionException;
use Crafteus\Exceptions\FileGenerationException;
use Crafteus\Exceptions\FileNotReadableException;
use Crafteus\Exceptions\PermissionDeniedException;
use Crafteus\Exceptions\PhpStubException;
use SplFileInfo;
use Crafteus\Support\Helper;

class Stub extends SplFileInfo
{

	/**
	 * The file, URL, or content source of the stub.
	 *
	 * @var string
	 */
	protected string $origin_stub;

	/**
	 * The type of the stub source.
	 *
	 * @var string
	 */
	protected string $origin_type;

	/**
	 * Template used for this stub.
	 *
	 * @var Template|null
	 */
	private Template|null $template = null;

	/**
	 * The current content of the stub.
	 *
	 * @var string|null
	 */
	protected string|null $current_content = null;

	/**
	 * Determines if the file should be generated.
	 * If set to false, the stub file will not be created.
	 *
	 * @var bool
	 */
	public bool $state_generate = true;

	/**
	 * Stores the previous content of the stub file.
	 *
	 * @var string|null
	 */
	public string|null $old_content = null;

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
	 * The path of the generated file.
	 *
	 * @var string
	 */
	private string $file_path;

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
	 * Unique identifier for the stub.
	 *
	 * @var string
	 */
	public readonly string $key_id;

	/**
	 * The templating mechanism used.
	 *
	 * @var string|\Closure|null
	 */
	protected string|\Closure|null $templating;

	/**
	 * Stores the last templating result.
	 *
	 * @var array|object|null
	 */
	public array|object|null $last_templating = null;

	/**
	 * Stores errors encountered during stub processing.
	 *
	 * @var array
	 */
	private array $errors = [];

	/**
	 * Keywords associated with the stub.
	 *
	 * @var array<string>
	 */
	protected array $keywords = [];

	/**
	 * Stores content that cannot be written to a file due to write restrictions.
	 * 
	 * When file writing is disabled, new content is stored in this property 
	 * until writing is allowed again.
	 * 
	 * @var string|null $deferred_content The deferred content awaiting file write.
	 */
	public $deferred_content;

	/**
	 * Stub constructor.
	 *
	 * @param string $stub The stub source (file, URL, or content).
	 * @param Template|null $template The template associated with the stub.
	 * @param string $directory The directory path.
	 * @param string $basename Base name of file.
	 * @param string|null $extension File extension.
	 * @param bool $generate Whether the stub should be generated.
	 * @param string|int|null|null $key_id Unique identifier for the stub.
	 * 
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
	 * Adds an error to the error list.
	 *
	 * @param \Throwable|string $err The error to add.
	 * 
	 * @return Stub
	 * @throws \Throwable If the provided error is an exception, it is thrown immediately.
	 * 
	 */
	private function addErrors(\Throwable|string $err) : Stub {

		$this->errors[] = $err;

		if(!is_string($err))
			throw $err;
			

		return $this;

	}

	/**
	 * Checks if any errors exist.
	 *
	 * @return bool
	 * 
	 */
	public function errorExists() : bool {
		return count($this->errors) > 0;
	}

	/**
	 * Retrieves the list of errors.
	 *
	 * @return array
	 * 
	 */
	public function getErrors() : array {
		return $this->errors;
	}

	/**
	 * Gets the associated template.
	 *
	 * @return Template|null
	 * 
	 */
	public function getTemplate() : ?Template {
		return $this->template;
	}

	/**
	 * Gets the templating mechanism.
	 *
	 * @return string|\Closure|null
	 * 
	 */
	public function getTemplating() : string|\Closure|null {
		return $this->templating;
	}

	/**
	 * Sets the templating mechanism.
	 *
	 * @param string|null|\Closure $templating
	 * 
	 * @return Stub
	 * 
	 */
	public function setTemplating(string|null|\Closure $templating) : Stub {
		$this->templating = $templating;
		return $this;
	}

	/**
	 * Gets the origin stub source.
	 *
	 * @return string
	 * 
	 */
	public function getOriginStub() : string {
		return $this->origin_stub;
	}

	/**
	 * Gets the origin stub type.
	 *
	 * @return string
	 * 
	 */
	public function getOriginType() : string {
		return $this->origin_type;
	}

	/**
	 * Retrieves the content of the stub.
	 *
	 * @return string
	 * 
	 */
	public function getStubContent() : string {
		return in_array($this->getOriginType(), ['file', 'url']) ? file_get_contents($this->getOriginStub()) : $this->getOriginStub();

	}

	/**
	 * Sets the origin stub.
	 *
	 * @param string $stub
	 * 
	 * @return void
	 * @throws PermissionDeniedException If the file exists but is not readable.
	 * 
	 */
	protected function setOriginStub(string $stub) : void {

		if(filter_var($stub, FILTER_VALIDATE_URL) !== false){
			$this->origin_type = 'url';
		}
		elseif(is_file($stub)){
			if(!is_readable($stub))
				$this->addErrors(new PermissionDeniedException(
					$stub, 
					code : 5501
				));
			$this->origin_type = 'file';
		}
		else $this->origin_type = 'content';

		$this->origin_stub = $stub;

	}

	/**
	 * Sets the current content of the stub.
	 *
	 * @param string|null $content
	 * 
	 * @return Stub
	 * 
	 */
	public function setCurrentContent(string|null $content = null) : Stub {
		if(Crafteus::$disable_file_writing) $this->deferred_content = $content;
		$this->current_content = $content;
		return $this;
	}

	/**
	 * Retrieves the current content of the stub.
	 *
	 * @return string|null
	 * 
	 */
	public function getCurrentContent() : ?string {
		return Crafteus::$disable_file_writing ? $this->deferred_content : $this->current_content;
	}

	/**
	 * Gets the file path of the stub.
	 *
	 * @return string
	 * 
	 */
	public function getFilePath() : string {
		return $this->file_path;
	}

	/**
	 * Sets the file path of the stub.
	 *
	 * @param string $file_path
	 * 
	 * @return void
	 * 
	 */
	private function setFilePath(string $file_path) : void {
		$this->file_path = $file_path;
	}

	/**
	 * Retrieves the template data.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * 
	 * @return mixed
	 * 
	 */
	public function getData(string|null $key = null, $default_value = null) {
		return $this->template ? $this->getTemplate()->getData($key, $default_value) : null;
	}

	/**
	 * Sets the last templating result.
	 *
	 * @param mixed $value
	 * 
	 * @return void
	 * 
	 */
	private function setLastTemplating($value) : void {
		if($value !== $this->last_templating)
			$this->last_templating = $value;
	}

	/**
	 * Retrieves the previous templating result.
	 *
	 * @return array|object|null
	 * 
	 */
	public function getOldTemplating() : array|object|null {
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
	 * @param string|null $templating The fully qualified class name of the templating engine to instantiate.
	 *                    Falls back to `getTemplating()` if null.
	 * @param bool $set_last Whether to store the instance as the last used templating engine.
	 * @param bool $force_new Whether to force creation of a new instance even if one already exists.
	 * @param bool $update_current_content Whether to update the new instance with the current stub content.
	 *
	 * @return object|null The templating engine instance, or null if instantiation failed.
	 */
	public function getTemplatingInstance(?string $templating = null, bool $set_last = false, bool $force_new = false, bool $update_current_content = true) : object|null {

		$templating = $templating ?? $this->getTemplating();

		if(!is_string($templating) || !class_exists($templating))
			return null;

		$instance = is_object($this->getOldTemplating()) && !$force_new
			? $this->getOldTemplating()
			: new $templating(...[$this])
		;

		if($update_current_content)
			$instance->setCurrentContent($instance->getStub()->getCurrentContent());

		if($set_last)
			$this->setLastTemplating($instance);

		return $instance;

	}

	/**
	 * Generates content using the templating mechanism.
	 *
	 * @return bool
	 * 
	 */
	public function generateContentWithTemplating() : bool {
		$result = false;
		$templating = $this->getTemplating();
		if((is_string($templating) && class_exists($templating)) || is_callable($templating)){
			if(is_string($templating) && class_exists($templating)){
				$_templating = $this->getTemplatingInstance();
				if(method_exists($_templating, 'run'))
					$_templating->run();
				// if(!is_object($this->getOldTemplating()))
				$this->setLastTemplating($_templating);
				$result = true;
			}
			else if(!is_string($templating)) {
				$res = ($templating)(...[$this]);
				if(is_array($res))
					$this->setLastTemplating($res);
				$result = true;
			}
		}
		else if(method_exists($this->getTemplate(), $m = 'templating')){
			$res = $this->getTemplate()->$m(...[$this]);
			if(is_array($res))
				$this->setLastTemplating($res);
			$result = true;
		}
		return $result;
	}

	/**
	 * Generates the stub file content.
	 *
	 * @param string|null $content
	 * 
	 * @return bool
	 * 
	 */
	public function generateContentFile(?string $content = null) : bool {
		if($this->isWritable()){
			if(Crafteus::$disable_file_writing)
				$this->deferred_content = $content ?? $this->getCurrentContent();
			else
				file_put_contents($this->getFilePath(), $content ?? $this->getCurrentContent());
			return true;
		}
		return false;
	}

	/**
	 * Creates the directory path if it does not exist.
	 *
	 * @return void
	 * @throws DirectoryCreationException If directory creation fails.
	 * 
	 */
	public function makePathDirectory() : void {
		$path = $this->getPath();

		set_error_handler(
			fn ($severity, $message, $file, $line) => $this->addErrors(new DirectoryCreationException(
				$path,
				code : 9501,
				previous: new BaseErrorException(DirectoryCreationException::class, $message, 9501, $severity, $file, $line)
			)),
			E_WARNING
		);

		if(!is_dir($path))
			mkdir($path, recursive : true);
		
		restore_error_handler();

	}
	
	/**
	 * Generates the stub file.
	 *
	 * @param bool $force
	 * @param bool $is_generated
	 * 
	 * @return bool
	 * @throws FileGenerationException If file generation fails.
	 * 
	 */
	public function generateFile(bool $force = true, bool $is_generated = true) : bool {
		if($this->state_generate && !$this->already_exists || ($this->already_exists && $force)){
			// Helper::dd($this->getStubFilePath(), $this->getFilePath(), $this->getCurrentContent());

			$this->makePathDirectory();
			set_error_handler(
				fn ($severity, $message, $file, $line) => $this->addErrors(new FileGenerationException(
					$this->getFilePath(),
					$this->getPath(),
					code: 5502,
					previous: new BaseErrorException($message, 0, $severity, $file, $line)
				)),
				E_WARNING
			);

			if($this->getOriginType() === 'file'){
				if(Crafteus::$disable_file_writing)
					$this->deferred_content = file_get_contents($this->getOriginStub());
				else copy($this->getOriginStub(), $this->getFilePath());
			}
			else{

				if(Crafteus::$disable_file_writing)
					$this->deferred_content = $this->getStubContent();
				else file_put_contents($this->getFilePath(), $this->getStubContent());
			}

			restore_error_handler();

			$this->is_generated = $is_generated;
			
			$this->generateContentFile();

			return true;
		}
		return false;
		
	}
	
	/**
	 * Executes the PHP stub and captures the output.
	 *
	 * @return void
	 * 
	 */
	public function phpStub() : void
	{
		try {
			$c = (function($data, $stub){
				ob_start();
				include $stub->getOriginStub();
				return ob_get_clean();
			})($this->getData(), $this);
			$this->setCurrentContent($c)->generateContentFile();
		} catch (\Throwable $th) {
			$this->addErrors(new PhpStubException(
				$this->getOriginStub(),
				code : 5503,
				previous: $th
			));
		}
	}

	/**
	 * Deletes the generated file.
	 *
	 * @return void
	 * @throws FileDeletionException If file deletion fails.
	 * 
	 */
	public function deleteFile() : void {

		if(!$this->isFile()) return;

		$path = $this->getFilePath();

		set_error_handler(
			fn ($severity, $message, $file, $line) => $this->addErrors(new FileDeletionException(
				$path,
				code : 9502,
				previous: new BaseErrorException(FileDeletionException::class, $message, 9502, $severity, $file, $line)
			)),
			E_WARNING
		);

		unlink($path);

		restore_error_handler();

	}

	/**
	 * Checks if the file has been generated.
	 *
	 * @return bool
	 * 
	 */
	public function isGenerated() : bool {
		return $this->is_generated;
	}

	/**
	 * Cancels the file generation and restores previous content or deletes the file.
	 *
	 * @return bool
	 * 
	 */
	public function cancelGenerateFile() : bool {
		$this->is_generated = false;
		if($this->already_exists){
			try {
				if(!$this->isFile())
					$this->generateFile(is_generated:false);
				$this->cancelGenerateContent();
			} catch (\Error $th) {
				return false;
			}
		}
		else {
			try {
				$this->deleteFile();
			} catch (\Throwable $th) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Cancels the content generate in file.
	 *
	 * @return bool
	 * 
	 */
	public function cancelGenerateContent() : bool {
		return $this->generateContentFile($this->getOldContent());
	}

	/**
	 * Initializes the old content of the file if it already exists.
	 *
	 * @return void
	 * @throws FileNotReadableException If the file is not readable.
	 * 
	 */
	public function initOldContent() : void {

		if($this->already_exists){

			if(!is_readable($f = $this->getFilePath()))
				$this->addErrors(new FileNotReadableException(
					$f,
					code : 9500
				));
				
			$this->setOldContent(file_get_contents($this->getFilePath()));
		
		}

	}

	/**
	 * Retrieves the old content of the file.
	 *
	 * @return string|null
	 * 
	 */
	public function getOldContent() : ?string {
		return $this->old_content;
	}

	/**
	 * Sets the old content of the file.
	 *
	 * @param string $content
	 * 
	 * @return void
	 * 
	 */
	public function setOldContent(string $content) : void {
		$this->old_content = $content;
	}

	/**
	 * Get the relative directory path.
	 *
	 * @return string The relative directory path.
	 */
	public function getRelativePath() : string {
		return Helper::relativePath($this->getPath());
	}

	/**
	 * Get the relative file path.
	 *
	 * @return string The relative file path.
	 */
	public function getRelativeFilePath() : string {
		return Helper::relativePath($this->getFilePath());
	}

	/**
	 * Get file extension.
	 *
	 * @return string
	 * 
	 */
	public function getExtension() : string {
		return $this->extension;
	}

	/**
	 * Gets the base name of the file
	 *
	 * @return string The base name without path information.
	 * 
	 */
	public function getBasename(string $suffix = "") : string {
		return $this->basename . $suffix;
	}

	/**
	 * Retrieves the configuration filtered by Stub from the Template's public properties.
	 * For certain properties, it applies a key-based filtering using the `getUniqueConfig` method.
	 *
	 * @param string|null $key
	 * @param mixed $default_value
	 * 
	 * @return mixed The filtered configuration of the Template.
	 */
	public function getConfig(string|null $key = null, $default_value = null) {
		$result = $this->template->getConfig();
		foreach ($result as $key_ => $conf) {
			if(in_array($key_, array_unique(array_merge(Template::UNIQUE_STUB_CONFIG_PROPERTIES, $this->template->getUniqueStubConfigProperties()))))
				$result[$key_] = Template::getUniqueConfig($conf, $this->key_id);
		}
		return is_null($key) ? $result : Helper::getNestedArrayValue($key, $result, $default_value)['result'];
	}

	/**
	 * Retrieves the ecosystem instance.
	 *
	 * @return Ecosystem Ecosystem instance.
	 * 
	 */
	public function getEcosystem() : Ecosystem {
		return $this->getTemplate()->getEcosystem();
	}

}
