<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\BaseComponent;
use Keboola\Component\Manifest\ManifestManager\Options\OutFileManifestOptions;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use MyComponent\Pap_Api_Session;
use MyComponent\Pap_Api_AffiliatesGrid;
use MyComponent\Pap_Api_TransactionsGrid;
use MyComponent\Gpf_Data_Filter;
use MyComponent\Gpf_Rpc_Array;
//use MyComponent\MyConfig;
//use MyComponent\MyComponentDefinition;

require 'PapApi.class.php';

class Component extends BaseComponent
{
    protected function run(): void
    {

        $this->getLogger()->info('Componet starting');
        
       // write manifest for output table
       $this->getManifestManager()->writeTableManifest(
            'data.csv',
            new OutTableManifestOptions()
        );

        $this->getLogger()->info("Written table manifest");        

        // Pap API code

        //----------------------------------------------
        // login (as merchant)

        $apiUrl = $this->getConfig()->getStringValue(['parameters', 'api_url']);
        $username = $this->getConfig()->getStringValue(['parameters', 'username']);
        $password = $this->getConfig()->getStringValue(['parameters', '#password']);
        
        $this->getLogger()->info("apiUrl: $apiUrl");
        $this->getLogger()->info("username: $username");
        $this->getLogger()->info("password: " . (($password === '' || $password === null) ? "Missing in config" : "Defined"));

        foreach (get_declared_classes() as $definedClass) {
            $this->getLogger()->info($definedClass);
        }


        $this->getLogger()->info("Opening Pap API session");
        
        $session = new Pap_Api_Session($apiUrl);
        if(!$session->login($username, $password)) {
            $this->getLogger()->error("Cannot login. Message: ".$session->getMessage());
            die("Cannot login. Message: ".$session->getMessage());
        }

        $this->getLogger()->info("Session opened");

        //----------------------------------------------
        // get recordset with list of affiliates
        $request = new Pap_Api_AffiliatesGrid($session);
        $request->setLimit(0, 30);

        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->getLogger()->error("API call error: ".$e->getMessage());
            die("API call error: ".$e->getMessage());
        }
        $this->getLogger()->info("Retrieved list of affiliates");

        $grid = $request->getGrid();

        //----------------------------------------------
        // get recordset of list of transactions
        $request = new Pap_Api_TransactionsGrid($session);

        $request->addFilter('dateinserted', Gpf_Data_Filter::DATERANGE_IS, Gpf_Data_Filter::RANGE_THIS_YEAR);
        $request->addParam('columns', new Gpf_Rpc_Array(array(array('id'),array('transid'),array('campaignid'), array('orderid'), array('commission'), array('original_currency_code'), array('dateinserted'),  array('userid'))));
        $request->setLimit(0, 100);
        $request->setSorting('orderid', false);

        try {
            $request->sendNow();
        } catch(Exception $e) {
            $this->getLogger()->error("API call error: ".$e->getMessage());
            die("API call error: ".$e->getMessage());
        }
        $this->getLogger()->info("Retrieved list of transactions");

        $grid = $request->getGrid();
        $recordset = $grid->getRecordset();

        // iterate through the records
        foreach($recordset as $rec) {
            $this->getLogger()->info('order_id: '.$rec->get('orderid').', commission: '.$rec->get('commission').', id'.$rec->get('id').', date_inserted: '.$rec->get('dateinserted').', s_timestamp: '. date('Y-m-d H:i:s').'<br>');
        }

        //The first grid request returns only a limited number of records, depends on setLimit() function. If you want to retrieve all records, see using the cycle in the code below:

        while ($recordset->getSize() == $request->getLimit()) {
            $request->sendNow();
            $recordset = $request->getGrid()->getRecordset();
            // iterate through the records
            foreach($recordset as $rec) {
                $this->getLogger()->info('Transaction OrderID: '.$rec->get('orderid'). ', Commission: '.$rec->get('commission').'<br>');
            }
        }

        $outputPath = $this->getDataDir() . '/out/tables/data.csv';

        $this->getLogger()->info("Going to write ouput to $outputPath");

        // Otevřít soubor pro zápis
        $file = fopen($outputPath, 'w') or die("Unable to open output file $outputPath for writing!");

        // Hlavičky CSV souboru
        fputcsv($file, array('order_id', 'commission', 'id', 'date_inserted', 's_timestamp'));

        // Iterate through the records and write them to CSV
        foreach ($recordset as $rec) {
            $data = array(
                $rec->get('orderid'),
                $rec->get('commission'),
                $rec->get('id'),
                $rec->get('dateinserted'),
                date('Y-m-d H:i:s')
            );

            // Write data to CSV
            fputcsv($file, $data);

            // Výpis obsahu každého řádku
            $this->getLogger()->info('Zapsán řádek do CSV: ' . implode(',', $data) . '<br>');
        }

        // Zavřít soubor
        fclose($file);

        $this->getLogger()->info("Component finished");


        //$fp = fopen($outputPath, 'w') or die("Unable to open output file $outputPath for writing!");
        //fwrite($fp, "id,name\n1,joe");
        //fclose($fp);
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
