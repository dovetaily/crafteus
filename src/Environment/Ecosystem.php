<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Template;
use Crafteus\Environment\Support\AnonymousTemplate;
use Crafteus\Exceptions\EcosystemMissingMethodException;
use Crafteus\Exceptions\TemplateConfigException;
use Crafteus\Support\Helper;
use TypeError;

class Ecosystem
{

	private ?Foundation $foundation;

	protected array $rules = [];

	protected array $templates_instance = [];

	protected bool $init_stub_content = true;

	/**
	 * [Description for $replace_exist_file]
	 * 
	 * If value is
	 *  - true : the files
	 *  - false : the files has not replace
	 *  - null : you have an question for replace
	 *
	 * @var bool|null
	 */
	public bool|null $replace_exist_file = null;

	public function template() : array {

		throw new EcosystemMissingMethodException(static::class, 'template', 3300);

		// throw new \Exception("La classe `" . static::class . "` ne possède pas la méthode `template()`", 1);

		return [];

	}

	public function getRules() : array {
		return $this->rules;
	}
	
	public function getTemplateInstance(string|int $template_name, array $more_config = []) : Template|Bool {
		if(isset($this->templates_instance[$template_name]))
			return $this->templates_instance[$template_name];
		else{
			if($template = $this->getTemplate($template_name)){

				$ins = null;

				if(is_string($template)){

					if(class_exists($template) && is_subclass_of($template, Template::class)){

						$ins = new $template;

						// update config...

						$ins_config = get_object_vars($ins);
						foreach ($more_config as $key => $value) {
							if(array_key_exists($key, $ins_config) && $ins_config[$key] !== $value){
								try {
									$ins->$key = $value;
								} catch (\TypeError $th) {
									throw new TemplateConfigException(static::class, $key, $template, 4301, $th);

									// throw new \TypeError("Erreur Venant de la classe Ecosystem `" . static::class . "`, veuillez vérifier la validité des données de la clé `" . $key . "` passer à la classe template `" . $template . "`. (" . $th->getMessage() . ")", 1);
									
								}
								$ins_config[$key] = $value; // more_config
							}
						}
						// var_dump($more_config, get_object_vars($ins));

						$this->compliantTemplateArray(
							get_object_vars($ins), "Erreur Venant de la classe Ecosystem `" . static::class . "`, veuillez vérifier la validité des propriétés de la classe template `" . $template . "`.",
							compliant_message: [
								'required' => 'The class property `:name` is required',
								'type' => 'The class property `:name` type is not available :available_type',
								'empty' => 'The class property `:name` is empty',
								'verify' => 'The class property `:name` has not available value',
							],
							template_rule : $ins->getConfigRule()
						);

						$ins->setEcosystem($this);
						$ins->setTemplateName($template_name);
						// var_dump(get_object_vars($ins), $ins->getConfigRule());

					}
					else
						throw new \Exception("Le template `" . $template . "` n'est pas une héritant de `" . Template::class . "`", 1);
				}
				elseif(is_array($template)){
					if(isset($template['config']) && is_array($template['config'])){
						// update config...
						$origin_config = $template['config'];
						foreach ($more_config as $key => $value) {
							if(array_key_exists($key, $origin_config) && $origin_config[$key] !== $value){
								$template['config'][$key] = $value; // more_config
							}
						}
						// var_dump($template['rules']['config']);
						$this->compliantTemplateArray($template['config'], template_rule : isset($template['rules']) && is_array($template['rules']) && isset($template['rules']['config']) ? $template['rules']['config'] : []);

						// var_dump($template['config']);exit();
						$ins = new AnonymousTemplate(...$template['config']);
						$ins->setConfigRule($template['rules']['config']);
						$ins->setEcosystem($this);
						$ins->setTemplateName($template_name);
						if(isset($template['getFileName']) && is_callable($template['getFileName']))
							$ins->setGetFileName($template['getFileName']);
					}
					else
						throw new \Exception("Error Venant de la classe Ecosystem `" . static::class . "`, veuillez vérifier que la clé `config` existe dans votre template `".$template_name."`", 1);
					
					// var_dump(new AnonymousTemplate(...$template['config']));
					// var_dump($this->templates_config, $template, $key);
					// $this->compliantTemplateArray($template, error_message : $error_message);
				}
				else throw new \Exception("Error Processing Request", 1);

				if($ins){
					if($this->addTemplateInstance($template_name, $ins)){
						return $this->templates_instance[$template_name];
					}
					throw new \Exception("L'instance du template `".$template_name." existe déjà pour l'écosystème de cette fondation`", 1);
				}
			}
		}
		return false;
	}
	
	protected function cleanTemplate(Template $template) : void {
		$this->compliantTemplateArray(
			get_object_vars($template),
			AnonymousTemplate::class == get_class($template) ? null : "Error Venant de la classe Ecosystem `" . self::class . "`, veuillez vérifier la validité des propriétés de la classe template `" . $template . "`.",
			compliant_message: AnonymousTemplate::class == get_class($template) ? [] : [
				'required' => 'The class property `:name` is required',
				'type' => 'The class property `:name` type is not available :available_type',
				'empty' => 'The class property `:name` is empty',
				'verify' => 'The class property `:name` has not available value',
			],
			template_rule : $template->getConfigRule()
		);
	}
	
	protected function addTemplateInstance(string $template_name, Template $template) : bool {
		if(!isset($this->templates_instance[$template_name])){
			$this->templates_instance[$template_name] = $template;
			return true;
		}
		return false;
	}

	public static function getDefaultTemplateRule(array $rule = []) : array{
		return Template::defaultRuleConfig($rule);
	}
	
	private function compliantTemplateArray(array $template, string|null $error_message = null, array $template_rule = [], array $compliant_message = []) {
		// var_dump($template, Ecosystem::getDefaultTemplateRule($template_rule));exit;
		$compliant = Helper::compliantArray(
			rules : Ecosystem::getDefaultTemplateRule($template_rule),
			data : $template
		);
		if($compliant->check(message : $compliant_message)->errorExists()){
			$m = $error_message ?? "Error Venant de la classe Ecosystem `" . static::class . "`, veuillez vérifier que les clés envoyé dans la méthode `template()` sont valides.";
			Helper::dd($m . "\n\n". print_r($compliant->getErrors(), true));
			throw new \Exception($m, 1);
		}

	}
	public function getTemplate(string $template_name) : string|array|bool {
		$templates = $this->template();
		return isset($templates[$template_name]) ? $templates[$template_name] : false;
	}

	public function initStubContent() : bool {
		return $this->init_stub_content;
	}

	public function setFoundation(Foundation $foundation) : void {
		$this->foundation = $foundation;
	}

	public function getFoundation() : ?Foundation {
		return $this->foundation;
	}

	protected function generateTemplate(Template|string|int $template) : bool {
		if((is_string($template) || is_int($template)) && is_subclass_of($st = $this->getTemplateInstance($template), Template::class))
			$template = $st;

		// if(is_null($template) && !is_null($template_name) && is_subclass_of($st = $this->getTemplateInstance($template_name), Template::class)){
		// 	$template = $st;
		// }

		if(is_subclass_of($template, Template::class)){
			// beforeGenerate
			$stubs = $template->generateStubsFile();
		}
		return false;
	}

	public function generateTemplates() {
		foreach ($this->templates_instance as $template_name => $template_instance) {
			$generated = $this->generateTemplate(template : $template_instance);
		}
	}
}