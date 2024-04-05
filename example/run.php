<?php

declare(strict_types=1);

use Keboola\Component\Logger;
use Keboola\Component\UserException;
use MyComponent\Component;

use MyComponent\Gpf;
use MyComponent\Gpf_Object;
use MyComponent\Gpf_Rpc_Serializable;
use MyComponent\Gpf_Rpc_DataEncoder;
use MyComponent\Gpf_Rpc_DataDecoder;
use MyComponent\Gpf_Rpc_Array;
use MyComponent\Gpf_Rpc_Server;
use MyComponent\Gpf_Rpc_MultiRequest;
use MyComponent\Gpf_Rpc_Params;
use MyComponent\Gpf_Exception;
use MyComponent\Gpf_Data_RecordSetNoRowException;
use MyComponent\Gpf_Rpc_ExecutionException;
use MyComponent\Gpf_Rpc_Object;
use MyComponent\Gpf_Rpc_Request;
use MyComponent\Gpf_HttpResponse;
use MyComponent\Gpf_Http;
use MyComponent\Gpf_Templates_HasAttributes;
use MyComponent\Gpf_Data_RecordHeader;
use MyComponent\Gpf_Data_Row;
use MyComponent\Gpf_Data_Record;
use MyComponent\Gpf_Data_Grid;
use MyComponent\Gpf_Data_Filter;
use MyComponent\Gpf_Rpc_GridRequest;
use MyComponent\Gpf_Data_RecordSet;
use MyComponent\Gpf_Data_RecordSet_Sorter;
use MyComponent\Gpf_Data_IndexedRecordSet;
use MyComponent\Gpf_Net_Http_Request;
use MyComponent\Gpf_Net_Http_ClientBase;
use MyComponent\Gpf_Net_Http_Response;
use MyComponent\Gpf_Rpc_Form;
use MyComponent\Gpf_Rpc_Form_Validator_FormValidatorCollection;
use MyComponent\Gpf_Rpc_FormRequest;
use MyComponent\Gpf_Rpc_RecordSetRequest;
use MyComponent\Gpf_Rpc_DataRequest;
use MyComponent\Gpf_Rpc_Data;
use MyComponent\Gpf_Rpc_FilterCollection;
use MyComponent\Gpf_Rpc_PhpErrorHandler;
use MyComponent\Gpf_Php;
use MyComponent\Gpf_Rpc_ActionRequest;
use MyComponent\Gpf_Rpc_Action;
use MyComponent\Gpf_Rpc_Map;
use MyComponent\Gpf_Log;
use MyComponent\Gpf_Log_Logger;
use MyComponent\Gpf_Api_IncompatibleVersionException;
use MyComponent\Gpf_Api_Session;
use MyComponent\Gpf_Rpc_Json;
use MyComponent\Gpf_Rpc_Json_Error;
use MyComponent\Gpf_Rpc_JsonObject;
use MyComponent\Pap_Api_Object;
use MyComponent\Pap_Api_AffiliatesGrid;
use MyComponent\Pap_Api_AffiliatesGridSimple;
use MyComponent\Pap_Api_BannersGrid;
use MyComponent\Pap_Api_Affiliate;
use MyComponent\Pap_Api_AffiliateSignup;
use MyComponent\Pap_Api_TransactionsGrid;
use MyComponent\Pap_Api_Transaction;
use MyComponent\Pap_Tracking_Action_RequestActionObject;
use MyComponent\Pap_Tracking_Request;
use MyComponent\Pap_Api_Tracker;
use MyComponent\Pap_Api_SaleTracker;
use MyComponent\Pap_Api_ClickTracker;
use MyComponent\Pap_Api_RecurringCommission;
use MyComponent\Pap_Api_RecurringCommissionsGrid;
use MyComponent\Pap_Api_PayoutsGrid;
use MyComponent\Pap_Api_PayoutsHistoryGrid;
use MyComponent\Pap_Api_Session;
use MyComponent\Gpf_Net_Http_Client;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $app = new Component($logger);
    $app->execute();
    exit(0);
} catch (UserException $e) {
    $logger->info("UserException in run.php: ". $e->getMessage());
    $logger->error($e->getMessage());
    exit(1);
} catch (Throwable $e) {
    $logger->info("Throwable in run.php: ". $e->getMessage());

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
} finally {
    $logger->info("Finnaly block in run.php");
}
