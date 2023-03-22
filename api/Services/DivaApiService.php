<?php

namespace Api\Services;

use Api\Modules\DivaClient;

class DivaApiService
{
    /**
     * DivaClient
     *
     * @var \Api\Modules\DivaClient
     */
    private $client;

    public function __construct()
    {
        $baseUrl = config('diva_api.base_url');
        $this->client = new DivaClient($baseUrl);
    }


    public function getAllTapes()
    {
        $tapes = $this->client->getAllTapes();
        return $tapes;
    }

    public function getObjectInfo($objectName, $collectionName = null){
        $object = $this->client->getObjectInfo($objectName, $collectionName);
        return $object;
    }

    public function deleteObject($objectName,$collectionName=null,$instance=0,$priority=50 ){
        $object = $this->client->deleteObject($objectName, null,-1,20 );

        // [
        //     "requestId" => 83898,
        //     "statusCode" => 1000,
        //     "statusName" => "DIVA_OK",
        //     "statusDescription" => "success",
        //   ]
        return $object;
    }

    public function archive($objectName, $filePathRoot, $components = [], $collectionName=null,$sourceServer=null, $instance=-1, $priority=50 , $qos=3 )
    {
        $object = $this->client->archive($objectName, $filePathRoot, $components, $collectionName, $sourceServer, $instance, $priority , $qos );
        return $object;
    }

    public function restore($objectName, $filePathRoot, $collectionName=null, $destinationServer=null, $instance=-1, $priority=50 , $qos=4 ){
        $object = $this->client->restore($objectName, $filePathRoot, $collectionName, $destinationServer, $instance, $priority , $qos );

        // [
        //     "requestId" => 83917,
        //     "statusCode" => 1000,
        //     "statusName" => "DIVA_OK",
        //     "statusDescription" => "success",
        //   ]
        return $object;
    }

    public function dateArrayToDateString($dateArray)
    {
        if(empty($dateArray)){
            return null;
        }
        return date("YmdHis", mktime($dateArray[3], $dateArray[4],$dateArray[5], $dateArray[1], $dateArray[2], $dateArray[0]));
    }
}
