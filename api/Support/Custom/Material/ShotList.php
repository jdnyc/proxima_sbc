<?php
namespace Api\Support\Custom\Material;

use Slim\Http\Request;
use Api\Types\ManageType;


class ShotList{

    private $telecineTypeKey = 'telecine_type';



    private $requestParams = [
        ManageType::BROADMAS => ['id_no','tape_no','telecine_type'],
        ManageType::STAPEMED => ['tape_no','telecine_type'],
        ManageType::NEWSCONMET => ['tape_no','telecine_type'],
        ManageType::NEWSMAS => ['id_no','telecine_type'],
        ManageType::KTV_NEWSMAS => ['tape_no','id_no','telecine_type'],
        ManageType::MOVIEMAS => ['id_no','telecine_type'],
        ManageType::SFILMMED => ['tape_no','telecine_type'],
    ];

    private $params = [];
    
    public function __construct($data)
    {
      
        $this->data = $data;
    }

    public function isTelecineType()
    {

        if (array_key_exists($this->telecineTypeKey, $this->data)) {
            return true;
        }

        return false;
    }


    public function getManageType(){
        return $this->data[$this->telecineTypeKey];
    }

    public function isValid(){
        $valid = true;
        
        foreach($this->requestParams[$this->getManageType()] as $key => $value){
        
            if(array_key_exists($value,$this->data)){
         
                $this->setParams($value,$this->data[$value]);
            }else{
                $valid = false;
                
            }
        }

        return $valid;
    }

    public function setParams($key,$value){
        
        $this->params[$key] = $value;
  
    }

    public function getParams(){
        return $this->params;
    }


    public function saveShotList($contentId){
        $this->setParams('content_id', $contentId);
        if($this->isTelecineType()){
            if($this->isValid()){
            
                $materialService = new \Api\Services\MaterialService(app()->getContainer());
    
                $materialService->scenes($this->getParams());
            }
        }
     
    }
}