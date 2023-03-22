<?php
// php 
require dirname(__DIR__) . '/vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test</title>

    <link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="/css/proxima3.css" />
    <link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">    

    <!--Custom Style loading-->
    <?php
    //Custom Style loading        
    if (class_exists('\ProximaCustom\core\CssManager')) {
        $styles = \ProximaCustom\core\CssManager::getCustomStyles();
        foreach ($styles as $style) {
            echo $style;
        }
    }
    ?>
</head>

<body>
    <div id="root">        
        <h1 class="text-center">UI Design</h1>
        <div>
            <h2>Test</h2>
            <div id="test"></div>
        </div>
    </div>    

    <script src='/javascript/lang.js'></script>
    <script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="/lib/extjs/ext-all.js"></script>
    <script type="text/javascript" src="/javascript/component/button/Ariel.IconButton.js"></script>
    <script type="text/javascript" src="/javascript/ext.ux/upload/Ext.ux.form.FileUploadField.js"></script>
    <script type="text/javascript" src="/javascript/common.js"></script>
    <script type="text/javascript" src="/custom/ktv-nps/javascript/common.js"></script>

    <!--개발 스크립트 로드-->
    <script src="/custom/ktv-nps/javascript/ext.ux/Custom.SnsPublishWindow.js"></script>

    <script type="text/javascript" src="/javascript/lang.php"></script>
    <script type="text/javascript" src="/js/jquery.min.js"></script>

    <script>
        Ext.onReady(function() {
            
            var contentId = 31;
            var postId = 5;
            var snsPublishWin = new Custom.SnsPublishWindow({
                postId: postId,
                contentId: contentId
            });
            snsPublishWin.show();
        });
    </script>
</body>

</html>