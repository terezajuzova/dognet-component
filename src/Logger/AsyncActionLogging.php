<?php

declare(strict_types=1);

namespace Keboola\Component\Logger;

use Keboola\Component\Config\BaseConfig;

interface AsyncActionLogging
{
    public function setupAsyncActionLogging(string $componentRunMode = BaseConfig::COMPONENT_RUN_MODE_RUN): void;
}
