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

        $dataDir = getenv('KBC_DATADIR') === false ? '/data/' : (string) getenv('KBC_DATADIR');
        $outputPath = $dataDir . '/out/tables/' . 'data.csv';

        $this->getLogger()->info('******* Going to write ouput to: ' . $outputPath);

        $fp = fopen($outputPath, 'w') or die("Unable to open file!");
        fwrite($fp, "id;name\n1;joe");
        fclose($fp);

        $this->getLogger()->info('******* Component finished');
    }

    ///** @return array<string,string> */
   /* protected function getSyncActions(): array
    {
        $this->getLogger()->info('******* get sync actions');
        return ['custom' => 'customSyncAction'];
    }*/
    protected function getConfigClass(): string
    {
        return MyConfig::class;
    }
    protected function getConfigDefinitionClass(): string
    {
        return MyConfigDefinition::class;
    }
}
