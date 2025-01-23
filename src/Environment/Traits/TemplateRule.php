<?php
namespace Crafteus\Environment\Traits;

use Crafteus\Support\Rule;

trait TemplateRule {

	/**
	 * Config Rule
	 *
	 * @var array
	 */
	protected array $config_rule = [];

	/**
	 * Data Rule
	 *
	 * @var array
	 */
	protected array $data_rule = [];

	/**
	 * Get config rule
	 *
	 * @return array
	 * 
	 */
	public function getConfigRule() : array {
		return $this->config_rule;
	}
	
	/**
	 * Get data rule
	 *
	 * @return array
	 * 
	 */
	public function getDataRule() : array {
		return $this->data_rule;
	}
	
	/**
	 * Set configuration rule
	 *
	 * @param array $rule
	 * 
	 * @return void
	 * 
	 */
	public function setConfigRule(array $rule) : void {
		$this->config_rule = $rule;
	}
	
	/**
	 * Set data rule
	 *
	 * @param array $rule
	 * 
	 * @return void
	 * 
	 */
	public function setDataRule(array $rule) : void {
		$this->data_rule = $rule;
	}

	/**
	 * Get all rules
	 *
	 * @param mixed $key
	 * 
	 * @return array
	 * 
	 */
	public function getRules($key) : array {
		return [
			'config' => $this->getConfigRule(),
			'data' => $this->getDataRule(),
		];
	}

	public static function defaultRuleConfig(array $rule = []) : array {
		return [
			...$rule,
			'path' => [
				'required' => true,
				// 'empty' => false,
				'type' => 'string|array',
				'verify' => 'array<string>',
			],
			'extension' => [
				'required' => true,
				'empty' => true,
				'type' => 'string|array',
				'verify' => 'array<string>',
			],
			'stub_file' => [
				'required' => true,
				// 'empty' => false,
				'type' => 'string|array',
				'verify' => ['array<string>', function($data, $data_exists, Rule $rule){
					$check = null;
					if(!$rule->errorExists()){

						if(is_array($data)){
							$res = [];
							foreach ($data as $dt) {
								if(!file_exists($dt))
									$res[] = 'The stub file `' . $dt . '` doesn\'t exists !';
							}
							if(!empty($res)) $check = $res;
						}
						else $check = !file_exists($data) 
							? 'The stub file `' . $data . '` doesn\'t exists !'
							: null
						;
					}
					return $check;
					// return !$rule->errorExists() && 
					// ;
				}],
			],
			'generate' => [
				'required' => true,
				// 'empty' => false,
				'type' => 'boolean',
			],
			'templating' => [
				'required' => false,
				'empty' => true,
				'type' => 'string|array|function',
				'verify' => ['array<string,function>', function($data, $data_exists, Rule $rule){
					$check = null;
					if(!$rule->errorExists()){

						if(is_array($data)){
							$res = [];
							foreach ($data as $dt) {
								if(is_string($dt) && !class_exists($dt))
									$res[] = 'The templating class `' . $dt . '` doesn\'t exists or is not class !';
							}
							if(!empty($res)) $check = $res;
						}
						else $check = is_string($data) && !class_exists($data) 
							? 'The templating class `' . $data . '` doesn\'t exists or is not class !'
							: null
						;
					}
					return $check;
					// return !$rule->errorExists() && 
					// ;
				}],
			],
		];
	}

}