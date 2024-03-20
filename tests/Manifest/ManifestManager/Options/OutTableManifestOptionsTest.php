<?php

declare(strict_types=1);

namespace Keboola\Component\Tests\Manifest\ManifestManager\Options;

use Keboola\Component\Manifest\ManifestManager\Options\OptionsValidationException;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use PHPUnit\Framework\TestCase;

class OutTableManifestOptionsTest extends TestCase
{
    /**
     * @dataProvider provideOptions
     * @param mixed[] $expected
     */
    public function testToArray(array $expected, OutTableManifestOptions $options): void
    {
        $this->assertEquals($expected, $options->toArray());
    }

    /**
     * @return mixed[][]
     */
    public function provideOptions(): array
    {
        return [
            'some options' => [
                [
                    'delimiter' => '|',
                    'enclosure' => '_',

                ],
                (new OutTableManifestOptions())
                    ->setDelimiter('|')
                    ->setEnclosure('_'),
            ],
            'all options' => [
                [
                    'destination' => 'my.table',
                    'primary_key' => ['id'],
                    'delimiter' => '|',
                    'enclosure' => '_',
                    'columns' => [
                        'id',
                        'number',
                        'other_column',
                    ],
                    'incremental' => true,
                    'metadata' => [
                        [
                            'key' => 'an.arbitrary.key',
                            'value' => 'Some value',
                        ],
                        [
                            'key' => 'another.arbitrary.key',
                            'value' => 'A different value',
                        ],
                    ],
                    'column_metadata' => (object) [
                        '123456' => [
                            [
                                'key' => 'int.column.name',
                                'value' => 'Int column name',
                            ],
                        ],
                        'column1' => [
                            [
                                'key' => 'yet.another.key',
                                'value' => 'Some other value',
                            ],
                        ],
                    ],
                ],
                (new OutTableManifestOptions())
                    ->setEnclosure('_')
                    ->setDelimiter('|')
                    ->setColumnMetadata((object) [
                        '123456' => [
                            [
                                'value' => 'Int column name',
                                'key' => 'int.column.name',
                            ],
                        ],
                        'column1' => [
                            [
                                'value' => 'Some other value',
                                'key' => 'yet.another.key',
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
                            'value' => 'A different value',
                            'key' => 'another.arbitrary.key',
                        ],
                    ])
                    ->setPrimaryKeyColumns(['id']),
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidOptions
     */
    public function testInvalidOptions(string $expectedExceptionMessage, callable $callWithInvalidArguments): void
    {
        $this->expectException(OptionsValidationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $callWithInvalidArguments();
    }

    /**
     * @return mixed[][]
     */
    public function provideInvalidOptions(): array
    {
        return [
            'non-array metadata' => [
                'Metadata item #0 must be an array, found "string"',
                function (): void {
                    (new OutTableManifestOptions())->setMetadata([
                        'one',
                        'two',
                    ]);
                },
            ],
            'metadata with extra key' => [
                'Metadata item #0 must have only "key" and "value" keys',
                function (): void {
                    (new OutTableManifestOptions())->setMetadata([
                        [
                            'key' => 'my-key',
                            'value' => 'my-value',
                            'something' => 'my-value',
                        ],
                    ]);
                },
            ],
            'missing one of the metadata keys' => [
                'Metadata item #0 must have only "key" and "value" keys',
                function (): void {
                    (new OutTableManifestOptions())->setMetadata([
                        [
                            'key' => 'my-key',
                            'something' => 'my-value',
                        ],
                    ]);
                },
            ],
            'Column metadata is not array' => [
                'Each column metadata item must be an array',
                function (): void {
                    (new OutTableManifestOptions())->setColumnMetadata([
                        'x',
                    ]);
                },
            ],
            'Column name is not a string' => [
                'Each column metadata item must have string key',
                function (): void {
                    (new OutTableManifestOptions())->setColumnMetadata([
                        ['x'],
                    ]);
                },
            ],
            'Column metadata item is not an array' => [
                'Column "column1": Metadata item #0 must be an array, found "string"',
                function (): void {
                    (new OutTableManifestOptions())->setColumnMetadata([
                        'column1' => ['x'],
                    ]);
                },
            ],
            'Column metadata item is missing required keys' => [
                'Column "column1": Metadata item #0 must have only "key" and "value" keys',
                function (): void {
                    (new OutTableManifestOptions())->setColumnMetadata([
                        'column1' => [
                            ['some' => 'x'],
                        ],
                    ]);
                },
            ],
            'Column metadata item has extra keys' => [
                'Column "column1": Metadata item #1 must have only "key" and "value" keys',
                function (): void {
                    (new OutTableManifestOptions())->setColumnMetadata([
                        'column1' => [
                            [
                                'key' => 'x',
                                'value' => 'y',
                            ],
                            [
                                'key' => 'x',
                                'value' => 'y',
                                'string' => 'is not',
                            ],
                        ],
                    ]);
                },
            ],
        ];
    }
}
