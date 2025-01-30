<?php
namespace Crafteus\Environment\Traits;

use Crafteus\Exceptions\StubAlreadyExistsException;
use Crafteus\Environment\Stub;
use Crafteus\Environment\Template;
use Crafteus\Support\Helper;

trait TemplateStub {

	/**
	 * All stub objects for the template.
	 *
	 * @var ?array<Stub>
	 */
	private ?array $stubs = null;

	/**
	 * Initializes the stub files for the template.
	 *
	 * @param bool $generate_stub_content Whether to generate content for the stubs.
	 * 
	 * @return self
	 * @throws StubAlreadyExistsException If Stub already exists.
	 * 
	 */
	public function initStub(bool $generate_stub_content = true) : Template {
		
		if(is_null($this->stubs)){
			$this->stubs = [];
	
			foreach ($this->getPath() as $key => $path) {
				$extension = $this->getExtension($key);
				$stub_file = $this->getStubFile($key);
				$generate = $this->getGenerate($key);
	
				$name = $this->getBaseName($key);
	
				$file_name = $name . (empty($extension) ? '' : ('.' . $extension));
				$file_path = $path . '/' . $file_name;
				
				$templating = $this->getTemplating($key);
				
				$stub = $this->addStub($stub_file, $file_path, $generate, $key);

				
				if (is_null($stub)) {
					throw new StubAlreadyExistsException(
						$key,
						$this->getTemplateName(),
						code : 5400
					);
				}
				else{
					$stub->setTemplating($templating);
					if($generate_stub_content)
						$this->generateStubContent($stub);
				}

			}
		}

		return $this;

	}
	
	/**
	 * Adds a stub file to the template.
	 *
	 * @param string $stub_file The stub file (url or string structure) path.
	 * @param string $file_path The target file path.
	 * @param bool $generate Whether the stub should be generated.
	 * @param string|int|null $key Optional key for the stub.
	 * 
	 * @return Stub|null The created Stub object or null if it already exists.
	 * 
	 */
	public function addStub(string $stub_file, string $file_path, bool $generate = true, string|int|null $key = null) : ?Stub {
		
		if(is_null($key) || !isset($this->stubs[$key])){

			$stub = new Stub(
				stub : $stub_file,
				file_path : $file_path,
				template : $this,
				generate : $generate,
				key_id : is_null($key) ? count($this->stubs) : $key
			);

			if(!is_null($key)) $this->stubs[$key] = $stub;
			else $this->stubs[] = $stub;

			return $stub;

		}

		return null;

	}
	
	/**
	 * Retrieves all stub objects.
	 *
	 * @return array List of stub objects.
	 * 
	 */
	public function getStubs() : array {
		return $this->stubs;
	}

	/**
	 * Retrieves a specific stub object by its key.
	 *
	 * @param string|int $key The key of the stub.
	 * 
	 * @return Stub|null The stub object or null if not found.
	 * 
	 */
	public function getStub(string|int $key) : ?Stub {
		return !is_null($this->stubs) && isset($this->stubs[$key]) ? $this->stubs[$key] : null;
	}

	/**
	 * Generates the content for a stub.
	 *
	 * @param string|int|Stub $stub The stub object or its key.
	 * 
	 * @return bool True if content was successfully generated, false otherwise.
	 * 
	 */
	protected function generateStubContent(string|int|Stub $stub) : bool {
		$stub = is_string($stub) || is_numeric($stub)
			? $this->getStub($stub)
			: $stub
		;
		if($stub){
			if($stub->getOriginType() == 'file' && preg_match('/.php.stub$/i', $file = $stub->getOriginStub()))
				$stub->phpStub();
	
			$stub->generateContentWithTemplating();
			return true;
		}
		return false;
	}

	/**
	 * Generates the file for a specific stub.
	 *
	 * @param string|int|Stub $stub The stub object or its key.
	 * @param bool $generate_stub_content Whether to generate the content of the stub.
	 * 
	 * @return bool True if the file was generated successfully, false otherwise.
	 * 
	 */
	public function generateStubFile(string|int|Stub $stub, bool $generate_stub_content = true) : bool {
		$stub = is_string($stub) || is_numeric($stub)
			? $this->getStub($stub)
			: $stub
		;
		if(!is_null($stub) && $stub->generateFile($this->getEcosystem()->replace_exist_file)){
			if($generate_stub_content) $this->generateStubContent($stub);
			return true;
		}
		return false;
	}

	/**
	 * Generates all stub files for the template.
	 *
	 * @param bool $generate_stub_content Whether to generate content for all stubs.
	 * @param bool $cancel_all_on_error Whether to cancel all generation on error.
	 * @param bool $reinit_stub Whether to reinitialize stubs.
	 * 
	 * @return array Result of the generation process, including generated and not generated stubs.
	 * 
	 */
	public function generateStubsFile(bool $generate_stub_content = true, bool $cancel_all_on_error = true, bool $reinit_stub = false) : array {
		$result = [
			'generated' => [],
			'not_generated' => [],
		];

		if(is_null($this->stubs) || $reinit_stub) $this->initStub(false);

		foreach ($this->stubs as $key => $stub) {
			try {
				$file_generated = $this->generateStubFile(stub : $stub, generate_stub_content : $generate_stub_content);
			} catch (\Throwable $th) {
				$file_generated = false;
				echo $th;
			}
			$result[$file_generated ? 'generated' : 'not_generated'][$key] = $stub;
		}
		if($cancel_all_on_error && count($result['not_generated']) > 0){
			array_map(fn ($stub) => $stub->cancelGenerateFile(), [
				...($this->getEcosystem()->cancelAllOnError() ? $result['generated'] : []), 
				...$result['not_generated']
			]);
		}
		return $result;
	}

	/**
	 * Cancels all generated stub files.
	 *
	 * @return void
	 * 
	 */
	public function cancelStubsFilesGenerated() : void {
		$this->initStub(false);
		if(!empty($this->stubs)){
			foreach ($this->stubs as $stub)
				if($stub->isGenerated())
					$stub->cancelGenerateFile();
		}
	}
}