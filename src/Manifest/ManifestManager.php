<?php

declare(strict_types=1);

namespace Keboola\Component\Manifest;

use InvalidArgumentException;
use Keboola\Component\JsonHelper;
use Keboola\Component\Manifest\ManifestManager\Options\OutFileManifestOptions;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Symfony\Component\Filesystem\Filesystem;
use function pathinfo;
use const PATHINFO_EXTENSION;

/**
 * Handles everything related to generating and reading manifests for tables and files.
 */
class ManifestManager
{
    private string $dataDir;

    public function __construct(
        string $dataDir
    ) {
        $this->dataDir = $dataDir;
    }

    final public function getManifestFilename(string $fileName): string
    {
        $isAlreadyManifestFilename = pathinfo($fileName, PATHINFO_EXTENSION) === 'manifest';
        if ($isAlreadyManifestFilename) {
            return $fileName;
        }
        return $fileName . '.manifest';
    }

    public function writeFileManifest(
        string $fileName,
        OutFileManifestOptions $options
    ): void {
        $tableManifestName = $this->getManifestFilename($fileName);
        $this->internalWriteFileManifest($tableManifestName, $options->toArray());
    }

    public function writeTableManifest(string $fileName, OutTableManifestOptions $options): void
    {
        $manifestName = self::getManifestFilename($fileName);

        $this->internalWriteTableManifest($manifestName, $options->toArray());
    }

    /**
     * @return mixed[]
     */
    public function getFileManifest(string $fileName): array
    {
        $baseDir = implode('/', [$this->dataDir, 'in', 'files']);
        return $this->loadManifest($fileName, $baseDir);
    }

    /**
     * @return mixed
     */
    public function getTableManifest(string $tableName)
    {
        $baseDir = implode('/', [$this->dataDir, 'in', 'tables']);

        return $this->loadManifest($tableName, $baseDir);
    }

    /**
     * @return mixed[]
     */
    private function loadManifest(string $fileName, string $baseDir): array
    {
        $isPathInDirectory = strpos($fileName, $baseDir) === 0;
        $fs = new Filesystem();
        if (!$isPathInDirectory) {
            if ($fs->isAbsolutePath($fileName)) {
                throw new InvalidArgumentException(sprintf(
                    'Manifest source "%s" must be in the data directory (%s)!',
                    $fileName,
                    $baseDir
                ));
            }

            $fileName = implode('/', [$baseDir, $fileName]);
        }

        $manifestFilename = $this->getManifestFilename($fileName);
        if (!$fs->exists($manifestFilename)) {
            return [];
        }

        return JsonHelper::readFile($manifestFilename);
    }

    /**
     * @param mixed[] $manifestContents
     */
    private function internalWriteManifest(string $manifestAbsolutePath, array $manifestContents): void
    {
        JsonHelper::writeFile($manifestAbsolutePath, $manifestContents);
    }

    /**
     * @param mixed[] $manifestContents
     */
    private function internalWriteTableManifest(string $tableManifestName, array $manifestContents): void
    {
        $this->internalWriteManifest($this->dataDir . '/out/tables/' . $tableManifestName, $manifestContents);
    }

    /**
     * @param mixed[] $manifestContents
     */
    private function internalWriteFileManifest(string $fileManifestName, array $manifestContents): void
    {
        $this->internalWriteManifest($this->dataDir . '/out/files/' . $fileManifestName, $manifestContents);
    }
}
