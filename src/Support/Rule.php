<?php
namespace Crafteus\Support;

use Crafteus\Support\Traits\Errors;
use Crafteus\Support\Traits\RuleOverload;
use Crafteus\Support\Traits\RuleVerify;
use Crafteus\Support\Traits\RuleCheck;

class Rule{

	use Errors, RuleOverload, RuleVerify, RuleCheck;

	public static $all_rules = [];

	/**
	 * [Description for $required]
	 *
	 * @var bool
	 */
	private $name;

	protected $required;

	protected $type = null;

	protected $empty;

	protected $verify = null;
	
	public $message = [];
	
	protected array $errors = [];

	public function __construct(string $name, bool $required = false, $type = null, $empty = false, $verify = null, array $message = []) {

		$this->name = $name;

		$this->setRequired($required);
		$this->setType($type);
		$this->setEmpty($empty);
		$this->setVerify($verify);

		$this->message = array_merge(self::defaultMessage(), $message);

		self::$all_rules[] = ['name' => $name, 'object' => $this];

	}

	public static function defaultMessage(){
		return [
			'required' => 'The key `:name` is required',
			'type' => 'The key `:name` type is not available :available_type',
			'empty' => 'The key `:name` is empty',
			'verify' => 'The key `:name` has not available value',
		];
	}


	public function getErrors() : array {
		return $this->errors;
	}

	public function errorExists() : bool {
		return !empty($this->getErrors());
	}

	protected function addError($key, $message){
		if(isset($this->errors[$key])){
			if(is_array($this->errors[$key])) $this->errors[$key][] = $message;
			else $this->errors[$key] = [$this->errors[$key], $message];
		}
		else $this->errors[$key] = $message;
	}

	public function formatMessage($key, ?string $message = null, array $replace_values = []) : string {

		$replace_values = [':name' => $this->name, ...$replace_values];

		if(is_null($message) && isset($this->message[$key])){
			$message = $this->message[$key];
		}

		if(is_string($message)){
			foreach ($replace_values as $key_ => $value)
				$message = str_replace($key_, $value, $message);
			return $message;
		}
		else throw new \Exception("Error Processing Request", 1);
		
	}

	public static function baseTemplateRule(){
		return [
			'path' => [
				'type' => 'string|array',
				'required' => true,
				'empty' => false,
			],
			'stub_file' => [
				'type' => 'string|array',
				'required' => true,
				'empty' => false,
			],
			'generate' => [
				'type' => 'bool',
				'required' => false,
			],
			'keyword_class' => [
				'type' => 'string',
				'required' => true,
			],
			'keywords' => [
				'type' => 'array',
			]
		];
	}

}