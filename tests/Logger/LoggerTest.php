<?php

declare(strict_types=1);

namespace Keboola\Component\Tests\Logger;

use DateTimeImmutable;
use Keboola\Component\Config\BaseConfig;
use Keboola\Component\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testDefaultHandler(): void
    {
        $logger = new Logger();
        $logger->debug('test');

        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);

        /** @var StreamHandler $streamHandler */
        $streamHandler = $handlers[0];
        $this->assertSame('php://stderr', $streamHandler->getUrl());
        $this->assertSame(Logger::DEBUG, $streamHandler->getLevel());
    }

    public function testSetupSyncActionLogging(): void
    {
        $logger = new Logger();
        $logger->debug('test');
        $logger->setupSyncActionLogging();

        $handlers = $logger->getHandlers();
        $this->assertCount(2, $handlers);

        /** @var StreamHandler $streamHandler1 */
        $streamHandler1 = $handlers[0];
        $this->assertSame('php://stderr', $streamHandler1->getUrl());
        $this->assertSame(Logger::CRITICAL, $streamHandler1->getLevel());

        /** @var StreamHandler $streamHandler2 */
        $streamHandler2 = $handlers[1];
        $this->assertSame('php://stderr', $streamHandler2->getUrl());
        $this->assertSame(Logger::ERROR, $streamHandler2->getLevel());

        // Init streams (stream is created with first message)
        $streamHandler1->handle([
            'level' => Logger::CRITICAL,
            'message' => '',
            'extra' => [],
            'context' => [],
            'datetime' => new DateTimeImmutable(),
            'channel' => '',
            'level_name' => Logger::getLevelName(Logger::CRITICAL),
        ]);
        $streamHandler2->handle([
            'level' => Logger::ERROR,
            'message' => '',
            'extra' => [],
            'context' => [],
            'datetime' => new DateTimeImmutable(),
            'channel' => '',
            'level_name' => Logger::getLevelName(Logger::ERROR),
        ]);

        // Connect tester (logger) to the streams
        /** @var resource $stream1 */
        $stream1 = $streamHandler1->getStream();
        /** @var resource $stream2 */
        $stream2 = $streamHandler2->getStream();
        StreamTester::attach($stream1);
        StreamTester::attach($stream2);

        // Log some messages
        $logger->info('Info!', ['context' => 'info']);
        $logger->error('Error!', ['context' => 'error']);
        $logger->critical('Critical!', ['context' => 'critical']);

        // Critical (application error) message has context in sync action
        $this->assertSame(
            "Error!\n".
            "Critical! {\"context\":\"critical\"} \n",
            StreamTester::getContent()
        );
    }

    public function testDebugLogging(): void
    {
        $logger = new Logger();
        $logger->setupAsyncActionLogging(BaseConfig::COMPONENT_RUN_MODE_DEBUG);

        $handlers = $logger->getHandlers();

        $this->assertCount(4, $handlers);

        // Init streams (stream is created with first message)
        $logger->critical('');
        $logger->error('');
        $logger->info('');
        $logger->debug('');

        /** @var StreamHandler $handlerCritical */
        $handlerCritical = $handlers[0];
        /** @var StreamHandler $handlerError */
        $handlerError = $handlers[1];
        /** @var StreamHandler $handlerLog */
        $handlerLog = $handlers[2];
        /** @var StreamHandler $handlerDebug */
        $handlerDebug = $handlers[3];

        // Connect tester (logger) to the streams
        /** @var resource $streamCritical */
        $streamCritical = $handlerCritical->getStream();
        /** @var resource $streamError */
        $streamError = $handlerError->getStream();
        /** @var resource $streamLog */
        $streamLog = $handlerLog->getStream();
        /** @var resource $streamDebug */
        $streamDebug = $handlerDebug->getStream();
        StreamTester::attach($streamCritical);
        StreamTester::attach($streamError);
        StreamTester::attach($streamLog);
        StreamTester::attach($streamDebug);

        // Log some messages
        $logger->info('Info!', ['context' => 'info']);
        $logger->error('Error!', ['context' => 'error']);
        $logger->critical('Critical!', ['context' => 'critical']);
        $logger->debug('Debug message!', ['context' => 'debug context']);

        Assert::assertStringContainsString('Info!', StreamTester::getContent());
        Assert::assertStringContainsString('Error!', StreamTester::getContent());
        Assert::assertStringContainsString(
            'CRITICAL: Critical! {"context":"critical"}',
            StreamTester::getContent()
        );
        Assert::assertStringContainsString(
            'DEBUG: Debug message! {"context":"debug context"}',
            StreamTester::getContent()
        );

        $this->assertSame(Logger::CRITICAL, $handlerCritical->getLevel());
        $this->assertSame(Logger::WARNING, $handlerError->getLevel());
        $this->assertSame(Logger::INFO, $handlerLog->getLevel());
        $this->assertSame(Logger::DEBUG, $handlerDebug->getLevel());

        $this->assertSame('php://stderr', $handlerCritical->getUrl());
        $this->assertSame('php://stderr', $handlerError->getUrl());
        $this->assertSame('php://stdout', $handlerLog->getUrl());
        $this->assertSame('php://stdout', $handlerDebug->getUrl());
    }

    public function testSetupAsyncActionLogging(): void
    {
        $logger = new Logger();
        $logger->debug('test');
        $logger->setupAsyncActionLogging();

        $handlers = $logger->getHandlers();
        $this->assertCount(3, $handlers);
    }
}
