<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

$user_id = $_SESSION['user']['user_id'];
$user_name= $db->queryone("select USER_NM from bc_member where user_id ='$user_id'");
if(PEAR::isError($user_name)){
	echo "Msg:".$user_name->getMessage()."<br />";
	echo "Query:".$db->last_query;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<title>EBS NPS::마이페이지</title>
	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all-das.css" />


	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all.js"></script>

		<!--상세보기 - 스트리밍 선언 / 트리콤보 -->
	<script type="text/javascript" src="/flash/flowplayer/example/flowplayer-3.2.4.min.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.TreeCombo.js"></script>
	<script type="text/javascript" src="/ext/examples/ux/BufferView.js"></script>

	<script type="text/javascript" src="/javascript/functions.js"></script>
	<script type="text/javascript" src="/javascript/lang.php"></script>

	<script type="text/javascript" src="js/changePassword.js"></script>
	<script type="text/javascript" src="js/npstodas.js"></script>
	<script type="text/javascript" src="js/notice.js"></script>
	<script type="text/javascript" src="js/dastonps.js"></script>
	<script type="text/javascript" src="js/recent.js"></script>

<link rel="stylesheet" href="style_new.css" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.style3 {color: #FFFFFF; font-weight: bold; }
.style4 {color: #000000}

.wait{ background-color:#FFFFBB; }
.complete{ background-color:#8bd674; }
.refuse{ background-color:#f19898; }

	#images-view .x-panel-body{
		background: white;
		font: 11px Arial, Helvetica, sans-serif;
	}
	#images-view .thumb{
		background: #dddddd;
		padding: 3px;
	}
	#images-view .thumb img{
		height: 60px;
		width: 80px;
	}
	#images-view .thumb-wrap{
		float: left;
		margin: 4px;
		margin-right: 0;
		padding: 5px;
	}
	.comments{
		background-color: blue;
	}
	#images-view .thumb-wrap span{
		display: block;
		overflow: hidden;
		text-align: center;
	}

	#images-view .x-view-over{
		border:1px solid #dddddd;
		background: #efefef url(../../resources/images/default/grid/row-over.gif) repeat-x left top;
		padding: 4px;
	}

	#images-view .x-view-selected{
		background: #eff5fb url(images/selected.gif) no-repeat right bottom;
		border:1px solid #99bbe8;
		padding: 4px;
	}
	#images-view .x-view-selected .thumb{
		background:transparent;
	}

-->
</style>

<script type="text/javascript">
	function MM_preloadImages() { //v3.0
	  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}
	function MM_swapImgRestore() { //v3.0
	  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
	}
	function MM_findObj(n, d) { //v4.01
	  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
		d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
	  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
	  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
	  if(!x && d.getElementById) x=d.getElementById(n); return x;
	}

	function MM_swapImage() { //v3.0
	  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
	   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
	}


Ext.BLANK_IMAGE_URL = '/ext/resources/images/default/s.gif';

Ext.onReady(function(){
	Ext.override(Ext.PagingToolbar, {
		doLoad : function(start){
			var o = {}, pn = this.getParams();
			o[pn.start] = start;
			o[pn.limit] = this.pageSize;
			if(this.fireEvent('beforechange', this, o) !== false){
				var options = Ext.apply({}, this.store.lastOptions);
				options.params = Ext.applyIf(o, options.params);
				this.store.load(options);
			}
		}
	});

	Ext.QuickTips.init();

	var pagesize = 5;
	Ext.ns('Ext.ariel');
	Ext.ariel.detailLoadMask = new Ext.LoadMask(Ext.getBody(), {msg:"로딩중입니다..."});

	new Ext.TabPanel({
		renderTo: 'tab1',
		activeTab: 0,
		height: 230,
		items: [
			notice_grid,
			recent_contents_grid
		],
		listeners: {
			tabchange: function(self, p){
				switch(p.id){
					case 'notice_grid':
						Ext.getCmp('notice_grid').getStore().load({
							params: {
								start:0,
								limit: pagesize
							}
						});
					break;
					case 'recent_grid':
						Ext.getCmp('recent_grid').getStore().load({
							params: {
								start:0,
								limit: pagesize
							}
						});
					break;
				}
			}
		}
	});

	new Ext.TabPanel({
		renderTo: 'tab2',
		activeTab: 0,
		height: 230,
		items: [
			npstodas_grid,
			dastonps_grid
		],
		listeners: {
			tabchange: function(self, p){
				switch(p.id){
					case 'npstodas':
						Ext.getCmp('npstodas').getStore().load({
							params: {
								start:0,
								limit: pagesize
							}
						});
					break;
					case 'dastonps':
						Ext.getCmp('dastonps').getStore().load({
							params: {
								start:0,
								limit: pagesize
							}
						});
					break;
				}
			}
		}
	});
});
</script>

</head>

<body style="min-width:1003px">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td height="58" valign="top" background="my_images/top_bg1.jpg"><table width="1003" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="209"><img src="my_images/my_logo.jpg" width="209" height="58" /></td>
        <td width="146" align="left">&nbsp;</td>
        <td width="460" align="left" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="4" colspan="7"></td>
          </tr>
          <tr>
            <td align="center">
			<a href="notice.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image52','','my_images/big_icon1_over.jpg',1)"><img src="my_images/big_icon1.jpg" name="Image52" width="31" height="30" border="0" id="Image52" /></a></td>
            <td rowspan="2" align="center" valign="middle"><img src="my_images/big_icon_sun.jpg" width="3" height="20" /></td>
            <td align="center">
			<a href="recent.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image53','','my_images/big_icon2_over.jpg',1)"><img src="my_images/big_icon2.jpg" name="Image53" width="34" height="30" border="0" id="Image53" /></a></td>
            <td rowspan="2" align="center" valign="middle"><img src="my_images/big_icon_sun.jpg" width="3" height="20" /></td>
            <td align="center">
			<a href="npstodas.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image54','','my_images/big_icon3_over.jpg',1)"><img src="my_images/big_icon3.jpg" name="Image54" width="31" height="30" border="0" id="Image54" /></a></td>
            <td rowspan="2" align="center" valign="middle"><img src="my_images/big_icon_sun.jpg" width="3" height="20" /></td>
            <td align="center">
			<a href="dastonps.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('Image55','','my_images/big_icon4_over.jpg',1)"><img src="my_images/big_icon4.jpg" name="Image55" width="28" height="30" border="0" id="Image55" /></a></td>
          </tr>
          <tr>
            <td height="20" align="center"><span class="style3">공지사항</span></td>
            <td align="center"><span class="style3">최근등록작업</span></td>
            <td align="center"><span class="style3">NPS to DAS 전송</span></td>
            <td align="center"><span class="style3">DAS to NPS 전송</span></td>
          </tr>
        </table></td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td height="41" background="my_images/top_bg2.jpg"><table width="1003" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="12" colspan="4"></td>
      </tr>
      <tr>
        <td width="38">&nbsp;</td>
        <td width="25" align="left"><img src="my_images/ad_icon.jpg" width="19" height="22" /></td>
        <td width="171" align="left"><?=$user_name?>(<?=$user_id?>)님 환영합니다.</td>
        <td width="774" align="left"><span class="style4"><img src="my_images/pw_bt.jpg" width="77" height="22" style="cursor: pointer" onClick="buildFormChangePassword('<?=$user_id?>')"/></span></td>
      </tr>
       <tr>
        <td height="8" colspan="4"></td>
      </tr>
    </table></td>
  </tr>
</table>

<div id="tab1" style="margin-top: 35px; margin-left: 25px; margin-right: 25px"></div>
<div id="tab2" style="margin-top: 40px; margin-left: 25px; margin-right: 25px"></div>


</body>
</html>
