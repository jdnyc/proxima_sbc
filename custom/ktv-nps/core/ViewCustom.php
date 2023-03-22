<?php

namespace ProximaCustom\core;

use \Proxima\core\View;

class ViewCustom
{    
    public static function render($viewName, $variables = [])
    {
        $ds = DIRECTORY_SEPARATOR;
        $customViewsDir = dirname(__DIR__) . $ds . 'views';        
        $view = new View($customViewsDir);        
        $view->render($viewName);
    }

    public static function renderScript($scriptName)
    {
        $scriptName = str_replace('.js', '', $scriptName);
        $partialJsPath = dirname(__DIR__) . '/javascript/partial/' . $scriptName .'.js';
        if(!file_exists($partialJsPath))
        {
            return;
        }
        ob_start();
        include $partialJsPath; 
        echo ob_get_clean();
    }

    public static function getScriptData($scriptPath, $variables = [])
    {
        $path = dirname(__DIR__) . '/' . $scriptPath;
        if(!file_exists($path))
        {
            return false;
        }
        ob_start();
        include $path; 
        return  ob_get_clean();
    }
}