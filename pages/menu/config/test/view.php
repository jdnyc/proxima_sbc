<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />
    
 

    
    <link rel="stylesheet" type="text/css" href="feed-viewer.css" />
	    
    <script type="text/javascript" src="ext/adapter/ext/ext-base.js"></script>


    
    <script type="text/javascript" src="save-state.php"></script>
    <script type="text/javascript" src="get-state.php"></script>
    <script type="text/javascript" src="SessionProvider.js"></script>
    <script type="text/javascript" src="TabCloseMenu.js"></script>
    <script type="text/javascript" src="FeedViewer.js"></script>
    <script type="text/javascript" src="FeedWindow.js"></script>

    <script type="text/javascript" src="FeedGrid.js"></script>
    <script type="text/javascript" src="MainPanel.js"></script>
    <script type="text/javascript" src="FeedPanel.js"></script>

</head>
<body>
<div id="header"><div style="float:right;margin:5px;" class="x-small-editor"></div></div>

<!-- Template used for Feed Items -->
<div id="preview-tpl" style="display:none;">
    <div class="post-data">

        <span class="post-date">{pubDate:date("M j, Y, g:i a")}</span>
        <h3 class="post-title">{title}</h3>
        <h4 class="post-author">by {author:defaultValue("Unknown")}</h4>
    </div>
    <div class="post-body">{content:this.getBody}</div>
</div>

</body>

</html>