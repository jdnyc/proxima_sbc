<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');

$_SERVER['DOCUMENT_ROOT'] = TEMP_ROOT;

require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/lib/functions.php');
try
{

	$GLOBALS['flag'] = '0';
		//한번에 불러오는 로우
	$limit = 2000;
	$created_time = date('YmdHis');

	$total_list = array();

	define('CREATED_TIME', $created_time);

	$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$created_time.'.log';
	$log_path_else = TEMP_ROOT.'/log/'.basename(__FILE__).'_else_'.$created_time.'.log';
	$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$created_time.'.log';
	$log_msg = '';


	$j = 1;
	$total = 20131019;
	$datas = array();

	for($start = 20130730 ; $start < $total ; $start = date( 'Ymd', strtotime($start)+ 86400  ) )
	{
		$list = get_fileList('XMLLog/'.$start.'.log');
		//print_r($list);

		foreach($list as $row)
		{
			$row = trim($row);
			$row =trim($row , '[ Line : 128 ]' );
			$nrow = strstr( $row,'<');

			if( strstr($nrow, 'content_id') ){

				$xml = simplexml_load_string(trim($nrow));
				$content_id = (string)$xml->result['content_id'];

				$data = $db->queryRow("select c.content_id,c.category_title,c.category_full_path,c.title,m.path from view_content c,bc_media m where c.content_id=m.content_id and m.media_type='original' and c.content_id='$content_id'");

				$carray = explode('/',$data[category_full_path] );
				if(count($carray) > 3 ){
					array_pop($carray);
					$tar_category = array_pop($carray);
					$new_cate = $db->queryOne("select category_title from bc_category where category_id='$tar_category'");
					$data[category_title] = $new_cate;
				}

				if( empty($data) ){
					@file_put_contents($log_path, $content_id."\n", FILE_APPEND);
				}else{

					array_push($datas, $data);
				}
			}

		}
	//	if($start == 20130802) exit;
	}

	echo createExcel('content_list', $datas);
}
catch(Exception $e)
{
	echo $e->getMessage().' '.$db->last_query;
}

function get_row_text($row)
{
	unset($text);
	foreach($row as $key => $val)
	{
		$text .= str_replace(',','', $val).',';
	}

	return $text;
}

function get_fileList($filename)
{
	$texts = @fopen($filename, "r");
	$list = array();
	$item='';
	if ($texts)
	{
		while (($buffer = fgets($texts, 4096)) !== false)
		{
			$item .= $buffer;
		}
		if (!feof($texts))
		{
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($texts);
	}

	if( !empty($item) )
	{
		$list = explode("\n", $item);
	}
	return $list;
}

?>