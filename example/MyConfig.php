<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfig;

class MyConfig extends BaseConfig
{
    public function getMaximumAllowedErrorCount(): int
    {
        $defaultValue = 0;
        return $this->getIntValue(['parameters', 'errorCount', 'maximumAllowed'], $defaultValue);
    }
}
