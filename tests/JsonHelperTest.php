<?php

declare(strict_types=1);

namespace Keboola\Component\Tests;

use Keboola\Component\JsonHelper;
use Keboola\Component\JsonHelper\JsonHelperException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class JsonHelperTest extends TestCase
{
    public function testDecodeSuccessfully(): void
    {
        $json = '{"key":"val","nums":[2,3]}';
        $this->assertSame(
            [
                'key' => 'val',
                'nums' => [2, 3],
            ],
            JsonHelper::decode($json)
        );
    }

    public function testDecodeWrongJsonThrowsException(): void
    {
        $json = '{"key":"val"';
        $this->expectException(NotEncodableValueException::class);
        $this->expectExceptionMessage('Syntax error');
        JsonHelper::decode($json);
    }

    public function testEncodeNonFormattedSuccessfully(): void
    {
        $array = [
            'key' => 'val',
            'keys' => [0, 1, 2],
        ];

        $this->assertSame(
            '{"key":"val","keys":[0,1,2]}',
            JsonHelper::encode($array)
        );
    }

    public function testEncodeFormattedSuccessfully(): void
    {
        $array = [
            'key' => 'val',
            'keys' => [0, 1, 2],
        ];

        $this->assertSame(
            '{
    "key": "val",
    "keys": [
        0,
        1,
        2
    ]
}',
            JsonHelper::encode($array, true)
        );
    }

    public function testReadNonExistingFileThrowsException(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File "/dev/null/file.json" could not be found.');
        JsonHelper::readFile('/dev/null/file.json');
    }

    public function testReadInvalidFileThrowsException(): void
    {
        $this->expectException(NotEncodableValueException::class);
        $this->expectExceptionMessage('Syntax error');
        JsonHelper::readFile(__DIR__ . '/fixtures/json-file-helper-test/invalidJsonFile.json');
    }

    public function testReadFileSuccessfully(): void
    {
        $array = JsonHelper::readFile(__DIR__ . '/fixtures/json-file-helper-test/file.json');
        $this->assertSame(
            [
                'key' => 'value',
                'keys' => ['a', 'b'],
            ],
            $array
        );
    }

    public function testWriteToFileSuccessfully(): void
    {
        $filePath = __DIR__ . '/fixtures/json-file-helper-test/tmp.json';
        $array = [
            'key' => 'val',
            'keys' => [0, 1, 2],
        ];
        JsonHelper::writeFile($filePath, $array);

        $this->assertSame(
            '{"key":"val","keys":[0,1,2]}',
            file_get_contents($filePath)
        );
    }

    public function testWriteToFilePrettyPrintedSuccessfully(): void
    {
        $filePath = __DIR__ . '/fixtures/json-file-helper-test/tmp.json';
        $array = [
            'key' => 'val',
            'keys' => [0, 1, 2],
        ];
        JsonHelper::writeFile($filePath, $array, true);

        $this->assertSame(
            '{
    "key": "val",
    "keys": [
        0,
        1,
        2
    ]
}',
            file_get_contents($filePath)
        );
        unlink($filePath);
    }

    public function testWriteToNonExistingDirectorySuccessfully(): void
    {
        $filePath = __DIR__ . '/non-existing-folder/tmp.json';
        $array = [
            'key' => 'val',
        ];

        JsonHelper::writeFile($filePath, $array);
        $this->assertSame('{"key":"val"}', file_get_contents($filePath));

        unlink($filePath);
        rmdir(pathinfo($filePath, PATHINFO_DIRNAME));
    }
}
