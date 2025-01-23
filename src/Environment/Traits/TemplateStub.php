<?php
namespace Crafteus\Environment\Traits;

use Crafteus\Environment\Stub;
use Crafteus\Environment\Template;
use Crafteus\Support\Helper;

trait TemplateStub {

	/**
	 * [Description for $stubs]
	 *
	 * @var ?array<Stub>
	 */
	private ?array $stubs = null;

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
					Helper::dd("The Stub \"$key\" key is already exists !");
					// throw new Exception("Error Processing Request", 1);
				}
				else{
					$stub->setTemplating($templating);
					if($generate_stub_content) $stub->generateContentWithTemplating();
				}
	
			}
				// $this->getData();
				// Helper::dd($this->getPath());
				// Helper::dump($this->getData());
				// Helper::dd($this->getEcosystem()->getFoundation()->getName());
				// Helper::dump($this->ecosystem->getApp());
		}

		return $this;

	}
	
	public function addStub(string $stub_file, string $file_path, bool $generate = true, string|int|null $key = null) : ?Stub {
		
		if(is_null($key) || !isset($this->stubs[$key])){

			$stub = new Stub(
				stub_file_path : $stub_file,
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
	
	public function getStubs() : array {
		return $this->stubs;
	}
	public function getStub(string|int $key) : ?Stub {
		return !is_null($this->stubs) && isset($this->stubs[$key]) ? $this->stubs[$key] : null;
	}

	protected function generateStubContent(string|int|null $key = null, ?Stub $stub = null) : void {
		$stub = is_null($stub) && !is_null($key) && !is_null($st = $this->getStub($key)) ? $st : $stub;
		if(!is_null($stub))
			$stub->generateContentWithTemplating();
	}
	// public function generateStubsContent() : void {
	// 	foreach ($this->stubs as $key => $stub) {
	// 	}
	// }
	public function generateStubFile(string|int|Stub $stub, bool $generate_stub_content = true) : bool {
		$stub = is_string($stub) || is_numeric($stub)
			? $this->getStub($stub)
			: $stub
		;
		if(!is_null($stub) && $stub->generateFile()){
			if($generate_stub_content) $this->generateStubContent(stub : $stub);
			return true;
		}
		// else throw new Exception("Error Processing Request", 1);
		return false;
	}

	public function generateStubsFile(bool $generate_stub_content = true, bool $cancel_all_on_error = true, bool $reinit_stub = false) : array {
		$result = [
			'generated' => [],
			'not_generated' => [],
		];

		if(is_null($this->stubs) || $reinit_stub) $this->initStub(false);

		foreach ($this->stubs as $key => $stub) {
			$file_generated = $this->generateStubFile(stub : $stub, generate_stub_content : $generate_stub_content);
			$result[$file_generated ? 'generated' : 'not_generated'][$key] = $stub;
		}
		if($cancel_all_on_error && count($result['not_generated']) > 0){
			// Helper::dump('---sks');
			array_map(fn ($stub) => $stub->cancelGenerateFile(), $result['not_generated']);
		}
		return $result;
	}
}