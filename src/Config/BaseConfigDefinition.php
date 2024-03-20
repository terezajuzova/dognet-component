<?php

declare(strict_types=1);

namespace Keboola\Component\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * ConfigDefinition specifies the bare minimum of what should a config contain.
 * It's a best practice to extend it and define all parameters required by your code.
 * That way you can be sure that your code has all the data it needs and it can fail fast
 * otherwise. Usually your code requires some parameters, so it's easiest to extend this
 * class and just override `getParametersDefinition()` method.
 */
class BaseConfigDefinition implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder. You probably don't need to touch this.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('root');
        $this->getRootDefinition($treeBuilder);
        return $treeBuilder;
    }

    /**
     * Definition of parameters section. Override in extending class to validate parameters sent to the component early.
     */
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder('parameters');
        /** @var ArrayNodeDefinition $parametersNode */
        $parametersNode = $builder->getRootNode();
        return $parametersNode;
    }

    /**
     * Root definition to be overridden in special cases
     */
    protected function getRootDefinition(TreeBuilder $treeBuilder): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->ignoreExtraKeys(false);

        // @formatter:off
        $rootNode
            ->children()
                ->append($this->getParametersDefinition());
        // @formatter:on

        return $rootNode;
    }
}
