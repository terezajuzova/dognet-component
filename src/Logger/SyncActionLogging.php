<?php

declare(strict_types=1);

namespace Keboola\Component\Logger;

use Keboola\Component\Config\BaseConfig;

interface SyncActionLogging
{
    /**
     * Sync actions MUST NOT output anything to stdout
     */
    public function setupSyncActionLogging(string $componentRunMode = BaseConfig::COMPONENT_RUN_MODE_RUN): void;
}
