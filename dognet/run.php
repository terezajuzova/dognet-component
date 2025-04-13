<?php

error_reporting(E_ALL & ~E_DEPRECATED);

declare(strict_types=1);

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use MyComponent\Component;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $app = new Component($logger);
    $app->execute();
    exit(0);
} catch (UserException $e) {
    //Keboola ignores any other level
    $logger->info("UserException in run.php: ". $e->getMessage());
    $logger->error($e->getMessage());
    exit(1);
} catch (Throwable $e) {
    //Keboola ignores any other level
    $logger->info("Throwable in run.php: ". $e->getMessage());

    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => is_object($e->getPrevious()) ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
