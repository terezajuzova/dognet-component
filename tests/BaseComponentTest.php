<?php

declare(strict_types=1);

namespace Keboola\Component\Tests;

use Exception;
use Keboola\Component\BaseComponent;
use Keboola\Component\Exception\BaseComponentException;
use Keboola\Component\Logger;
use Monolog\Handler\NullHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class BaseComponentTest extends TestCase
{
    public function testLoadInputStateFile(): void
    {
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/state-file'
        ));

        $baseComponent = new BaseComponent($this->getLogger());

        $inputStateFile = $baseComponent->getInputState();
        $this->assertCount(4, $inputStateFile);

        $this->assertArrayHasKey('key1', $inputStateFile);
        $this->assertEquals('value1', $inputStateFile['key1']);

        $this->assertArrayHasKey('key2', $inputStateFile);
        $this->assertEquals(2, $inputStateFile['key2']);

        $this->assertArrayHasKey('list', $inputStateFile);
        $this->assertCount(3, $inputStateFile['list']);
        $this->assertEquals('a', $inputStateFile['list'][0]);
        $this->assertEquals('b', $inputStateFile['list'][1]);
        $this->assertEquals('c', $inputStateFile['list'][2]);

        $this->assertArrayHasKey('dict', $inputStateFile);
        $this->assertCount(1, $inputStateFile['dict']);
        $this->assertArrayHasKey('key', $inputStateFile['dict']);
        $this->assertEquals('value', $inputStateFile['dict']['key']);
    }

    public function testSyncActions(): void
    {
        $logger = $this->getLogger();
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/sync-action'
        ));
        $baseComponent = new class ($logger) extends BaseComponent
        {
            protected function run(): void
            {
                throw new Exception('Not implemented');
            }

            protected function getSyncActions(): array
            {
                return ['sync' => 'handleSync'];
            }

            public function handleSync(): array
            {
                return ['status' => 'success', 'count' => 20];
            }
        };
        $expectedJson = '{"status":"success","count":20}';
        $this->expectOutputString($expectedJson);
        $baseComponent->execute();
    }

    public function testRunAction(): void
    {
        $logger = $this->getLogger();
        $handler = new TestHandler();
        $logger->setHandlers([$handler]);
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/run-action'
        ));
        $baseComponent = new class ($logger) extends BaseComponent
        {
            protected function run(): void
            {
                echo 'Shitty output';
                $this->getLogger()->alert('Log message from run');
            }
        };
        $this->expectOutputString('Shitty output');
        $baseComponent->execute();

        $this->assertTrue($handler->hasAlert('Log message from run'));
    }

    public function testWillNotFailWithEmptyConfigAction(): void
    {
        $logger = $this->getLogger();
        $handler = new TestHandler();
        $logger->setHandlers([$handler]);
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/empty-config-file'
        ));
        $baseComponent = new class ($logger) extends BaseComponent
        {
            protected function run(): void
            {
                echo 'Shitty output';
                $this->getLogger()->alert('Log message from run');
            }
        };
        $this->expectOutputString('Shitty output');
        $baseComponent->execute();

        $this->assertTrue($handler->hasAlert('Log message from run'));
    }

    public function testLoadInputStateFileEmptyThrowsException(): void
    {
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/empty-state-file'
        ));

        $logger = new Logger();

        $this->expectException(NotEncodableValueException::class);
        $this->expectExceptionMessage('Syntax error');
        new BaseComponent($logger);
    }

    public function testLoadInputStateFileUndefined(): void
    {
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/undefined-state-file'
        ));

        $baseComponent = new BaseComponent($this->getLogger());

        $this->assertSame([], $baseComponent->getInputState());
    }

    public function testCannotSetUpInvalidSyncActions(): void
    {
        $logger = new Logger();
        $this->expectException(BaseComponentException::class);
        $this->expectExceptionMessage('Unknown sync action "nonexistentMethod", method does not exist in class');
        new class($logger) extends BaseComponent
        {
            protected function getSyncActions(): array
            {
                return ['nonexistentMethod'];
            }
        };
    }

    public function testRunCannotBeSyncAction(): void
    {
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/run-action'
        ));
        $logger = new Logger();
        $this->expectException(BaseComponentException::class);
        $this->expectExceptionMessage('"run" cannot be a sync action');
        new class($logger) extends BaseComponent
        {
            protected function getSyncActions(): array
            {
                return ['run' => 'run'];
            }
        };
    }

    public function testRunActionCannotBePublic(): void
    {
        putenv(sprintf(
            'KBC_DATADIR=%s',
            __DIR__ . '/fixtures/base-component-data-dir/run-action'
        ));
        $this->expectException(BaseComponentException::class);
        $this->expectExceptionMessage('Method "run" cannot be public since version 7');
        new class($this->getLogger()) extends BaseComponent
        {
            public function run(): void
            {
                return;
            }
        };
    }

    private function getLogger(): MonologLogger
    {
        $logger = new MonologLogger('app');
        $logger->setHandlers([new NullHandler()]);
        return $logger;
    }
}
