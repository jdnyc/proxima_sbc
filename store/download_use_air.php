<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$server_ip = convertIP( $_SERVER['REMOTE_ADDR']  );
$server_ip = $server_ip;//.':'.$_SERVER['SERVER_PORT'];
if( $_POST['flag'] == 'audio' )
{
	$flag = $_POST['flag'];
	$is_all = $_POST['is_all'];
	$media_type = $_POST['media_type'];

	$content_id_list = $_POST['content_id_list'];
	$user_id = $_SESSION['user']['user_id'];

	if($is_all == 'true')
	{
		if( is_null($user_id) )
		{
			exit;
		}

//		if( in_array( $val['ud_content_id'], $CG_LIST ) )//CG용
//		{
//		if( $val['f4777959'] == 'O' || $val['f4777967'] == 'O' ||$val['f4777976'] == 'O' ||$val['f4777984'] == 'O' )
//		{
//		}
//		}
//
//		( select * from bc_usr_meta where )

		$media_id_array =  $db->queryAll("select m.media_id
		from bc_favorite f,
		bc_content c,
		bc_media m
		where f.content_id=m.content_id and c.content_id=f.content_id and f.user_id='$user_id' and m.media_type='$media_type' and  ( m.status is null or  m.status = '0' ) and c.is_deleted = 'N'  ");
		$temp = array();

		foreach( $media_id_array as $media )
		{

			$temp [] = $media['media_id'];
		}
		$media_id_list = join(',', $temp);
	}
	else
	{
		if( json_decode($content_id_list , true) )
		{
			$content_id_list = json_decode($content_id_list , true);

			$content_ids = join(',', $content_id_list);

			$media_id_array =  $db->queryAll("select media_id from bc_media where media_type='$media_type' and ( status is null or  status = '0' ) and content_id in ( ".$content_ids." ) ");
			$temp = array();

			foreach( $media_id_array as $media )
			{
				$temp [] = $media['media_id'];
			}

			$media_id_list = join(',', $temp);
		}
	}


}
else if( $_POST['media_type'] )
{
	$media_type = $_POST['media_type'];

	$content_id_list = $_POST['content_id_list'];

	if( json_decode($content_id_list , true) )
	{
		$content_id_list = json_decode($content_id_list , true);

		$content_ids = join(',', $content_id_list);

		$media_id_array =  $db->queryAll("select media_id from bc_media where media_type='$media_type' and ( status is null or  status = '0' ) and content_id in ( ".$content_ids." ) ");
		$temp = array();

		foreach( $media_id_array as $media )
		{
			$temp [] = $media['media_id'];
		}


		$media_id_list = join(',', $temp);
	}
}
else
{
	$media_id_list = $_POST['media_id_list'];
}

?>
<html>
	<head>
		<title>GeminiBadge_Downloader</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css" media="screen">
		html, body { height:100%; background-color: #ffffff;}
		body { margin:0; padding:0; overflow:hidden; }
		#flashContent { width:100%; height:100%; }
		</style>
	</head>
	<body>
		<div id="flashContent">
			<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="75" height="22" id="GeminiBadge_Downloader" align="middle" >
				<param name="movie" value="/air_app/GeminiBadge_Downloader.swf" />
				<param name="quality" value="high" />
				<param name="bgcolor" value="#ffffff" />
				<param name="play" value="true" />
				<param name="loop" value="true" />
				<param name="wmode" value="window" />
				<param name="scale" value="showall" />
				<param name="menu" value="true" />
				<param name="devicefont" value="false" />
				<param name="salign" value="" />
				<param name="allowScriptAccess" value="sameDomain" />
				<param name="FlashVars" value="appurl=http://<?=$server_ip?>/air_app/ArielDownloader.air&server_url=<?=$server_ip?>&media_ids=<?=$media_id_list?>&app_ver=1.2.4" />
				<!--[if !IE]>-->
				<object type="application/x-shockwave-flash" data="/air_app/GeminiBadge_Downloader.swf" width="75" height="22" >
					<param name="movie" value="/air_app/GeminiBadge_Downloader.swf" />
					<param name="quality" value="high" />
					<param name="bgcolor" value="#ffffff" />
					<param name="play" value="true" />
					<param name="loop" value="true" />
					<param name="wmode" value="window" />
					<param name="scale" value="showall" />
					<param name="menu" value="true" />
					<param name="devicefont" value="false" />
					<param name="salign" value="" />
					<param name="allowScriptAccess" value="sameDomain" />
					<param name="FlashVars" value="appurl=http://<?=$server_ip?>/air_app/ArielDownloader.air&server_url=<?=$server_ip?>&media_ids=<?=$media_id_list?>&app_ver=1.2.4" />
				<!--<![endif]-->
					<a href="http://www.adobe.com/go/getflash">
						<img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" />
					</a>
				<!--[if !IE]>-->
				</object>
				<!--<![endif]-->
			</object>
		</div>
	</body>
</html>
