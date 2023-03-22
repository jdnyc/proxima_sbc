<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {
	if ($_POST['status'] == 'all') {
		$from_que = $mdb->queryAll("select q.*, c.title from queue q, content c where q.status != 'deleted' and q.content_id = c.content_id order by id desc");

		$array = array();
		foreach($from_que as $q){
			$jarray = "{";
			$jarray .= '"id":"'.$q['id'].'","asset_type":"'.$q['asset_type'].'","title":"'.$q['title'].'","file":"'.$q['file'].'","creation_datetime":"'.$q['creation_datetime'].'","content_id": "'.$q['content_id'].'",';

			$datas = $mdb->queryAll("select * from content_value where (content_field_id = 507 or content_field_id = 615 or content_field_id = 58172) and content_id = $q[content_id] order by content_id desc");

			foreach($datas as $data){
				//echo $data['content_field_id']."<br />";
				if($data['content_field_id'] == '507'){					
					$jarray .= '"duration":"'.$data['value'].'",';
				}
				elseif($data['content_field_id'] == '615'){
					$jarray .= '"resolution":"'.$data['value'].'",';
				}
				elseif($data['content_field_id']  == '58172'){
					$jarray .= '"vcodec":"'.$data['value'].'"';
				}
			}
			$jarray .= "}";
			array_push($array, $jarray);
		}
		$jarray .= "]";
		//print_r($array);
		$get_count = count($array);
		$json_meta .= implode(', ', $array);

		echo '{"success":true,"total":'.$get_count.',"data":['.$json_meta.']}';

	} else {
		//$data = $mdb->queryAll("select * from queue where status = '" . $_POST['status'] . "' order by id desc");
		$from_que = $mdb->queryAll("select q.*, c.title from queue q, content c where q.status = '" .$_POST['status']. "' and q.content_id = c.content_id order by id desc");

		$array = array();
		foreach($from_que as $q){
			$jarray = "{";
			$jarray .= '"id":"'.$q['id'].'","asset_type":"'.$q['asset_type'].'","title":"'.$q['title'].'","file":"'.$q['file'].'","creation_datetime":"'.$q['creation_datetime'].'","content_id": "'.$q['content_id'].'",';

			$datas = $mdb->queryAll("select * from content_value where (content_field_id = 507 or content_field_id = 615 or content_field_id = 58172) and content_id = $q[content_id] order by content_id desc");

			foreach($datas as $data){
				//echo $data['content_field_id']."<br />";
				if($data['content_field_id'] == '507'){					
					$jarray .= '"duration":"'.$data['value'].'",';
				}
				elseif($data['content_field_id'] == '615'){
					$jarray .= '"resolution":"'.$data['value'].'",';
				}
				elseif($data['content_field_id']  == '58172'){
					$jarray .= '"vcodec":"'.$data['value'].'"';
				}
			}
			$jarray .= "}";
			array_push($array, $jarray);
		}
		$jarray .= "]";
		//print_r($array);
		$get_count = count($array);
		$json_meta .= implode(', ', $array);

		echo '{"success":true,"total":'.$get_count.',"data":['.$json_meta.']}';
	}
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}



?>
