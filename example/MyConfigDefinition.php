<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class MyConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        $parametersNode /** @phpstan-ignore-line */
            ->isRequired()
            ->children()
                ->scalarNode('api_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('#password')
                    ->isRequired()
                    ->cannotBeEmpty();
        return $parametersNode;
    }
}
