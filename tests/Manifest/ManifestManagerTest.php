<?php

declare(strict_types=1);

namespace Keboola\Component\Tests\Manifest;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\Component\Manifest\ManifestManager\Options\OutFileManifestOptions;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;

class ManifestManagerTest extends TestCase
{
    /**
     * @dataProvider provideFilenameForGetManifestFilename
     */
    public function testGetManifestFilename(string $expected, string $filename): void
    {
        $manifestManager = new ManifestManager('/data');
        $this->assertSame(
            $expected,
            $manifestManager->getManifestFilename($filename)
        );
    }

    /**
     * @return string[][]
     */
    public function provideFilenameForGetManifestFilename(): array
    {
        return [
            'file with extension' => [
                '/some/file.csv.manifest',
                '/some/file.csv',
            ],
            'file that is already manifest' => [
                '/some/file.csv.manifest',
                '/some/file.csv.manifest',
            ],
            'file without extension' => [
                '/some/file.manifest',
                '/some/file',
            ],
        ];
    }

    public function testWillWriteFileManifest(): void
    {
        $temp = new Temp('testWillWriteFileManifest');
        $dataDir = $temp->getTmpFolder();
        $manager = new ManifestManager($dataDir);
        $fileName = 'file.jpg';

        $manager->writeFileManifest(
            $fileName,
            (new OutFileManifestOptions())
                ->setTags(['sometag'])
                ->setIsPublic(false)
                ->setIsPermanent(false)
                ->setNotify(true)
                ->setIsEncrypted(false)
        );

        $this->assertJsonFileEqualsJsonFile(
            __DIR__ . '/fixtures/expected-file.manifest',
            $dataDir . '/out/files/file.jpg.manifest'
        );
    }

    public function testWillLoadFileManifest(): void
    {
        $manager = new ManifestManager(__DIR__ . '/fixtures/manifest-data-dir');

        $expectedManifest = [
            'is_permanent' => false,
            'is_public' => false,
            'tags' => [
                'sometag',
            ],
            'notify' => true,
        ];
        $this->assertSame($expectedManifest, $manager->getFileManifest('people.csv'));
    }

    public function testWillLoadTableManifest(): void
    {
        $manager = new ManifestManager(__DIR__ . '/fixtures/manifest-data-dir');

        $expectedManifest = [
            'destination' => 'destination-table',
            'primary_key' => [
                'id',
                'number',
            ],
        ];
        $this->assertSame($expectedManifest, $manager->getTableManifest('people.csv'));
    }

    public function testNonexistentManifestReturnsEmptyArray(): void
    {
        $manager = new ManifestManager(__DIR__ . '/fixtures/manifest-data-dir');

        $this->assertSame([], $manager->getTableManifest('manifest-does-not-exist'));
    }

    public function testWillLoadTableManifestWithoutCsv(): void
    {
        $manager = new ManifestManager(__DIR__ . '/fixtures/manifest-data-dir');

        $expectedManifest = [
            'destination' => 'destination-table',
            'primary_key' => [
                'id',
                'number',
            ],
        ];
        $this->assertSame($expectedManifest, $manager->getTableManifest('products'));
    }

    /**
     * @return string[][]
     */
    public function provideTableNameForManifestReadWriteTest(): array
    {
        return [
            [
                'delimiter-and-enclosure',
            ],
            [
                'full-featured',
            ],
        ];
    }

    /**
     * @dataProvider provideWriteManifestOptions
     */
    public function testWriteTableManifest(string $expected, OutTableManifestOptions $options): void
    {
        $temp = new Temp('testWriteManifestFromOptions');
        $dataDir = $temp->getTmpFolder();
        $manifestManager = new ManifestManager($dataDir);

        $manifestManager->writeTableManifest(
            'my-table',
            $options
        );

        $this->assertJsonFileEqualsJsonFile(
            $expected,
            $dataDir . '/out/tables/my-table.manifest'
        );
    }

    /**
     * @return mixed[][]
     */
    public function provideWriteManifestOptions(): array
    {
        return [
            'writes only some' => [
                __DIR__ . '/fixtures/expectedManifestForWriteFromOptionsSomeOptions.manifest',
                (new OutTableManifestOptions())
                    ->setDelimiter('|')
                    ->setEnclosure('_'),
            ],
            'write all options' => [
                __DIR__ . '/fixtures/expectedManifestForWriteFromOptionsAllOptions.manifest',
                (new OutTableManifestOptions())
                    ->setEnclosure('_')
                    ->setDelimiter('|')
                    ->setColumnMetadata([
                        'column1' => [
                            [
                                'key' => 'yet.another.key',
                                'value' => 'Some other value',
                            ],
                        ],
                    ])
                    ->setColumns(['id', 'number', 'other_column'])
                    ->setDestination('my.table')
                    ->setIncremental(true)
                    ->setMetadata([
                        [
                            'key' => 'an.arbitrary.key',
                            'value' => 'Some value',
                        ],
                        [
                            'key' => 'another.arbitrary.key',
                            'value' => 'A different value',
                        ],
                    ])
                    ->setPrimaryKeyColumns(['id']),
            ],
        ];
    }
}
