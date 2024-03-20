<?php

declare(strict_types=1);

namespace Keboola\Component\Config;

use InvalidArgumentException;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use function array_key_exists;
use function implode;

/**
 * Offers basic abstraction over the JSON config. You can extend it and add your own getters for custom parameters.
 * It's then easier to use them then to remember under which key they are in the parameters array.
 */
class BaseConfig implements ConfigInterface
{
    protected array $config;

    public const COMPONENT_RUN_MODE_RUN = 'run';

    public const COMPONENT_RUN_MODE_DEBUG = 'debug';

    private ConfigurationInterface $configDefinition;

    /**
     * @param mixed[] $config Configuration array
     * @param null|ConfigurationInterface $configDefinition (optional) Custom class to validate the config
     */
    public function __construct(
        array $config,
        ?ConfigurationInterface $configDefinition = null
    ) {
        $this->setConfigDefinition($configDefinition);
        $this->setConfig($config);
    }

    /**
     * @param mixed[] $config
     */
    protected function setConfig(array $config): void
    {
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration($this->configDefinition, [$config]);
        $this->config = $processedConfig;
    }

    protected function setConfigDefinition(?ConfigurationInterface $configDefinition): void
    {
        if ($configDefinition === null) {
            $configDefinition = new BaseConfigDefinition();
        }
        $this->configDefinition = $configDefinition;
    }

    /**
     * Returns all the data in config as associative array
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->config;
    }

    /**
     * @param mixed $default
     */
    public function getArrayValue(array $keys, $default = null): array
    {
        /** @var array $value */
        $value = $this->getValue($keys, $default);
        return $value;
    }

    /**
     * @param mixed $default
     */
    public function getStringValue(array $keys, $default = null): string
    {
        /** @var string $value */
        $value = $this->getValue($keys, $default);
        return $value;
    }

    /**
     * @param mixed $default
     */
    public function getIntValue(array $keys, $default = null): int
    {
        /** @var int $value */
        $value = $this->getValue($keys, $default);
        return $value;
    }

    /**
     * Returns value by key. You can supply default value for when the key is missing.
     * Without default value exception is thrown for nonexistent keys.
     *
     * @param string[] $keys
     * @param mixed $default
     * @return mixed
     */
    public function getValue(array $keys, $default = null)
    {
        $config = $this->config;
        $pointer = &$config;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $pointer)) {
                if ($default === null) {
                    throw new InvalidArgumentException(sprintf(
                        'Key "%s" does not exist',
                        implode('.', $keys)
                    ));
                }
                return $default;
            }
            $pointer = &$pointer[$key];
        }
        return $pointer;
    }

    /**
     * Returns `parameters` section of the config
     */
    public function getParameters(): array
    {
        return $this->getArrayValue(['parameters'], []);
    }

    /**
     * Returns `storage` section of the config
     */
    public function getStorage(): array
    {
        return $this->getArrayValue(['storage'], []);
    }

    /**
     * Returns `image_parameters` section of the config
     */
    public function getImageParameters(): array
    {
        return $this->getArrayValue(['image_parameters'], []);
    }

    /**
     * Returns `authorization` section of the config
     */
    public function getAuthorization(): array
    {
        return $this->getArrayValue(['authorization'], []);
    }

    /**
     * Returns `action` section of the config
     */
    public function getAction(): string
    {
        return $this->getStringValue(['action'], 'run');
    }

    public function getInputFiles(): array
    {
        return $this->getArrayValue(['storage', 'input', 'files'], []);
    }

    public function getExpectedOutputFiles(): array
    {
        return $this->getArrayValue(['storage', 'output', 'files'], []);
    }

    public function getInputTables(): array
    {
        return $this->getArrayValue(['storage', 'input', 'tables'], []);
    }

    public function getExpectedOutputTables(): array
    {
        return $this->getArrayValue(['storage', 'output', 'tables'], []);
    }

    /**
     * @return mixed
     */
    public function getOAuthApiData()
    {
        return $this->getValue(['authorization', 'oauth_api', 'credentials', '#data'], '');
    }

    public function getOAuthApiAppSecret(): string
    {
        return $this->getStringValue(['authorization', 'oauth_api', 'credentials', '#appSecret'], '');
    }

    public function getOAuthApiAppKey(): string
    {
        return $this->getStringValue(['authorization', 'oauth_api', 'credentials', 'appKey'], '');
    }

    public function getEnvKbcRunID(): string
    {
        return (string) getenv('KBC_RUNID');
    }

    public function getEnvKbcProjectId(): int
    {
        return (int) getenv('KBC_PROJECTID');
    }

    public function getEnvKbcStackId(): string
    {
        return (string) getenv('KBC_STACKID');
    }

    public function getEnvKbcConfigId(): string
    {
        return (string) getenv('KBC_CONFIGID');
    }

    public function getEnvKbcConfigRowId(): string
    {
        return (string) getenv('KBC_CONFIGROWID');
    }

    public function getEnvKbcComponentId(): string
    {
        return (string) getenv('KBC_COMPONENTID');
    }

    public function getEnvKbcBranchId(): string
    {
        return (string) getenv('KBC_BRANCHID');
    }

    public function getEnvKbcStagingFileProvider(): string
    {
        return (string) getenv('KBC_STAGING_FILE_PROVIDER');
    }

    public function getEnvComponentRunMode(): string
    {
        return (string) getenv('KBC_COMPONENT_RUN_MODE');
    }

    public function getEncKbcProjectName(): string
    {
        $env = getenv('KBC_PROJECTNAME');
        if (!$env) {
            throw new InvalidConfigurationException('The variable "KBC_PROJECTNAME" is not allowed.');
        }
        return (string) $env;
    }

    public function getEnvKbcTokenId(): string
    {
        $env = getenv('KBC_TOKENID');
        if (!$env) {
            throw new InvalidConfigurationException('The variable "KBC_TOKENID" is not allowed.');
        }
        return (string) $env;
    }

    public function getEnvKbcTokenDescription(): string
    {
        $env = getenv('KBC_TOKENDESC');
        if (!$env) {
            throw new InvalidConfigurationException('The variable "KBC_TOKENDESC" is not allowed.');
        }
        return (string) $env;
    }

    public function getEnvKbcToken(): string
    {
        $env = getenv('KBC_TOKEN');
        if (!$env) {
            throw new InvalidConfigurationException('The variable "KBC_TOKEN" is not allowed.');
        }
        return (string) $env;
    }

    public function getEnvKbcUrl(): string
    {
        $env = getenv('KBC_URL');
        if (!$env) {
            throw new InvalidConfigurationException('The variable "KBC_URL" is not allowed.');
        }
        return (string) $env;
    }
}
