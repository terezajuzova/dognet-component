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

        // write manifest for output table
        $this->getManifestManager()->writeTableManifest(
            'data.csv',
            (new OutTableManifestOptions())
                ->setPrimaryKeyColumns(['id'])
                ->setDestination('out.my-dognet-data-source.data')
        );
    }

    protected function customSyncAction(): array
    {
        $this->getLogger()->info('******* custom sync action');
        $data = [
            ['id' => 1, 'name' => 'joe'],
            ['id' => 2, 'name' => 'marry'],
            ['id' => 3, 'name' => 'peter']
        ];

        return ['result' => 'success', 'data' => $data];
    }

    /** @return array<string,string> */
    protected function getSyncActions(): array
    {
        $this->getLogger()->info('******* get sync actions');
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
