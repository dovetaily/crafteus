<?php
namespace Crafteus\Support\Traits;

trait RuleVerify{

	protected static ?array $verification_method = null;

	abstract public function getRequired() : bool;

	abstract protected function addError($key, $message) : void;

	abstract public function getVerify() : array|null;

	abstract public function formatMessage($key, ?string $message = null, array $replace_values = []) : string;

	abstract public static function checkTypes($data, array $types): bool;

	public function checkVerify($data, bool $data_exists = true/* , $key = null */) {

		if($data_exists || ($this->getRequired() && !$data_exists)){

			if(self::$verification_method === null) self::$verification_method = [

				'file_exists' => function($data, $data_exists, $rule, $verify, $method){

					if($rule->errorExists()) return null;

					$verif = file_exists($data);

					return !$verif ? 'The data key `:name` file `' . $data . '` doesn\'t exists !' : null;

				},

				'array<file_exists>' => function($data, $data_exists, $rule, $verify, $method){

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

						// else $check = !file_exists($data) 
						// 	? 'The stub file `' . $data . '` doesn\'t exists !'
						// 	: null
						// ;
					}

					return $check;

				},
				'array' => [

					'detect' => fn ($v) => $v !== 'array<file_exists>' && preg_match('/^array<[^\\>\\<]+>$/i', $v),

					'call' => function($data, $data_exists, $rule, $verify){

						$check = null;

						// if($data_exists){

						if($data_exists && is_array($data)){

							// if(!is_array($data))

							// 	$check = 'The key `:name` type is not an array !';

							// else{

								preg_match('/^array<([^\\>\\<]+)>$/i', $verify, $m);

								$types = explode('|', end($m));

								foreach ($data as $value) {

									// if(!in_array(
									// 	(!is_string($value) && is_callable($value) && gettype($value) == 'object')
									// 		? 'function'
									// 		: gettype($value)
									// 	,
									// 	$types
									// )){
									if(!self::checkTypes($value, $types)){

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

				'regexp' => [

					'detect' => fn ($v) => preg_match('/^regexp\\:.*$/i', $v),

					'call' => function($data, $data_exists, $rule, $verify){

						$check = null;

						if($data_exists){

							preg_match('/^regexp\\:(.*)$/i', $verify, $m);

							$pattern = end($m);

							if(!empty($pattern)){
								if(!((bool) preg_match($pattern, $data))){
									$check = 'The key `:name` value has not respect regexp : ' . $pattern;
								}
							}

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

						$error = $verify($data, $data_exists, $this, self::$verification_method);

						if(!empty($error))
							$check = ['state' => false, 'errors' => is_array($error) ? $error : [$error]];

					}

					elseif(is_string($verify)) {

						$errors = [];

						$recognized = false;

						foreach (self::$verification_method as $method => $val) {

							$error = null;

							// var_dump($val);

							if($verify == $method && is_callable($val)){

								$error = $val($data, $data_exists, $this, $verify, $method/* , $key */);

								$recognized = true;

							}

							else if(
								is_array($val) &&
								isset($val['detect']) &&
								isset($val['call']) &&
								is_callable($val['detect']) &&
								is_callable($val['call']) &&
								$val['detect']($verify)
							){
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
