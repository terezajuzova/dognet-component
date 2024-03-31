<?php

//$logger->error('*******Start*********');
fwrite(STDOUT, '*************Start*******');

/*declare(strict_types=1);

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use Keboola\Component\MyComponent;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $logger->error('*******Start*********');
    fwrite(STDOUT, '*************Start*******');
    $app = new MyComponent($logger);
    $app->execute();
    $logger->error('*******End*********');
    fwrite(STDOUT, '*************End**********');
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (Throwable $e) {
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
}*/
