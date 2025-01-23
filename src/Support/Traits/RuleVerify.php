<?php
namespace Crafteus\Support\Traits;

trait RuleVerify{
	
	public function checkVerify($data, bool $data_exists = true/* , $key = null */) {
		if($data_exists || ($this->getRequired() && !$data_exists)){
			$auto = [
				'file_exists' => function($data, $data_exists, $rule, $verify, $method){
					// exit('sss');
					if($rule->errorExists()) return null;
					$verif = file_exists($data);
					return !$verif ? 'The data key `:name` file `' . $data . '` doesn\'t exists !' : null;
				},
				'array' => [
					'detect' => fn ($v) => preg_match('/^array<[^\\>\\<]+>$/i', $v),
					'call' => function($data, $data_exists, $rule, $verify){
						$check = null;
						// if($data_exists){
						if($data_exists && is_array($data)){
							// if(!is_array($data))
							// 	$check = 'The key `:name` type is not an array !';
							// else{
								preg_match('/^array<([^\\>\\<]+)>$/i', $verify, $m);
								$types = explode(',', end($m));
								foreach ($data as $value) {
									if(!in_array((!is_string($data) && is_callable($data) && gettype($data) == 'object') ? 'function' : gettype($value), $types)){
									// if(!in_array(gettype($value), $types)){
										$check = 'The key `:name` values has not only types : '. implode(',', $types);
										break;
									}
									# code...
								}
							// }
						}
						return $check;
					}
				],
				// 'sort' => function($v){},
			];

			if(!is_null($verify_values = $this->getVerify())){

				$check = ['state' => true, 'errors' => []];

				foreach ($verify_values as $verify) {

					if(!is_string($verify) && is_callable($verify)){
						$error = $verify($data, $data_exists, $this);
						if(!empty($error))
							$check = ['state' => false, 'errors' => is_array($error) ? $error : [$error]];
					}
					elseif(is_string($verify)) {
						$errors = [];
						$recognized = false;
						foreach ($auto as $method => $val) {
							$error = null;
							// var_dump($val);
							if($verify == $method && is_callable($val)){
								$error = $val($data, $data_exists, $this, $verify, $method/* , $key */);
								$recognized = true;
							}
							else if(is_array($val) && isset($val['detect']) && isset($val['call']) && is_callable($val['detect']) && is_callable($val['call']) && $val['detect']($verify)){
								$recognized = true;
								$error = $val['call']($data, $data_exists, $this, $verify, $method/* , $key */);
							}
		
							
							if(!empty($error)) 
								$errors = array_merge($errors, is_array($error) ? $error : [$error]);

							if(!empty($error)) break;
		
						}
						if(!$recognized)
							throw new \Exception("The verification of `" . $verify . "` is not recognized", 1);
						$check = ['state' => empty($errors), 'errors' => empty($errors) ? [] : $errors];
					}

					if(!array_key_exists('state', $check)) throw new \Exception("Error Processing Request", 1);
					
					if(!$check['state']) break;
				}

				return $check;
			}
		}
		return ['state' => true];
	}

	public function initCheckVerify($data, bool $data_exists = true) {
		$res = $this->checkVerify($data, $data_exists);
		if(!$res['state']){
			$this->addError('verify', $this->formatMessage('verify'));
			if(isset($res['errors']) && !empty($res['errors'])){
				$err = is_string($res['errors']) ? [$res['errors']] : (is_array($res['errors']) ? $res['errors'] : []);
				foreach ($err as $error) {
					$this->addError('verify', $this->formatMessage('verify', message : $error));
				}
			}
		}
	}
}