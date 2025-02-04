# Crafteus

## How to Install

You can install **Crafteus** via Composer by running the following command:

```sh
composer require dovetaily/crafteus
```

## Usage

Once installed, you can start using **Crafteus** to generate stub files based on your custom Ecosystems.

### Basic Usage

#### Create your Ecosystem

```php
use Crafteus\Environment\Ecosystem;

class MyEcosystem extends Ecosystem
{
	public function template() : array {
		return [
			'my-template1' => [
				'config' => [
					// Required keys
					'path' => '../absolute-path-where-the-file-will-be-generated',
					'extension' => 'extension-of-the-generated-file',
					'stub_file' => "../your-absolute-path-of-the-stub-file",
					'generate' => true,

					// Optional keys
					'templating' => null,

					// 'your_custom_configuration_key' => 'value', // key names must follow PHP variable naming conventions.
				],
			],
			'my-template2-class' => MyTemplate::class, // In advance usage
		];
	}
}
```

##### Configuration Keys

1. **`path`** (Required)  
	- **Type:** `string` | `array<string>`  
	- **Description:** Specifies the path(s) where the generated file(s) will be created.  
		- Example (Single Path):  
			```php
			'path' => __DIR__ . "/your-path/folder"
			```
		- Example (Multiple Paths):  
			```php
			'path' => [
				'key_path1' => __DIR__ . "/path1",
				'key_path2' => __DIR__ . "/path2"
			]
			```
	- **Note:** If multiple paths are provided in `path`, you can make them match with the other configuration keys (extension, stub_file, ...) otherwise the multiple paths will take the first existing configuration key.

2. **`extension`** (Required)  
	- **Type:** `string` | `array<string>`  
	- **Description:** Defines the file extension for the generated file(s).  
		- Example (Single Extension):  
			```php
			'extension' => 'php'
			```
		- Example (Multiple Extensions):  
			```php
			'extension' => [
				'key_path1' => 'php',
				'key_path2' => 'txt'
			]
			```

3. **`stub_file`** (Required)  
	- **Type:** `string` | `array<string>`  
	- **Description:** Indicates the absolute path(s) to the stub file(s) used as a template. Stub files define the structure/content of the generated file(s).  
		- Example:  
			```php
			'stub_file' => __DIR__ . "/../file.stub"
			```
		- Example (Multiple Extensions):  
			```php
			'stub_file' => [
				'key_path1' => __DIR__ . "/../file1.stub",
				'key_path2' => "path/file2.model"
			]
			```

4. **`generate`** (Required)  
	- **Type:** `bool` | `array<bool>`  
	- **Description:** Determines whether the file(s) should be generated. If `false`, no file will be created.  
		- Example:  
			```php
			'generate' => true
			```
		- Example (Multiple Generates):
			```php
			'generate' => [
				'key_path1' => true,
				'key_path2' => false
			]
			```

5. **`templating`** (Optional)  
	- **Type:** `string` | `\Closure` | `array<string,\Closure>`  
	- **Description:** Specifies custom logic for templating. You can use a closure or reference a class to manipulate the stub's content dynamically.  
		- Example (Closure):  
			```php
			'templating' => function (\Crafteus\Environment\Stub $stub) {
					$stub->getData(); // Data sent to the ecosystem
					$stub->getCurrentContent(); // Retrieves the current content of the stub.
					$stub->setCurrentContent(string|null $content); // Sets the current content of the stub.
			}
			```
		- Example (Class):  
			```php
			'templating' => MyTemplating::class // In advance usage
			```
		- Example (Multiple Templating):
			```php
			'templating' => [
				'key_path1' => function($stub){/*...*/},
				'key_path2' => MyTemplating::class // In advance usage
			]
			```


#### Usage of Ecosystem  

##### **1. Building Your Crafteus Application**  

You can initialize a **Crafteus** instance using the `make()` method, which accepts the following parameters:  

- **`ecosystem`** *(string)*: The class of your ecosystem.  
- **`data`** *(array)*: The data to be injected into the ecosystem.  
- **`templates_config`** *(array, optional)*: Default template configurations.  

###### **Example:**
```php
// index.php

use Crafteus\Crafteus;
use MyEcosystem;

$crafteusApp = Crafteus::make(
    ecosystem: MyEcosystem::class,
    data: [
        'foundation1', // This will be the filename, but the ecosystem can modify it if needed.
        'foundation2' => [
            'data' => [ // Data sent to the ecosystem
                'first-key' => 'value',
                // ...

                // You can also filter specific data per template
                "__template" => [
                    'my-template1' => [
                        'first-key' => 'value-change'
                    ]
                ]
            ],
            'config' => [
                'template' => [
                    'my-template1' => [
                        'path' => '../apply-this-path-only-for-this-foundation',
                        'generate' => false
                    ]
                ]
            ]
        ],
    ],
    templates_config: [
        'my-template1' => [
            'path' => 'change-default-path'
        ]
    ]
);
```
📌 **Note:** The method `make(string $ecosystem, array $data, array $templates_config = [])` returns an instance of `Crafteus\Environment\App`.

##### **2. Generating Files**  

Once the Crafteus application is created, you can generate files for your ecosystem using the `generate()` method:  

```php
$crafteusApp->generate();
```

📌 **Expected Output:** A structured array containing the results of the file generation for each foundation and its associated templates.  
```php
[
    'foundation1' => [
        'my-template1' => true, // Will Generate "/..my-template1-path/foundation1.php"
        'my-template2-class' => true, // Will Generate "/..my-template2-path/foundation1.php"
    ],
    'foundation2' => [
        'my-template1' => [
            'generated' => [/* List of generated Stub objects */],
            'not_generated' => [
                'key_path' => Object, // Instance of Crafteus\Environment\Stub
            ]
        ],
        'my-template2-class' => true, // Will Generate "/..my-template2-path/foundation2.php"
    ],
];
```

📌 **Error Handling:**  
If a stub fails to generate, you can retrieve the related errors using:  
```php
$crafteusApp->generate()['foundation2']['my-template1']['not_generated']['key_path']->getErrors();
```
🔹 If this returns an empty array, it means the template's `generate` configuration was set to `false`.

###### **Generating a Specific Foundation**  
You can also generate files for a single foundation:

```php
$crafteusApp->getFoundation('foundation1')->getEcosystemInstance()->generateTemplates();
// or more simply
$crafteusApp->foundation1->generateTemplates();

$crafteusApp->getFoundation('foundation1')->generateEcosystem();
```

📌 **Expected Output:**  
```php
[
    'my-template1' => true,
    'my-template2-class' => true,
]
```

##### **3. Cancelling Generated Files**  

If you need to cancel the generated files, use the following methods:  

```php
$crafteusApp->getFoundation('foundation1')->getEcosystemInstance()->cancelTemplatesGenerated();
// or
$crafteusApp->foundation1->cancelTemplatesGenerated();

$crafteusApp->getFoundation('foundation1')->cancelGeneratedEcosystem();
$crafteusApp->cancelGenerated();
```

📌 **Behavior:**  
- If the generated file already exists, its content will be reset.  
- If the file does not exist, it will be deleted.

### Advance Usage
Coming soon ...

## **Features** ✨  

- ✅ **Define powerful Ecosystems**: Organize file generation using structured **Ecosystems** that group multiple **Templates**.  
- ⚡ **Flexible Template Configuration**: Control **file paths, extensions, stub sources, and generation rules** dynamically.  
- 🔧 **Custom Transformations**: Modify stub content using **closures, custom classes, or predefined logic** before file generation.  
- 🔗 **Multi-Path & Multi-Format Support**: Generate files in **multiple locations** with different **extensions** from a single template.  
- 📂 **Batch File Generation**: Generate multiple files at once, with independent configurations for each.  
- 🔄 **Rollback & Error Handling**: Easily cancel generated files or retrieve errors to debug failed generations.  
- 🔥 **Dynamic Data Injection**: Pass structured **data arrays** to personalize generated content.  
- 📌 **Seamless PHP Integration**: Works with any PHP project, requiring only **Composer** to install and use.

## Requirements

- PHP 8.1 or higher
- Composer

## Contributing

Contributions are welcome! Feel free to submit issues or pull requests on [GitHub](https://github.com/dovetaily/crafteus).

## License

Crafteus is open-source software licensed under the Apache-2.0 License.
