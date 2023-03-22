
<?php
 $xml_file = "videojs/img/1920_1080_384_539471_288282.xml";
 $xml=simplexml_load_file($xml_file);

 $str = "";
 $i=0;
 if($xml->ScenesDetect)
 {
 	foreach($xml->ScenesDetect->position as $d)
 	 {
 	 	$file_nm = $d[filename];
		if($file_nm)
		{
			$file_nm = str_replace("X:\\Img\\sbs\\200008\\31\\539471\\", '', $file_nm);
		} 	
 	 	
 	 	$str[]= $i.":{src:'videojs/img/".$file_nm."',style:{width: '120px',left:'-60px'}}";
 	 	$i+=1;	
 	 }

 }
$str = implode(",",$str);
//print_r($str);
?>
<!doctype html>
<html>
<head>
  <title>Video.js Thumbnails Example</title>
 <link href="/videojs/video-js.css" rel="stylesheet">
<!--  <link href="http://vjs.zencdn.net/5.4.6/video-js.css" rel="stylesheet"> -->
 
 
 
  <style>
    p {
      background-color: #eee;
      border: thin solid #777;
      padding: 10px;
    }
  </style>
<!--  <script src="http://vjs.zencdn.net/5.4.6/video.js"></script> -->
  <script src="/videojs/video.js"></script>
  
 
 
</head>
<body>

<!-- 
<video id='video'
       class='video-js vjs-default-skin'           
       preload='auto'
       data-setup='{ 
			"techOrder": ["html5"],
			"playbackRates": [0.5, 1, 1.5, 2]
		}'
       controls width=640>
         <source src="http://127.0.0.1/data/bbb.mp4" type='video/mp4'>
</video>
-->

<video id='video_player'
       class='video-js vjs-default-skin'           
       controls width=640>
         <source src="http://127.0.0.1/data/bbb.mp4" type='video/mp4'>
</video>


<button type='button' onclick='moveBackward(10)'>10초뒤로</button>
<button type='button' onclick='moveBackward(1)'>1초뒤로</button>
<button type='button' onclick='moveForward(1)'>1초앞으로</button>
<button type='button' onclick='moveForward(10)'>10초앞으로</button>

<script>
// initialize video.js
// if(Hls.isSupported()) {
// 	 var video = document.getElementById('video');
// 	 var hls   = new Hls();
// 	//hls.loadSource('http://www.streambox.fr/playlists/test_001/stream.m3u8');
// 	 //hls.loadSource('http://geminisoft.iptime.org:1936/vod/mp4/2016/01/20/15/67488/Proxy/67488.mp4.m3u8');
// 	 //hls.loadSource('http://geminisoft.iptime.org:1936/vod/mp4:2016/01/20/15/67488/Proxy/67488.mp4/playlist.m3u8');
// 	 //hls.loadSource('http://playertest.longtailvideo.com/adaptive/wowzaid3/playlist.m3u8');
// 	 hls.loadSource('rtmp://192.168.1.201/vod/mp4:aaa.mp4/playlist.m3u8');
// 	 hls.attachMedia(video);
// }

// var video = videojs('video');
// console.log(document.getElementById("video")); 
//video.thumbnails({<?=$str?>});

var video = videojs('video_player', {
	"preload": "auto",
	"techOrder": ["html5"],
	"playbackRates": [0.5, 1, 1.5, 2]
});



// function getTime(sec) {
	
// 	var cur_time = video.currentTime();

// 	video.currentTime(cur_time - sec);

// 	//alert(cur_time);
// }

function moveBackward(sec) {
	var cur_time = video.currentTime();

	video.currentTime(cur_time - sec);
}

function moveForward(sec) {
	var cur_time = video.currentTime();

	video.currentTime(cur_time + sec);
}
// // and here's an example of the bare-minimum plugin configuration:
// /*
// video.thumbnails({
//   0: {
//     src: 'example-thumbnail.png'
//   }
// });
// */
</script>
</body>
</html>
