<?php

namespace Api\Core;

class View
{
    private $viewPath;
    // private $headerTitle;
    // private $headerName = '';
    private $bodyName;
    // private $footerName = '';
    private $data = [];

    public function __construct(){
        header('Content-Type: text/html; charset=utf-8');   
        $this->init();
    }

    private function init()
    {
        
        $this->viewPath = dirname(__DIR__).'/Views/';
    }

    public function render()
    {
        ob_start();
        // $this->header();
        $this->body();
        // $this->footer();
        return ob_get_clean();
    }


    // private function header()
    // {
    //     $this->require($this->headerName);
    //     // echo "  <!DOCTYPE html>
    //     //         <html lang=\"en\">
    //     //         <head>
    //     //         <meta charset=\"UTF-8\">
    //     //     ";
    //     // if(!is_null($this->headerTitle)){
    //     //     echo "<title>".$this->headerTitle."</title>";
    //     // }

    //     // echo "  </head>
    //     //         <body>
    //     //     ";
    // }

    // public function setHeader($header): void
    // {
    //     $this->headerName = $header;
    // }

    // public function setHeaderTitle($headerTitle){
    //     $this->headerTitle = $headerTitle;
    // }

    // private function footer()
    // {
    //     $this->require($this->footerName);
    //     // echo "
    //     // </body>
        
    //     // </html>";
    // }

    // public function setFooter($footer): void
    // {
    //     $this->footerName = $footer;
    // }

    private function body()
    {
        if(!is_null($this->bodyName)){            
            $this->require($this->bodyName);
        };
    }

    public function setBody($fileName)
    {
        $this->bodyName = $fileName;
    }

    private function getViewPath()
    {
        return $this->viewPath;
    }

    private function require($filePath)
    {
        $fullPath = $this->getViewPath().$filePath;
        if(!file_exists($fullPath)){
            // var_dump('not found file '.$fullPath);
            // exit();
            return;
        }

        require_once $fullPath;
    }

    public function setData($key,$data){
        $this->data[$key] = $data;
    }

    private function getData($key)
    {
        return $this->data[$key];
    }

    public function tag($tag,$value)
    {
        return "<".$tag.">".$value."</".$tag.">";
    }
}