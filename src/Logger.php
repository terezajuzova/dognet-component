<?php

declare(strict_types=1);

namespace Keboola\Component;

use Keboola\Component\Config\BaseConfig;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger implements Logger\SyncActionLogging, Logger\AsyncActionLogging
{
    public static function getDefaultErrorHandler(): StreamHandler
    {
        $errorHandler = new StreamHandler('php://stderr');
        $errorHandler->setBubble(false);
        $errorHandler->setLevel(MonologLogger::WARNING);
        $errorHandler->setFormatter(new LineFormatter("%message%\n"));
        return $errorHandler;
    }

    public static function getDefaultLogHandler(): StreamHandler
    {
        $logHandler = new StreamHandler('php://stdout');
        $logHandler->setBubble(false);
        $logHandler->setLevel(MonologLogger::INFO);
        $logHandler->setFormatter(new LineFormatter("%message%\n"));
        return $logHandler;
    }

    public static function getDefaultCriticalHandler(): StreamHandler
    {
        $handler = new StreamHandler('php://stderr');
        $handler->setBubble(false);
        $handler->setLevel(MonologLogger::CRITICAL);
        $handler->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n"));
        return $handler;
    }

    public static function getDefaultDebugHandler(): StreamHandler
    {
        $handler = new StreamHandler('php://stdout');
        $handler->setBubble(false);
        $handler->setLevel(MonologLogger::DEBUG);
        $handler->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n"));
        return $handler;
    }

    public static function getSyncActionErrorHandler(): StreamHandler
    {
        $logHandler = new StreamHandler('php://stderr');
        $logHandler->setBubble(false);
        $logHandler->setLevel(MonologLogger::ERROR);
        $logHandler->setFormatter(new LineFormatter("%message%\n"));
        return $logHandler;
    }

    public static function getSyncActionCriticalHandler(): StreamHandler
    {
        $logHandler = new StreamHandler('php://stderr');
        $logHandler->setBubble(false);
        $logHandler->setLevel(MonologLogger::CRITICAL);
        $logHandler->setFormatter(new LineFormatter("%message% %context% %extra%\n", null, false, true));
        return $logHandler;
    }

    public static function getSyncActionDebugHandler(): StreamHandler
    {
        $logHandler = new StreamHandler('php://stdout');
        $logHandler->setBubble(false);
        $logHandler->setLevel(MonologLogger::DEBUG);
        $logHandler->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n"));
        return $logHandler;
    }

    public function __construct()
    {
        parent::__construct('php-component');

        // Add default logger to log errors in configuration, etc.
        // It will be overwritten by calling setupSyncActionLogging/setupAsyncActionLogging
        // from BaseComponent::initializeSyncActions
        $logHandler = new StreamHandler('php://stderr', static::DEBUG);
        $logHandler->setFormatter(new LineFormatter("%message%\n"));
        $this->pushHandler($logHandler);
    }

    public function setupSyncActionLogging(string $componentRunMode = BaseConfig::COMPONENT_RUN_MODE_RUN): void
    {
        $handlers = [
            self::getSyncActionCriticalHandler(),
            self::getSyncActionErrorHandler(),
        ];

        if ($componentRunMode === BaseConfig::COMPONENT_RUN_MODE_DEBUG) {
            $handlers[] = self::getSyncActionDebugHandler();
        }

        $this->setHandlers($handlers);
    }

    public function setupAsyncActionLogging(string $componentRunMode = BaseConfig::COMPONENT_RUN_MODE_RUN): void
    {
        $handlers = [
            self::getDefaultCriticalHandler(),
            self::getDefaultErrorHandler(),
            self::getDefaultLogHandler(),
        ];

        if ($componentRunMode === BaseConfig::COMPONENT_RUN_MODE_DEBUG) {
            $handlers[] = self::getDefaultDebugHandler();
        }

        $this->setHandlers($handlers);
    }
}
