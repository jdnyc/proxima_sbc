<?php

namespace Api\Core;

/**
 * 파일 경로 클래스
 * 
 */
class FilePath
{

    /**
     * 소스 원본경로
     *
     * @var [type]
     */
    public $sourcePath;

    public $islowerExt = true;

    /**
     * 정규화한 경로
     *
     * @var [type]
     */
    public $regularPath;

    public $separator ='/';
    public $separatorFile ='.';

    public $filePathArray = [];
    public $filenameArray = [];


    public $filePath = null;
    public $filenameExt = null;
    public $filename = null;
    public $fileExt = null;
 
    /**
     * 생성자
     *
     * @param string $sourcePath
     * @param string $agent
     */
    public function __construct($sourcePath, $separator = '/' , $isTrim = true)
    {       
        
        $this->sourcePath = $sourcePath;

        if( $separator ){
            $this->separator = $separator;
        }

        //경로 정규화
        $this->makeRegularPath($isTrim);

        //경로 쪼개기
        $this->makeFilePath();

    }
    public function getSourceFullPath(){
        return $this->sourcePath;
    }

    public function makeRegularPath($isTrim){
        
        // separator 통일
        $this->regularPath = str_replace('/', $this->separator, $this->sourcePath);
        $this->regularPath = str_replace('\\', $this->separator, $this->regularPath);

        if( $isTrim ){
            $this->regularPath = trim($this->regularPath, $this->separator );
        }
        return true;
    }

    /**
     * 경로 쪼개기
     *
     * @return void
     */
    public function makeFilePath(){
        //경로 배열
        $pathArray = explode($this->separator, $this->regularPath );

        $filenameExt = array_pop($pathArray);
        $this->filenameExt = $filenameExt;
        $this->filePathArray = $pathArray;

        $this->filePath = join($this->separator, $this->filePathArray);

        $filenameArray = explode($this->separatorFile, $this->filenameExt );
        if(count($filenameArray) > 1){
            $fileExt = array_pop($filenameArray);
        }else{
            $fileExt = '';
        }
      
        $this->filenameArray = $filenameArray;
        $this->filename = join($this->separatorFile, $this->filenameArray);
        if($this->islowerExt){
            $this->fileExt = strtolower($fileExt);
        }else{
            $this->fileExt = $fileExt;
        }

        return true;
    }
    
    /**
     * 원본 전체 경로
     *
     * @return void
     */
    public function getSrcFullPath(){
        return $this->sourcePath;
    }

    /**
     * 정규화한 전체 경로 파일명포함
     *
     * @return void
     */
    public function getFullPath(){
        return $this->regularPath;
    }

    /**
     * 파일명 제외한 파일경로
     *
     * @return void
     */
    public function getFilePath(){
        return $this->filePath;
    }

    /**
     * 확장자 포함 파일명
     *
     * @return void
     */
    public function getFilenameExt(){
        return $this->filenameExt;
    }

    /**
     * 확장자 제외 파일명
     *
     * @return void
     */
    public function getFilename(){
        return $this->filename;
    }

    /**
     * 확장자
     *
     * @return void
     */
    public function getFileExt(){
        return $this->fileExt;
    }
}
