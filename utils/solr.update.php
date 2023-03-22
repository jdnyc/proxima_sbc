<?php
require_once('/oradata/web/nps/lib/config.php');
require_once('/oradata/web/nps/lib/functions.php');
require_once('/oradata/web/nps/searchengine/solr/searcher.class.php');


$s = new Searcher($db);

$limit = 1000;
$total = $db->queryOne("select count(*) from bc_content where is_deleted='N' and status > 0 ");

$current_count = 1;

$loop_count = ceil($total/$limit);
for ($start=0; $start<$total; $start+=$limit)
{
	//$offset = $i*$limit;

	$db->setLimit($limit, $start);
	$content_list = $db->queryAll("select content_id as id from bc_content where is_deleted ='N' and status > 0 order by content_id asc");

	foreach ($content_list as $content)
	{
		echo "\r".$current_count++.'/'.$total."\t\t";

		$s->add($content['id'], 'NPS');
	}
}
?>