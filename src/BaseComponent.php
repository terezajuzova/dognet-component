<?php

declare(strict_types=1);

namespace Keboola\Component;

use ErrorException;
use Exception;
use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Config\BaseConfigDefinition;
use Keboola\Component\Exception\BaseComponentException;
use Keboola\Component\Logger\AsyncActionLogging;
use Keboola\Component\Logger\SyncActionLogging;
use Keboola\Component\Manifest\ManifestManager;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;
use Reflection;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use function error_reporting;

/**
 * This is the core class that does all the heavy lifting. By default you don't need to setup anything. There are some
 * extension points for you to use if you want to customise the behavior.
 */
class BaseComponent
{
    protected BaseConfig $config;

    private string $dataDir;

    private ManifestManager $manifestManager;

    private LoggerInterface $logger;

    /** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification */
    private array $inputState;

    public function __construct(LoggerInterface $logger)
    {
        static::setEnvironment();
        $this->logger = $logger;

        $dataDir = getenv('KBC_DATADIR') === false ? '/data/' : (string) getenv('KBC_DATADIR');
        $this->setDataDir($dataDir);

        $this->loadConfig();
        $this->initializeSyncActions();
        $this->loadInputState();

        $this->loadManifestManager();

        $this->checkRunMethodNotPublic();

        $this->logger->debug('Component initialization completed');
    }

    /**
     * Prepares environment. Sets error reporting for the app to fail on any
     * error, warning or notice. If your code emits notices and cannot be
     * fixed, you can set `error_reporting` in `$application->run()` method.
     */
    public static function setEnvironment(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                // respect error_reporting() level
                // libraries used in custom components may emit notices that cannot be fixed
                return false;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    /**
     * Automatically loads configuration from datadir, instantiates specified
     * config class and validates it with specified confing definition class
     */
    protected function loadConfig(): void
    {
        $configClass = $this->getConfigClass();
        $configDefinitionClass = $this->getConfigDefinitionClass();
        try {
            /** @var BaseConfig $config */
            $config = new $configClass(
                $this->getRawConfig(),
                new $configDefinitionClass()
            );
            $this->config = $config;
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }
    }

    protected function initializeSyncActions(): void
    {
        if (array_key_exists('run', $this->getSyncActions())) {
            throw BaseComponentException::runCannotBeSyncAction();
        }
        foreach ($this->getSyncActions() as $method) {
            if (!method_exists($this, $method)) {
                throw BaseComponentException::invalidSyncAction($method);
            }
        }
        if ($this->isSyncAction()) {
            if ($this->logger instanceof SyncActionLogging) {
                $this->logger->setupSyncActionLogging($this->config->getEnvComponentRunMode());
            }
        } else {
            if ($this->logger instanceof AsyncActionLogging) {
                $this->logger->setupAsyncActionLogging($this->config->getEnvComponentRunMode());
            }
        }
    }

    protected function loadInputState(): void
    {
        try {
            $this->inputState = JsonHelper::readFile($this->getDataDir() . '/in/state.json');
        } catch (FileNotFoundException $exception) {
            $this->inputState = [];
        }
    }

    private function checkRunMethodNotPublic(): void
    {
        $reflection = new ReflectionClass(static::class);
        $method = $reflection->getMethod('run');
        if ($method->isPublic()) {
            throw BaseComponentException::runMethodCannotBePublic();
        }
    }

    protected function writeOutputStateToFile(array $state): void
    {
        JsonHelper::writeFile(
            $this->getDataDir() . '/out/state.json',
            $state
        );
    }

    protected function getRawConfig(): array
    {
        return JsonHelper::readFile($this->dataDir . '/config.json');
    }

    /**
     * Override this method if you have custom config definition class. This
     * allows you to validate and require config parameters and fail fast if
     * there is a missing parameter.
     */
    protected function getConfigDefinitionClass(): string
    {
        return BaseConfigDefinition::class;
    }

    protected function setConfig(BaseConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * Data dir is set without the trailing slash
     */
    protected function setDataDir(string $dataDir): void
    {
        $this->dataDir = rtrim($dataDir, '/');
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function getConfig(): BaseConfig
    {
        return $this->config;
    }

    public function getManifestManager(): ManifestManager
    {
        return $this->manifestManager;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getInputState(): array
    {
        return $this->inputState;
    }

    public function execute(): void
    {
        if (!$this->isSyncAction()) {
            $this->run();
            return;
        }

        $action = $this->getConfig()->getAction();
        $syncActions = $this->getSyncActions();
        if (array_key_exists($action, $syncActions)) {
            $method = $syncActions[$action];
            echo JsonHelper::encode($this->$method());
        } else {
            throw BaseComponentException::invalidSyncAction($action);
        }
    }

    /**
     * This is the main method for your code to run in. You have the `Config`
     * and `ManifestManager` ready as well as environment set up.
     */
    protected function run(): void
    {
        // to be implemented in subclass
    }

    /**
     * Class of created config. It's useful if you want to implement getters for
     * parameters in your config. It's preferable to accessing configuration
     * keys as arrays.
     */
    protected function getConfigClass(): string
    {
        return BaseConfig::class;
    }

    /**
     * Loads manifest manager with application's datadir
     */
    protected function loadManifestManager(): void
    {
        $this->manifestManager = new ManifestManager($this->dataDir);
    }

    public function isSyncAction(): bool
    {
        return $this->getConfig()->getAction() !== 'run';
    }

    /**
     * Whitelist method names that can be used as synchronous actions. This is a
     * safeguard against executing any method of the component.
     *
     * Format: 'action' => 'method name' (e.g. 'getTables' => 'handleTableSyncAction')
     */
    protected function getSyncActions(): array
    {
        return [];
    }
}
