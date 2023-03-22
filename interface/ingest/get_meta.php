<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');
try
{
	$created = date('Ymd');

	libxml_use_internal_errors(true);

	$req_str = file_get_contents('php://input');
//	$req_str = '<request><load_metadata action="ingestlist" barcode="NA605320"/></request>';
	if (empty($req_str))
	{
		throw new Exception('요청값이 없습니다.');
	}

	//echo $req_str;

	$req_xml = simplexml_load_string($req_str);

	if (!$req_xml)
	{
		//$xml_parse_error = libxml_get_last_error();

		throw new Exception(libxml_get_last_error()->message);
	}

	$action		= $req_xml->load_metadata['action'];

	if( $action == 'tc_insert' )
	{
		$res = new SimpleXMLElement('<response><result /></response>');
		$res->result->addchild('tc_list');
		$res->result->addAttribute('success','true');
		$res->result->addAttribute('msg','');

		$barcode = $req_xml->load_metadata['barcode'];//바코드

		$ingest = $db->queryRow("select
								ingest_id, meta_table_id
							from
								ingest_metadata
							where
								UPPER(meta_value)='".strtoupper($barcode)."' order by ingest_id desc");

		if (empty($ingest['ingest_id']))
		{
			throw new Exception('등록되지 않은 바코드 입니다.', -1);
		}

		$content = $db->queryRow("select * from ingest where id='{$ingest['ingest_id']}' order by created_time desc");

		$tc_list = $req_xml->tc_list;

		foreach($tc_list as $tc)
		{
			$tc_in = $tc->tc['tc_in'];
			$tc_out = $tc->tc['tc_out'];
			$tc_id = getNextIngestSequence();

			$query ="
				insert into ingest_tc_list(INGEST_LIST_ID, ID, TC_IN, TC_OUT)
				values( '{$content['id']}',
					'$tc_id',
					'$tc_in',
					'$tc_out')";
			$insert = $db->exec($query);
			if(PEAR::isError($insert)) throw new Exception($insert->getMessage());

			$tc = $res->result->tc_list->addchild('tc');
			$tc->addAttribute('tc_id',$tc_id);
			$tc->addAttribute('tc_in',$tc_in);
			$tc->addAttribute('tc_out',$tc_out);
		}
	}
	else if( $action == 'tc_edit' )
	{
		$res = new SimpleXMLElement('<response><result /></response>');
		$res->result->addchild('tc_list');
		$res->result->addAttribute('success','true');
		$res->result->addAttribute('msg','');

		$barcode = $req_xml->load_metadata['barcode'];//바코드

		$ingest = $db->queryRow("select
								ingest_id, meta_table_id
							from
								ingest_metadata
							where
								UPPER(meta_value)='".strtoupper($barcode)."' order by ingest_id desc");
		if (empty($ingest))
		{
			throw new Exception('등록되지 않은 바코드 입니다.', -1);
		}
		$content = $db->queryRow("select * from ingest where id='{$ingest['ingest_id']}'");


		$tc_list = $req_xml->tc_list;

		foreach($tc_list as $tc)
		{
			$tc_in = $tc->tc['tc_in'];
			$tc_out = $tc->tc['tc_out'];
			$tc_id = $tc->tc['tc_id'];


			$query ="
				update ingest_tc_list set tc_in='$tc_in', tc_out='$tc_out'
				where id='$tc_id'";
			$update = $db->exec($query);
			if(PEAR::isError($update)) throw new Exception($update->getMessage());

			$tc = $res->result->tc_list->addchild('tc');
			$tc->addAttribute('tc_id',$tc_id);
			$tc->addAttribute('tc_in',$tc_in);
			$tc->addAttribute('tc_out',$tc_out);
		}
	}
	else if( $action == 'tc_del' )
	{
		$res = new SimpleXMLElement('<response><result /></response>');
		$res->result->addchild('tc_list');
		$res->result->addAttribute('success','true');
		$res->result->addAttribute('msg','');

		$barcode = $req_xml->load_metadata['barcode'];//바코드

		$ingest = $db->queryOne("select
								ingest_id, meta_table_id
							from
								ingest_metadata
							where
								UPPER(meta_value)='".strtoupper($barcode)."' order by ingest_id desc");
		if (empty($ingest['ingest_id']))
		{
			throw new Exception('등록되지 않은 바코드 입니다.', -1);
		}


		$content = $db->queryRow("select * from ingest where id='{$ingest['ingest_id']}'");


		$tc_list = $req_xml->tc_list;

		foreach($tc_list as $tc)
		{
			$tc_in = $tc->tc['tc_in'];
			$tc_out = $tc->tc['tc_out'];
			$tc_id = $tc->tc['tc_id'];

			$query ="delete ingest_tc_list where id='$tc_id'";
			$update = $db->exec($query);
			if(PEAR::isError($update)) throw new Exception($update->getMessage());

			$tc = $res->result->tc_list->addchild('tc');
			$tc->addAttribute('tc_id',$tc_id);
			$tc->addAttribute('tc_in',$tc_in);
			$tc->addAttribute('tc_out',$tc_out);
		}
	}
	else if( $action == 'ingestlist' )
	{
		$res = new SimpleXMLElement('<response><ingest_list /></response>');
		$barcode = $req_xml->load_metadata['barcode'];
		$member_id	= $req_xml->load_metadata['member_id'];

		$ingest = $db->queryRow("select
											ingest_id, meta_table_id
										from
											ingest_metadata
										where
											UPPER(meta_value)='".strtoupper($barcode)."' order by ingest_id desc");//바코드검색


		if (empty($ingest['ingest_id']))
		{
			throw new Exception('등록되지 않은 바코드 입니다.', -1);
		}

		//인제스트 리스트 데이터

		if($ingest['meta_table_id']==CLEAN) //소재영상일경우 대기상태의 것중 순번으로 정렬후 젤 위에것 가져오기
		{
			/*$content = $db->queryRow("
			select
				i.*
			from
				ingest i,
				(select i.id, im.meta_value from ingest i, ingest_metadata im, meta_field mf where i.id=im.ingest_id and im.meta_field_id=mf.meta_field_id and mf.name='Tape NO' ) t
			where
			i.id=t.id
			and t.meta_value='".strtoupper($barcode)."'
			and i.status='-3'
			order by t.meta_value asc
			");
			if (empty($content['id']))
			{
				throw new Exception('인제스트가 완료되었습니다.', -1);
			}*/
			//완료가 되었더라도 인제스트를 다시 받을수 있도록 수정함. //dohoon 0407 수정
			$content = $db->queryRow("select * from ingest where id='{$ingest['ingest_id']}'");

			if (empty($content))
			{
				throw new Exception('존재하지 않는 콘텐츠 입니다.', -1);
			}

		}
		else
		{
			$content = $db->queryRow("select * from ingest where id='{$ingest['ingest_id']}'");

			if (empty($content))
			{
				throw new Exception('존재하지 않는 콘텐츠 입니다.', -1);
			}
		}


		//메타데이터들의 값
		$metadata = $db->queryAll("select
										m.meta_field_id, f.name name, m.meta_value value
									from
										ingest_metadata m, meta_field f
									where
										m.ingest_id='{$content['id']}'
									and
										m.meta_field_id=f.meta_field_id
									order by
										f.sort");
		if (MDB2::isError($metadata)) throw new Exception($metadata->getMessage());

		$res_content = $res->ingest_list->addChild('content');
			$res_content->addAttribute('user_id', 			'admin');
			$res_content->addAttribute('content_id',		$content['content_id']);
			$res_content->addAttribute('content_type_id',	$content['content_type_id']);
			$res_content->addAttribute('meta_table_id',		$content['meta_table_id']);
		$res_content->addChild('title', convertSpecialChar($content['title']));

		$res_custom = $res->ingest_list->addChild('custom');
		foreach($metadata as $meta)
		{
			$res_ctrl = $res_custom->addChild('metactrl', convertSpecialChar($meta['value']));
				$res_ctrl->addAttribute('meta_field_id', $meta['meta_field_id']);
				$res_ctrl->addAttribute('name', $meta['name']);
		}
		//tc 리스트
		$tc_list = $db->queryAll("select * from ingest_tc_list where ingest_list_id=".$content['id']." order by id");
		if (MDB2::isError($tc_list)) throw new Exception($tc_list->getMessage());

		$res_tc_list = $res->ingest_list->addChild('tc_list');
		foreach ($tc_list as $tc)
		{
				$res_tc=$res_tc_list->addChild('tc');
				$res_tc->addAttribute('content_field_id','4164343');
				$res_tc->addAttribute('tc_in',	$tc['tc_in']);
				$res_tc->addAttribute('tc_out',	$tc['tc_out']);
				$res_tc->addAttribute('tc_id',	$tc['id']);
		}

		file_put_contents(LOG_PATH.'/xml/ingest/load_ingest_meta_'.$created.'.html', date("Y-m-d H:i:s\n").$res->asXML()."\n\n", FILE_APPEND);
	}
	else
	{
		$res = new SimpleXMLElement('<response><result /></response>');
		$res->result->addAttribute('success', 'false');
		$res->result->addAttribute('msg', '정의되지 않은 action 입니다.');
	}

	echo $res->asXML();
}
catch (Exception $e)
{
	$res = new SimpleXMLElement('<response><result /></response>');
	$res->result->addAttribute('success', 'false');
	$res->result->addAttribute('msg', $e->getMessage());

	echo $res->asXML();
}
?>