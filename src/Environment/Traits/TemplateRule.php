<?php
namespace Crafteus\Environment\Traits;

use Crafteus\Environment\Template;
use Crafteus\Support\Rule;

trait TemplateRule {

	/**
	 * Configuration Rule for the template.
	 *
	 * @var array
	 */
	protected array $config_rule = [];

	/**
	 * Data Rule for the template.
	 *
	 * @var array
	 */
	protected array $data_rule = [];

	/**
	 * Retrieves the configuration rule for the template.
	 *
	 * @return array The configuration rule.
	 * 
	 */
	public function getConfigRule() : array {

		return $this->config_rule;

	}
	
	/**
	 * Retrieves the data rule for the template.
	 *
	 * @return array The data rule.
	 * 
	 */
	public function getDataRule() : array {
		return $this->data_rule;
	}
	
	/**
	 * Sets the configuration rule for the template.
	 *
	 * @param array $rule The configuration rule to set.
	 * 
	 * @return self
	 * 
	 */
	public function setConfigRule(array $rule) : Template {
		$this->config_rule = $rule;
		return $this;
	}
	
	/**
	 * Sets the data rule for the template.
	 *
	 * @param array $rule The data rule to set.
	 * 
	 * @return void
	 * 
	 */
	public function setDataRule(array $rule) : void {
		$this->data_rule = $rule;
	}

	/**
	 * Retrieves all rules (configuration and data).
	 *
	 * @param mixed $key Optional key to retrieve a specific rule.
	 * 
	 * @return array An array containing both 'config' and 'data' rules.
	 * 
	 */
	public function getRules($key) : array {
		return [
			'config' => $this->getConfigRule(),
			'data' => $this->getDataRule(),
		];
	}

	/**
	 * Provides default configuration for template rules.
	 *
	 * @param array $rule Additional rule configuration.
	 * 
	 * @return array The merged default and provided rules.
	 * 
	 */
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