<?php
namespace Crafteus\Support\Traits;

trait RuleCheck{

	public static function checkList(){
		return [
			'required',
			'type',
			'empty',
			'verify',
		];
	}

	public function check($data = null, bool $data_exists = true, ?array $list = null, ...$args) {
		foreach ($list ?? self::checkList() as $method) {
			if(is_callable($method)) $method($data, $this);
			else {
				$method = 'initCheck' . ucfirst($method);
				if(method_exists($this, $method)) {
					$this->$method(...[$data, $data_exists, ...$args]);
				}
				else throw new \Exception("Error Processing Request ====> \$this->".$method, 1);
			}
		}

		return $this;
	}


	public function checkRequired(bool $data_exists = true){
		return !$this->getRequired() || ($this->getRequired() && $data_exists);
	}
	public function initCheckRequired($data, bool $data_exists = true) {
		if(!$this->checkRequired($data_exists)){
			$this->addError('required', $this->formatMessage('required'));
		}
	}


	public function checkType($data, bool $data_exists = true) {
		if(!is_null($types = $this->getType()) && ($data_exists || ($this->getRequired() && !$data_exists))){
			$check = false;
			foreach ($types as $type) {
				if(
					gettype($data) == $type || 
					($type == 'function' && !is_string($data) && is_callable($data) && gettype($data) == 'object')
				){
					$check = true;
					break;
				}
			}
			return $check;
			// return !$check ? $this->getEmpty() && !$data_exists : true;
			// return !$check ? $this->checkEmpty($data, $data_exists) : true;
		}
		return true;
	}
	public function initCheckType($data, bool $data_exists = true) {
		if(!$this->checkType($data, $data_exists)){
			$type = $this->getType();
			$this->addError('type', $this->formatMessage('type', replace_values : [':available_type' => '(' . implode(', ', $type) . ')' ]));
		}
	}


	public function checkEmpty($data, bool $data_exists) {
		if($data_exists || ($this->getRequired() && !$data_exists)){
			if(!$this->getEmpty() && !$data_exists) return false;
			else if(!$this->getEmpty()) {
				if(is_callable($empty_value = $this->getEmpty()))
					return $empty_value($data) ? true : false;
				else{
					$empty_values = ['', null, fn ($v) => is_array($v) && empty($v)];
					$check = true;
					foreach ($empty_values as $empty_value) {
						if(is_callable($empty_value)){
							$res = $empty_value($data);
							if($res){
								$check = false;
								break;
							}
						}
						else if($data === $empty_value){
							$check = false;
							break;
						}
					}
					return $check;
				}
			}
		}
		return true;
	}
	public function initCheckEmpty($data, bool $data_exists = true) {
		if(!$this->checkEmpty($data, $data_exists)){
			$this->addError('empty', $this->formatMessage('empty'));
		}
	}

}
