<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Support\Templating;
use SplFileInfo;
use Crafteus\Support\Helper;

class Stub extends SplFileInfo
{

	protected string $origine_stub;

	protected string $origine_type;

	public ?Template $template = null;

	protected ?string $current_content = null;

	public bool $state_generate = true;

	public ?string $old_content = null;

	protected bool $is_created = false;

	protected bool $already_exists = false;

	protected string $file_path;

	public string $key_id;

	protected string|\Closure|null $templating;

	public array|object|null $last_templating = null;

	/**
	 * [Description for $keywords]
	 *
	 * @var array<string>
	 */
	protected array $keywords = [];

	public function __construct(string $stub, string $file_path, ?Template $template, bool $generate = true, string|int|null $key_id = null) {

		parent::__construct($file_path);

		$this->state_generate = $generate;

		$this->template = $template;

		$this->setFilePath($file_path);

		$this->key_id = $key_id;

		$this->already_exists = $this->isFile();

		if($this->already_exists) $this->old_content = file_get_contents($file_path);
		
		$this->setOrigineStub($stub);

		$this->setCurrentContent($this->getStubContent());
	}

	public function getTemplate() : ?Template {
		return $this->template;
	}

	public function getTemplating() : string|\Closure|null {
		return $this->templating;
	}

	public function setTemplating(string|null|\Closure $templating) : Stub {
		$this->templating = $templating;
		return $this;
	}

	public function getOrigineStub() : string {
		return $this->origine_stub;
	}

	public function getOrigineType() : string {
		return $this->origine_type;
	}

	public function getStubContent() : string {
		return in_array($this->getOrigineType(), ['file', 'url']) ? file_get_contents($this->getOrigineStub()) : $this->getOrigineStub();

	}

	protected function setOrigineStub(string $stub) : void {

		if(is_file($stub)){
			if(!is_readable($stub)) throw new \Exception("Error Processing Request", 1);
			$this->origine_type = 'file';
		}
		elseif(filter_var($stub, FILTER_VALIDATE_URL) !== false){
			$this->origine_type = 'url';
		}
		else $this->origine_type = 'content';

		$this->origine_stub = $stub;

	}

	public function setCurrentContent(?string $content = null) : Stub {
		$this->current_content = $content;
		return $this;
	}

	public function getCurrentContent() : ?string {
		return $this->current_content;
	}

	public function getFilePath() : string {
		return $this->file_path;
	}

	private function setFilePath(string $file_path) : void {
		$this->file_path = $file_path;
	}

	public function getData() : ?array {
		return $this->template ? $this->getTemplate()->getData() : null;
	}

	private function setLastTemplating($value) : void {
		$this->last_templating = $value;
	}

	public function getOldTemplating() : array|object|null {
		return $this->last_templating;
	}

	public function generateContentWithTemplating() : bool {
		$result = false;
		$templating = $this->getTemplating();
		if(class_exists($templating) || is_callable($templating)){
			if(is_string($templating) && class_exists($templating)){
				$_templating = is_object($this->getOldTemplating())
					? $this->getOldTemplating()
					: new $templating(...[$this])
				;
				if(method_exists($_templating, 'run'))
					$_templating->run();
				// if(!is_object($this->getOldTemplating()))
				$this->setLastTemplating($_templating);
				$result = true;
			}
			else if(!is_string($this->templating)) {
				$res = ($this->templating)(...[$this]);
				if(is_array($res))
					$this->setLastTemplating($res);
				$result = true;
			}
		}
		else if(method_exists($this->template, $m = 'templating')){
			$res = $this->template->$m(...[$this]);
			if(is_array($res))
				$this->setLastTemplating($res);
		}
		return $result;
	}

	public function generateContentFile(?string $content = null) : bool {
		if($this->isWritable()){
			file_put_contents($this->getFilePath(), $content ?? $this->getCurrentContent());
			return true;
		}
		return false;
	}
	
	public function generateFile(bool $force = true, bool $is_created = true) : bool {
		if($this->state_generate && !$this->already_exists || ($this->already_exists && $force)){
			// Helper::dd($this->getStubFilePath(), $this->getFilePath(), $this->getCurrentContent());

			if(!is_dir($this->getPath())) mkdir(directory : $this->getPath(), recursive : true);
			if($this->getOrigineType() === 'file')
				copy($this->getOrigineStub(), $this->getFilePath());
			else
				file_put_contents($this->getFilePath(), $this->getStubContent());

			$this->is_created = $is_created;
			
			$this->generateContentFile();

			return true;
		}
		return false;
		// else throw new Exception("Error Processing Request", 1);
		
	}
	
	public function phpStub() : void {
		$c = (function($data, $stub){
			ob_start();
			include $stub->getOrigineStub();
			return ob_get_clean();
		})($this->getData());
		// Helper::dd($c);
	}

	public function deleteFile() : bool {
		if(!$this->isFile()) return false;
		try {
			unlink($this->getFilePath());
		} catch (\Error $th) {
			return false;
		}
		return true;
	}

	public function isCreated() : bool {
		return $this->is_created;
	}

	public function cancelGenerateFile() : bool {
		$this->is_created = false;
		if($this->already_exists){
			try {
				if(!$this->isFile())
					$this->generateFile(is_created:false);
				$this->generateContentFile($this->old_content);
			} catch (\Error $th) {
				return false;
			}
		}
		else return $this->deleteFile();
		return true;
	}


}
