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
