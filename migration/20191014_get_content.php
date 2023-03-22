<?php
set_time_limit(3600);
use \Api\Models\ContentsMig;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;
use Api\Support\Helpers\MetadataMapper;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {

    dump(date("Y-m-d H:i:s"));
    
    //디버그 모드
    $isDebug = false;


    $migrationService = new \ProximaCustom\services\MigrationService();
        
    $contentService = new \Api\Services\ContentService($app->getContainer());
    $mediaService = new \Api\Services\MediaService($app->getContainer());
    $mediaSceneService = new \Api\Services\MediaSceneService($app->getContainer());
  
    dump(date("Y-m-d H:i:s").' - start');


    $mapper = new MetadataMapper($app->getContainer());


    // $mig = new \Api\Models\ContentsMig();
    // dd($mig);

    $total = 32624;
    //$total = 10;
    $limit = 100;
 
    for($start = 0; $limit < $total ; $start+=$limit ){
        request()->limit = $limit;
        request()->start = $start;
        request()->sort = 'content_id';
        request()->dir = 'asc';



        $contents = $contentService->getContentList([]);
        $contents = $mapper->contentsMapper($contents);
              
        foreach($contents as $content){
           // dump($content);

         
            $mig = new \Api\Models\ContentsMig();
            $mig->content = json_encode($content);
            $mig->save();
            
        }
       // exit;
    }

    dump(date("Y-m-d H:i:s").' - end');

}catch(Exception $e){
    echo $e->getMessage();
}
?>