<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\BaseComponent;
use Keboola\Component\Manifest\ManifestManager\Options\OutFileManifestOptions;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use MyComponent\MyConfig;
use MyComponent\MyComponentDefinition;

class Component extends BaseComponent
{
    protected function run(): void
    {
        // get parameters
        $parameters = $this->getConfig()->getParameters();

        $this->getLogger()->info('******* component starts');
        $this->getLogger()->info('*******' . $this->getConfig()->getStringValue(['parameters', 'api_url']));
        $this->getLogger()->info('******* after logging url');

        // get manifest for input file
        $fileManifest = $this->getManifestManager()->getFileManifest('input-file.csv');

        // get manifest for input table
        $tableManifest = $this->getManifestManager()->getTableManifest('in.tableName');

        // write manifest for output file
        $this->getManifestManager()->writeFileManifest(
            'out-file.csv',
            (new OutFileManifestOptions())
                ->setTags(['tag1', 'tag2'])
        );

        // write manifest for output table
        $this->getManifestManager()->writeTableManifest(
            'data.csv',
            (new OutTableManifestOptions())
                ->setPrimaryKeyColumns(['id'])
                ->setDestination('out.report')
        );
    }

    protected function customSyncAction(): array
    {
        return ['result' => 'success', 'data' => ['joe', 'marry']];
    }

    /** @return array<string,string> */
    protected function getSyncActions(): array
    {
        return ['custom' => 'customSyncAction'];
    }
    protected function getConfigClass(): string
    {
        return MyConfig::class;
    }
    protected function getConfigDefinitionClass(): string
    {
        return MyConfigDefinition::class;
    }
}
