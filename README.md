# Keboola PHP Component

[![Build Status](https://travis-ci.com/keboola/php-component.svg?branch=master)](https://travis-ci.com/keboola/php-component)
[![Code Climate](https://codeclimate.com/github/keboola/php-component/badges/gpa.svg)](https://codeclimate.com/github/keboola/php-component)

General library for php component running in KBC. The library provides function related to [Docker Runner](https://github.com/keboola/docker-bundle).

## Installation

```
composer require keboola/php-component
```

## Usage

Create a subclass of `BaseComponent`. 

```php
<?php
class Component extends \Keboola\Component\BaseComponent
{
    protected function run(): void
    {
        // get parameters
        $parameters = $this->getConfig()->getParameters();

        // get value of customKey.customSubkey parameter and fail if missing
        $customParameter = $this->getConfig()->getValue(['parameters', 'customKey', 'customSubkey']);

        // get value with default value if not present
        $customParameterOrNull = $this->getConfig()->getValue(['parameters', 'customKey'], 'someDefaultValue');

        // get manifest for input file
        $fileManifest = $this->getManifestManager()->getFileManifest('input-file.csv');

        // get manifest for input table
        $tableManifest = $this->getManifestManager()->getTableManifest('in.tableName');

        // write manifest for output file
        $this->getManifestManager()->writeFileManifest(
            'out-file.csv',
            (new OutFileManifestOptions())
                ->setTags(['tag1', 'tag2'])
        );

        // write manifest for output table
        $this->getManifestManager()->writeTableManifest(
            'data.csv',
            (new OutTableManifestOptions())
                ->setPrimaryKeyColumns(['id'])
                ->setDestination('out.report')
        );
    }

    protected function customSyncAction(): array
    {
        return ['result' => 'success', 'data' => ['joe', 'marry']];
    }

    protected function getSyncActions(): array
    {
        return ['custom' => 'customSyncAction'];
    }
}
```

Use this `src/run.php` template. 

```php
<?php

declare(strict_types=1);

use Keboola\Component\Logger;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $app = new MyComponent\Component($logger);
    $app->execute();
    exit(0);
} catch (\Keboola\Component\UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (\Throwable $e) {
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
```

## Sync actions support

[Sync actions](https://developers.keboola.com/extend/common-interface/actions/) can be called directly via API. API will block and wait for the result. The correct action is selected based on the `action` key of config. `BaseComponent` class handles the selection automatically. Also it handles serialization and output of the action result - sync actions must output valid JSON.

To implement a sync action  
* add a method in your `Component` class. The naming is entirely up to you. 
* override the `Component::getSyncActions()` method to return array containing your sync actions names as keys and corresponding method names from the `Component` class as values. 
* return value of the method will be serialized to json

## Customizing config

### Custom getters in config

You might want to add getter methods for custom parameters in the config. That way you don't need to remember exact keys (`parameters.errorCount.maximumAllowed`), but instead use a method to retrieve the value (`$config->getMaximumAllowedErrorCount()`).

Simply create your own `Config` class, that extends `BaseConfig` and override `\Keboola\Component\BaseComponent::getConfigClass()` method to return your new class name. 

```php
class MyConfig extends \Keboola\Component\Config\BaseConfig 
{
    public function getMaximumAllowedErrorCount()
    {
        $defaultValue = 0;
        return $this->getValue(['parameters', 'errorCount', 'maximumAllowed'], $defaultValue);
    }
}
```
and
```php
class MyComponent extends \Keboola\Component\BaseComponent
{
    protected function getConfigClass(): string
    {
        return MyConfig::class;
    }
}
```

### Custom parameters validation

To validate input parameters extend `\Keboola\Component\Config\BaseConfigDefinition` class. By overriding the `getParametersDefinition()` method, you can validate the parameters part of the config. Make sure that you return the actual node you add, not `TreeBuilder`. You can use `parent::getParametersDefinition()` to get the default node or you can build it yourself. 

If you need to validate other parts of config as well, you can override `getRootDefinition()` method. Again, make sure that you return the actual node, not the `TreeBuilder`. 

```php
class MyConfigDefinition extends \Keboola\Component\Config\BaseConfigDefinition
{
    protected function getParametersDefinition()
    {
        $parametersNode = parent::getParametersDefinition();
        $parametersNode
            ->isRequired()
            ->children()
                ->arrayNode('errorCount')
                    ->isRequired()
                    ->children()
                        ->integerNode('maximumAllowed')
                            ->isRequired();
        return $parametersNode;
    }
}
```

 *Note:* Your build may fail if you use PhpStan because of complicated type behavior of the `\Symfony\Component\Config\Definition\Builder\ExprBuilder::end()` method, so you may need to [ignore some errors](https://github.com/phpstan/phpstan#ignore-error-messages-with-regular-expressions). 

Again you need to supply the new class name in your component

```php
class MyComponent extends \Keboola\Component\BaseComponent
{
    protected function getConfigDefinitionClass(): string
    {
        return MyConfigDefinition::class;
    }
}
```

If any constraint of config definition is not met a `UserException` is thrown. That means you don't need to handle the messages yourself. 

## Migration from version 6 to version 7

The default entrypoint of component (in `index.php`) changed from `BaseComponent::run()` to `BaseComponent::execute()`. Please also note, that the `run` method can no longer be public and can only be called from inside the component now.  

## More reading

For more information, please refer to the [generated docs](https://keboola.github.io/php-component/master/classes.html). See [development guide](https://developers.keboola.com/extend/component/tutorial/) for help with KBC integration.

## License

MIT licensed, see [LICENSE](./LICENSE) file.
