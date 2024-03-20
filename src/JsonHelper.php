<?php

declare(strict_types=1);

namespace Keboola\Component;

use Keboola\Component\JsonHelper\JsonHelperException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class JsonHelper
{
    public static function decode(string $json): array
    {
        $jsonEncoder = new JsonEncoder();
        /** @var array $decodeJson */
        $decodeJson = $jsonEncoder->decode($json, JsonEncoder::FORMAT);
        return $decodeJson;
    }

    public static function encode(array $data, bool $formatted = false): string
    {
        $context = [];
        if ($formatted) {
            $context = ['json_encode_options' => JSON_PRETTY_PRINT];
        }

        $jsonEncoder = new JsonEncoder();
        return (string) $jsonEncoder->encode($data, JsonEncoder::FORMAT, $context);
    }

    public static function readFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException(null, 0, null, $filePath);
        }

        return self::decode((string) file_get_contents($filePath));
    }

    public static function writeFile(string $filePath, array $data, bool $formatted = false): void
    {
        $filePathDir = pathinfo($filePath, PATHINFO_DIRNAME);
        if (!is_dir($filePathDir)) {
            mkdir($filePathDir, 0777, true);
        }

        $result = @file_put_contents(
            $filePath,
            self::encode($data, $formatted)
        );

        if ($result === false) {
            throw new JsonHelperException(sprintf('Could not write to file "%s".', $filePath));
        }
    }
}
