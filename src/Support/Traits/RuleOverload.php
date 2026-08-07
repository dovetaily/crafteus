<?php
namespace Crafteus\Support\Traits;

trait RuleOverload{
	
	public function getRequired() : bool {
		return $this->required;
	}

	public function setRequired(bool $required) : void {
		$this->required = $required;
	}



	public function getType() : array|null {
		return $this->type;
	}

	public function setType(array|string|callable|null $type) : void {
		if(!is_null($type)){
			$this->type = [];
			$types = is_array($type) 
				? $type 
				: (is_string($type)
					? explode('|', trim($type))
					: [$type]
				)
			;
			foreach ($types as $type_) { 
				$this->type = array_merge($this->type, is_string($type_) 
					? ($type_ == 'number' || $type_ == 'num' || $type_ == 'numeric'
						? ['intenger', 'double']
						: [$type_]
					) 
					: [$type_]
				);
			}
		}
	}



	public function getEmpty() : bool {
		return $this->empty;
	}

	public function setEmpty(bool $empty) : void {
		$this->empty = $empty;
	}



	public function getVerify() : array|null {
		return $this->verify;
	}

	public function setVerify(array|string|callable|\Closure|null $verify) : void {
		if(!is_null($verify)){
			$this->verify = is_array($verify) 
				? $verify 
				: (is_string($verify)
					// ? explode('|', trim($verify))
					? array_map(
						fn($v) => str_replace(',', '|', $v),
						explode(
							'|',
							preg_replace_callback(
								'/<[^<>]+>/',
								fn($value)
									=> str_replace('|', ',', $value[0]),
								$verify
							)
						)
					)
					: [$verify]
				)
			;
		}
	}

}
