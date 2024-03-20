<?php

declare(strict_types=1);

namespace Keboola\Component\Tests\Config;

use Generator;
use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Config\BaseConfigDefinition;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class BaseConfigTest extends TestCase
{
    public function testWillCreateConfigFromArray(): void
    {
        $config = new BaseConfig([]);

        $this->assertInstanceOf(BaseConfig::class, $config);
    }

    public function testCanOverrideParametersDefinition(): void
    {
        $configDefinition = new class extends BaseConfigDefinition implements ConfigurationInterface
        {
            protected function getParametersDefinition(): ArrayNodeDefinition
            {
                $nodeDefinition = parent::getParametersDefinition();
                // @formatter:off
                $nodeDefinition->isRequired();
                $nodeDefinition
                    ->children()
                        ->scalarNode('requiredValue')
                            ->isRequired()
                            ->cannotBeEmpty();
                // @formatter:on
                return $nodeDefinition;
            }
        };

        try {
            new BaseConfig(['parameters' => []], $configDefinition);
            $this->fail('Expected "InvalidConfigurationException" exception.');
        } catch (InvalidConfigurationException $e) {
            $this->assertContains(
                $e->getMessage(),
                [
                    'The child node "requiredValue" at path "root.parameters" must be configured.',
                    'The child config "requiredValue" under "root.parameters" must be configured.',
                ]
            );
        }
    }

    public function testStrictParametersCheck(): void
    {
        $configDefinition = new class extends BaseConfigDefinition implements ConfigurationInterface
        {
            protected function getParametersDefinition(): ArrayNodeDefinition
            {
                $nodeDefinition = parent::getParametersDefinition();
                // @formatter:off
                $nodeDefinition->isRequired();
                $nodeDefinition
                    ->children()
                    ->scalarNode('requiredValue')
                    ->isRequired()
                    ->cannotBeEmpty();
                // @formatter:on
                return $nodeDefinition;
            }
        };

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "extraValue" under "root.parameters"');

        new BaseConfig(['parameters' => ['requiredValue' => 'yes', 'extraValue' => 'no']], $configDefinition);
    }

    public function testCanOverrideRootDefinition(): void
    {
        $configDefinition = new class extends BaseConfigDefinition implements ConfigurationInterface
        {
            protected function getRootDefinition(TreeBuilder $treeBuilder): ArrayNodeDefinition
            {
                $rootNode = parent::getRootDefinition($treeBuilder);
                $rootNode
                    ->children()
                    ->scalarNode('requiredRootNode')
                    ->isRequired()
                    ->cannotBeEmpty();
                return $rootNode;
            }
        };

        try {
            new BaseConfig([], $configDefinition);
            $this->fail('Expected "InvalidConfigurationException" exception.');
        } catch (InvalidConfigurationException $e) {
            $this->assertContains(
                $e->getMessage(),
                [
                    'The child node "requiredRootNode" at path "root" must be configured.',
                    'The child config "requiredRootNode" under "root" must be configured.',
                ]
            );
        }
    }

    public function testIsForwardCompatible(): void
    {
        $config = new BaseConfig(['yetNonexistentKey' => 'value']);
        $this->assertSame(['yetNonexistentKey' => 'value'], $config->getData());
    }

    public function testGettersWillNotFailIfKeyIsMissing(): void
    {
        $config = new BaseConfig([
            'lorem' => [
                'ipsum' => [
                    'dolores' => 'value',
                ],
            ],
        ]);
        $this->assertSame([], $config->getParameters());
        $this->assertSame('run', $config->getAction());
        $this->assertSame([], $config->getAuthorization());
        $this->assertSame('', $config->getOAuthApiAppKey());
        $this->assertSame('', $config->getOAuthApiAppSecret());
        $this->assertSame('', $config->getOAuthApiData());
        $this->assertSame([], $config->getImageParameters());
        $this->assertSame([], $config->getStorage());
        $this->assertSame('', $config->getValue(['parameters', 'ipsum', 'dolor'], ''));
    }

    public function testGettersWillGetKeyIfPresent(): void
    {
        $configDefinition = new class extends BaseConfigDefinition implements ConfigurationInterface
        {
            protected function getParametersDefinition(): ArrayNodeDefinition
            {
                $nodeDefinition = parent::getParametersDefinition();
                // @formatter:off
                $nodeDefinition->isRequired();
                $nodeDefinition
                    ->children()
                    ->arrayNode('ipsum')
                        ->children()
                            ->scalarNode('dolor');
                // @formatter:on
                return $nodeDefinition;
            }
        };
        $config = new BaseConfig([
            'parameters' => [
                'ipsum' => [
                    'dolor' => 'value',
                ],
            ],
            'action' => 'run',
            'authorization' => [
                'oauth_api' => [
                    'credentials' => [
                        '#data' => 'value',
                        '#appSecret' => 'secret',
                        'appKey' => 'key',
                    ],
                ],
            ],
            'image_parameters' => ['param1' => 'value1'],
            'storage' => [
                'input' => [
                    'tables' => [],
                ],
                'output' => [
                    'files' => [],
                ],
            ],
        ], $configDefinition);
        $this->assertEquals(
            [
                'ipsum' => [
                    'dolor' => 'value',
                ],
            ],
            $config->getParameters()
        );
        $this->assertEquals(
            'run',
            $config->getAction()
        );
        $this->assertEquals(
            [
                'oauth_api' => [
                    'credentials' => [
                        '#data' => 'value',
                        '#appSecret' => 'secret',
                        'appKey' => 'key',
                    ],
                ],
            ],
            $config->getAuthorization()
        );
        $this->assertEquals(
            'value',
            $config->getOAuthApiData()
        );
        $this->assertEquals(
            'secret',
            $config->getOAuthApiAppSecret()
        );
        $this->assertEquals(
            'key',
            $config->getOAuthApiAppKey()
        );
        $this->assertEquals(
            ['param1' => 'value1'],
            $config->getImageParameters()
        );
        $this->assertEquals(
            [
                'input' => [
                    'tables' => [],
                ],
                'output' => [
                    'files' => [],
                ],
            ],
            $config->getStorage()
        );
        $this->assertEquals(
            'value',
            $config->getValue(['parameters', 'ipsum', 'dolor'])
        );
    }

    /**
     * @dataProvider envGettersDataProvider
     */
    public function testEnvGetters(array $envs): void
    {
        foreach ($envs as $env => $value) {
            putenv(sprintf('%s=%s', $env, $value));
        }

        $config = new BaseConfig([], new BaseConfigDefinition());

        Assert::assertEquals($envs['KBC_RUNID'], $config->getEnvKbcRunID());
        Assert::assertEquals($envs['KBC_PROJECTID'], $config->getEnvKbcProjectId());
        Assert::assertEquals($envs['KBC_STACKID'], $config->getEnvKbcStackId());
        Assert::assertEquals($envs['KBC_CONFIGID'], $config->getEnvKbcConfigId());
        Assert::assertEquals($envs['KBC_CONFIGROWID'], $config->getEnvKbcConfigRowId());
        Assert::assertEquals($envs['KBC_COMPONENTID'], $config->getEnvKbcComponentId());
        Assert::assertEquals($envs['KBC_BRANCHID'], $config->getEnvKbcBranchId());
        Assert::assertEquals($envs['KBC_STAGING_FILE_PROVIDER'], $config->getEnvKbcStagingFileProvider());

        if (!isset($envs['KBC_PROJECTNAME'])) {
            try {
                $config->getEncKbcProjectName();
                $this->fail('Should be fail.');
            } catch (InvalidConfigurationException $e) {
                Assert::assertEquals('The variable "KBC_PROJECTNAME" is not allowed.', $e->getMessage());
            }
        } else {
            Assert::assertEquals($envs['KBC_PROJECTNAME'], $config->getEncKbcProjectName());
        }

        if (!isset($envs['KBC_TOKENID'])) {
            try {
                $config->getEnvKbcTokenId();
                $this->fail('Should be fail.');
            } catch (InvalidConfigurationException $e) {
                Assert::assertEquals('The variable "KBC_TOKENID" is not allowed.', $e->getMessage());
            }
        } else {
            Assert::assertEquals($envs['KBC_TOKENID'], $config->getEnvKbcTokenId());
        }

        if (!isset($envs['KBC_TOKENDESC'])) {
            try {
                $config->getEnvKbcTokenDescription();
                $this->fail('Should be fail.');
            } catch (InvalidConfigurationException $e) {
                Assert::assertEquals('The variable "KBC_TOKENDESC" is not allowed.', $e->getMessage());
            }
        } else {
            Assert::assertEquals($envs['KBC_TOKENDESC'], $config->getEnvKbcTokenDescription());
        }

        if (!isset($envs['KBC_TOKEN'])) {
            try {
                $config->getEnvKbcToken();
                $this->fail('Should be fail.');
            } catch (InvalidConfigurationException $e) {
                Assert::assertEquals('The variable "KBC_TOKEN" is not allowed.', $e->getMessage());
            }
        } else {
            Assert::assertEquals($envs['KBC_TOKEN'], $config->getEnvKbcToken());
        }

        if (!isset($envs['KBC_URL'])) {
            try {
                $config->getEnvKbcUrl();
                $this->fail('Should be fail.');
            } catch (InvalidConfigurationException $e) {
                Assert::assertEquals('The variable "KBC_URL" is not allowed.', $e->getMessage());
            }
        } else {
            Assert::assertEquals($envs['KBC_URL'], $config->getEnvKbcUrl());
        }

        foreach ($envs as $env => $value) {
            putenv(sprintf('%s', $env));
        }
    }

    public function envGettersDataProvider(): Generator
    {
        yield 'envsWithoutForwardToken' => [
            [
                'KBC_RUNID' => 'runId',
                'KBC_PROJECTID' => 123456,
                'KBC_STACKID' => 'stackId',
                'KBC_CONFIGID' => 'configId',
                'KBC_CONFIGROWID' => 'configRowId',
                'KBC_COMPONENTID' => 'componentId',
                'KBC_BRANCHID' => 'brancId',
                'KBC_STAGING_FILE_PROVIDER' => 'staging_file_provider',
            ],
        ];

        yield 'allEnvIsSet' => [
            [
                'KBC_RUNID' => 'runId',
                'KBC_PROJECTID' => 123456,
                'KBC_STACKID' => 'stackId',
                'KBC_CONFIGID' => 'configId',
                'KBC_CONFIGROWID' => 'configRowId',
                'KBC_COMPONENTID' => 'componentId',
                'KBC_BRANCHID' => 'brancId',
                'KBC_STAGING_FILE_PROVIDER' => 'staging_file_provider',
                'KBC_PROJECTNAME' => 'projectName',
                'KBC_TOKENID' => 'tokenId',
                'KBC_TOKENDESC' => 'tokenDesc',
                'KBC_TOKEN' => 'token',
                'KBC_URL' => 'url',
            ],
        ];
    }
}
