<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

try
{
	//$strQuery = 'select * from categories';

	//$strQuery = "select c.id, c.title, p.path from categories c,  path_mapping p where p.category_id=c.id";

	$strQuery = "
	select
		c.id category_id,
		c.title,
		p.path ,		
		count(u.user_id) cnt
	from
		categories c,
		path_mapping p,
		user_mapping u
	where
		p.category_id=c.id	
	and u.category_id(+)=c.id
	group by c.id, c.title, p.path order by c.title";

	$counts = $db->queryAll("select  um.category_id,  m.breake,  count(um.user_id) count from  user_mapping um, member m where um.user_id=m.user_id group by um.category_id, m.breake");

	$codecounts = $db->queryAll("select * from  CATEGORY_PROGCD_MAPPING cpm ");

	$DBValue = $db->queryAll($strQuery);
	if(PEAR::isError($DBValue)) throw new Exception($DBValue->getMessage(), ERROR_QUERY);

	foreach($DBValue as $key => $v)
	{
		foreach($counts as $value)
		{
			if ( $v['category_id'] == $value['category_id'] )
			{
				if(($value['breake']) == 'C')
				{//재직일때
					$DBValue[$key]['cn'] = $value['count'];
				}
				else
				{//재직이아닐때
					$DBValue[$key]['tn'] = $DBValue[$key]['tn'] +  $value['count'];
				}

			}
		}

		foreach($codecounts as $codecount)
		{
			if ($v['category_id'] == $codecount['category_id'])
			{			
				$DBValue[$key]['prognm'] .= '['.$codecount['prognm'].'] ';
			}
		}
	}

	echo json_encode(array(
		'success' => true,
		'total' => $total,
		'data' => $DBValue
	));
}
catch(Exception $e)
{
	$msg = $e->getMessage();

	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $msg.'( '.$db->last_query.' )';
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}
?>