<?php

namespace Crafteus\Environment;

use Crafteus\Environment\Template;
use Crafteus\Environment\Support\AnonymousTemplate;
use Crafteus\Exceptions\DuplicateTemplateInstanceException;
use Crafteus\Exceptions\EcosystemMissingMethodException;
use Crafteus\Exceptions\InvalidTemplateException;
use Crafteus\Exceptions\InvalidTemplatePropertyException;
use Crafteus\Exceptions\InvalidTemplateTypeException;
use Crafteus\Exceptions\MissingConfigKeyException;
use Crafteus\Exceptions\TemplateConfigException;
use Crafteus\Exceptions\TemplateValidationException;
use Crafteus\Support\Helper;

class Ecosystem
{

	/**
	 * The instance of the foundation associated with the ecosystem.
	 *
	 * @var Foundation|null
	 */
	private ?Foundation $foundation;

	/**
	 * An array of validation rules for templates.
	 *
	 * @var array
	 */
	protected array $rules = [];

	/**
	 * An array of loaded template instances.
	 *
	 * @var array<Template>
	 */
	protected array $templates_instance = [];

	/**
	 * Indicates whether the stub content should be initialized at
	 * the same time as the template is created.
	 *
	 * @var bool
	 */
	protected bool $init_stub_content = false;

	/**
	 * Indicates whether existing files should be replaced.
	 *
	 * @var bool
	 */
	public bool $replace_exist_file = false;

	/**
	 * Cancel all files generated on error.
	 *
	 * @var bool
	 */
	public bool $cancel_all_on_error = false;

	/**
	 * Abstract method that must be implemented in subclasses to provide templates.
	 *
	 * @return array An array of templates.
	 * @throws EcosystemMissingMethodException If the method is not implemented.
	 * 
	 */
	public function template() : array {

		throw new EcosystemMissingMethodException(static::class, 'template', 3300);

		return [];

	}

	/**
	 * Retrieves the validation rules for templates in the ecosystem.
	 *
	 * @return array The validation rules.
	 * 
	 */
	public function getRules() : array {

		return $this->rules;

	}
	
	/**
	 * Retrieves a template instance, or returns false if not found.
	 *
	 * @param string|int $template_name The name or identifier of the template.
	 * @param array $more_config Additional configuration to apply to the instance.
	 * 
	 * @return Template|Bool The template instance, or false if not found.
	 * @throws InvalidTemplateException If the template is invalid.
	 * @throws InvalidTemplateTypeException If the template type is incorrect.
	 * @throws DuplicateTemplateInstanceException If an instance of the template already exists.
	 * 
	 */
	public function getTemplateInstance(string|int $template_name, array $more_config = []) : Template|Bool {
		if(isset($this->templates_instance[$template_name]))
			return $this->templates_instance[$template_name];
		else{
			if($template = $this->getTemplate($template_name)){

				$ins = null;

				if(is_string($template)){

					if(class_exists($template) && is_subclass_of($template, Template::class)){

						$ins = new $template;

						// update config start...

						$origin_config = get_object_vars($ins);
						foreach ($more_config as $key => $value) {
							if(array_key_exists($key, $origin_config) && $origin_config[$key] !== $value){
								try {
									$ins->$key = $value;
								} catch (\TypeError $th) {
									throw new TemplateConfigException(
										static::class,
										$key,
										$template,
										previous : $th,
										code : 4301
									);
								}
							}
						}
						// update config stop...

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

					}
					else
						throw new InvalidTemplateException($template, code : 3401);
				}
				elseif(is_array($template)){

					if(!isset($template['config']) || !is_array($template['config']))
						throw new MissingConfigKeyException($template_name, code : 4303);
					
					// update config start ...
					$origin_config = $template['config'];

					foreach ($more_config as $key => $value)
						if(array_key_exists($key, $origin_config) && $origin_config[$key] !== $value)
							$template['config'][$key] = $value;
					// update config stop ...

					$this->compliantTemplateArray(
						$template['config'],
						template_rule : $template['rules']['config'] ?? []
					);

					try {
						$ins = new AnonymousTemplate(...$template['config']);
					} catch (\Throwable $th) {
						throw new InvalidTemplatePropertyException(
							$template_name, 
							code : 4306,
							previous: $th
						);
						
					}

					$ins
						->setConfigRule($template['rules']['config'])
						->setEcosystem($this)
						->setTemplateName($template_name)
					;
					if(isset($template['getFileName']) && is_callable($template['getFileName']))
						$ins->setGetFileName($template['getFileName']);

				}
				else throw new InvalidTemplateTypeException(
					$template_name,
					static::class,
					code : 4304
				);

				if($ins){
					if($this->addTemplateInstance($template_name, $ins)){
						return $this->templates_instance[$template_name];
					}
					throw new DuplicateTemplateInstanceException(
						$template_name,
						static::class,
						$this->foundation->getName(),
						code : 3302
					);
				}
			}
		}
		return false;
	}
	
	/**
	 *  Cleans and validates the properties of a given template according to the defined configuration rules.
	 * This method ensures that the template's properties comply with the expected structure and values.
	 *
	 * @param Template $template The template instance to clean and validate.
	 * 
	 * @return void
	 * @throws TemplateValidationException If the template's properties do not comply with the expected rules.
	 * 
	 */
	protected function cleanTemplate(Template $template) : void {
		$this->compliantTemplateArray(
			get_object_vars($template),
			AnonymousTemplate::class == get_class($template) ? null : "Error from the `" . static::class . "` ecosystem class, please check the validity of the template class properties (or the configuration of the table of this template) or the configurations added in `Crafteus::make`.",
			compliant_message: AnonymousTemplate::class == get_class($template) ? [] : [
				'required' => 'The class property `:name` is required',
				'type' => 'The class property `:name` type is not available :available_type',
				'empty' => 'The class property `:name` is empty',
				'verify' => 'The class property `:name` has not available value',
			],
			template_rule : $template->getConfigRule()
		);
	}
	
	/**
	 * Adds a template instance to the ecosystem's collection of templates.
	 * If the template instance already exists, it will not be added again.
	 * This method ensures that each template has only one instance in the ecosystem.
	 * 
	 * @param string $template_name The name of the template.
	 * @param Template $template The template instance to add.
	 * 
	 * @return bool Returns true if the template was successfully added, false if it already exists.
	 * 
	 */
	protected function addTemplateInstance(string $template_name, Template $template) : bool {
		if(!isset($this->templates_instance[$template_name])){
			$this->templates_instance[$template_name] = $template;
			return true;
		}
		return false;
	}

	/**
	 * Retrieves the default validation rule for templates.
	 *
	 * @param array $rule Additional rules to apply.
	 * 
	 * @return array The default rule configuration.
	 * 
	 */
	public static function getDefaultTemplateRule(array $rule = []) : array{
		return Template::defaultRuleConfig($rule);
	}
	
	/**
	 * Checks the compliance of a template's properties according to the defined rules.
	 *
	 * @param array $template The properties of the template.
	 * @param string|null|null $error_message The error message to display in case of non-compliance.
	 * @param array $template_rule The validation rules.
	 * @param array $compliant_message Custom error messages.
	 * 
	 * @return void
	 * @throws TemplateValidationException If validation errors are found.
	 * 
	 */
	private function compliantTemplateArray(array $template, string|null $error_message = null, array $template_rule = [], array $compliant_message = []) : void {

		$compliant = Helper::compliantArray(
			rules : Ecosystem::getDefaultTemplateRule($template_rule),
			data : $template
		);

		if($compliant->check(message : $compliant_message)->errorExists()){

			throw new TemplateValidationException(
				$compliant->getErrors(),
				$error_message ?? "Error from the `" . static::class . "` ecosystem class, please check that the keys sent to the `template` method are valid or the configurations add in addition to `Crafteus::make`.",
				code : 4305
			);

		}

	}

	/**
	 * Retrieves a template by its name.
	 *
	 * @param string $template_name The name of the template.
	 * 
	 * @return string|array|bool The corresponding template, or false if not found.
	 * 
	 */
	public function getTemplate(string $template_name) : string|array|bool {
		$templates = $this->template();
		return isset($templates[$template_name]) ? $templates[$template_name] : false;
	}

	/**
	 * Obtain if you have to initialize the contents of the stub.
	 *
	 * @return bool
	 * 
	 */
	public function initStubContent() : bool {
		return $this->init_stub_content;
	}

	/**
	 * Sets the foundation associated with the ecosystem.
	 *
	 * @param Foundation $foundation The foundation instance.
	 * 
	 * @return Ecosystem The instance of the ecosystem.
	 * 
	 */
	public function setFoundation(Foundation $foundation) : Ecosystem {

		$this->foundation = $foundation;

		return $this;

	}

	/**
	 * Retrieves the foundation associated with the ecosystem.
	 *
	 * @return Foundation|null The foundation instance, or null if not set.
	 * 
	 */
	public function getFoundation() : ?Foundation {
		return $this->foundation;
	}

	/**
	 * Generates the template or returns false if generation fails.
	 *
	 * @param Template|string|int $template The template to generate.
	 * 
	 * @return bool|array True if all the stubs are generated, or a table of all not generated with generated stub files.
	 * 
	 */
	protected function generateTemplate(Template|string|int $template) : bool|array {
		if((is_string($template) || is_int($template)) && is_subclass_of($st = $this->getTemplateInstance($template), Template::class))
			$template = $st;

		if(is_subclass_of($template, Template::class)){
			// beforeGenerate
			$stubs_result = $template->generateStubsFile();

			return count($stubs_result['not_generated']) == 0 ? true : $stubs_result;

		}

		return false;
	}

	/**
	 * Generates all templates within the ecosystem.
	 *
	 * @return array An array of generation results for each template.
	 * 
	 */
	public function generateTemplates() : array {
		$generated = [];
		foreach ($this->templates_instance as $template_name => $template_instance) {
			$generated[$template_name] = $this->generateTemplate(template : $template_instance);
		}
		return $generated;
	}

	/**
	 * Cancels the generation of stub files for all templates.
	 *
	 * @return void
	 * 
	 */
	public function cancelTemplatesGenerated() : void {
		foreach ($this->templates_instance as $template_name => $template_instance) {
			$template_instance->cancelStubsFilesGenerated();
		}
	}

	/**
	 * If it returns true, while generating the files if an 
	 * error occurs, even the generated files will be canceled.
	 *
	 * @return bool
	 * 
	 */
	public function cancelAllOnError() : bool {
		return $this->cancel_all_on_error;
	}
}