<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$free_space = disk_free_space(ROOT);
$total_space = disk_total_space(ROOT);
$use_space = $total_space-$free_space;

$contents_type = $mdb->queryAll("select content_type_id as id, name from content_type");

unset($name);
unset($count);
$k=0;
foreach($contents_type as $type){
		$total_size = $mdb->queryOne("select sum(m.filesize) from content c, media m where c.content_type_id={$type['id']} and c.content_id=m.content_id");

			if(empty($total_size)) 
			{
				$total_size = 0;
			}

			else{
			$name[] = $type['name'];
			$count[]= formatBytes($total_size);
			$k++;
			}
}

$data = array(
		'success' => true,
		'data' => array()
			);

for($i=0;$i<$k;$i++)
{
		array_push($data['data'], array(
											'name' => $name[$i],
											'count' => $count[$i]
		));
}

echo json_encode(
	$data
);
?>