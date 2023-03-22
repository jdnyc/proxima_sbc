<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$receive_xml = file_get_contents('php://input');

$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");
$xml = new SimpleXMLElement($receive_xml);

$action = trim($xml->Info['action']);

try
{

	$return = $response->addChild('Result');
	$return->addAttribute('success', 'true');
	$return->addAttribute('msg', 'ok');

	switch($action)
	{
		case 'get_list':			//XDS 브라우져 연동을 위해 새롭게 추가됨. 20130617 by leedohoon
		{
			//$content_id = trim($xml->Info['content_id']);
			$Item_Node = $xml->Info->Item;

			$lists_xml = $return->addChild('Lists');			//반환을 할 XML

			foreach($Item_Node as $Item)
			{
				$list_xml = $lists_xml->addChild('List');
				
				$list_xml->addAttribute( 'content_id', trim($Item['content_id']));

				$metadatas = $list_xml->addChild('Metas');

				$Category_Id = $db->queryOne("select category_id from VIEW_CONTENT where content_id='".$Item['content_id']."'");

				

				$list_xml->addAttribute( 'category_id', $Category_Id);

				$meta_list = $db->queryAll("select * from VIEW_USR_META where content_id='".$Item['content_id']."' order by show_order ");
				
				foreach($meta_list as $meta)
				{
					if ( $meta['usr_meta_field_type'] == 'container' ) continue;

					$metadata = $metadatas->addChild('Meta' , $meta['usr_meta_value']);
					$metadata->addAttribute( 'USR_META_FIELD_TITLE', $meta['usr_meta_field_title']);
					$metadata->addAttribute( 'USR_META_FIELD_TYPE', $meta['usr_meta_field_type']);
					//$metadata->addAttribute( 'USR_META_VALUE', $meta['usr_meta_value'] );
				}
			}
			
			break;
		}
		case 'list':
			$user_id = trim($xml->Info['user_id']);

			$category_id = trim($xml->Info['category_id']);

			$content_id = trim($xml->Info['content_id']);

			$_where = array();

			if( !empty($user_id ) ){
				array_push($_where, " f.user_id='$user_id' ");
				$add_where = " and f.user_id='$user_id' ";
			}

			if( !empty($category_id ) ){
				array_push($_where, " c.category_full_path like '/0/$category_id%' ");
				$add_where2 = " and c.category_full_path like '/0/$category_id%' ";
			}

			if( !empty($content_id ) ){
				array_push($_where, " c.content_id = '$content_id' ");
			}

			if( is_null($category_id ) && is_null($user_id ) && is_null($content_id ) ){
				throw new Exception('정보가 없습니다');
			}

			$lists = $db->queryAll("select c.*,f.filename,n.status task_status, n.nps_work_list_id from
			nps_work_list n,
			TAPELESS_FILE_LIST f ,
			view_content c
			where
			n.nps_work_list_id=f.nps_work_list_id and c.content_id=f.content_id ".' and '.join(' and ', $_where)."  order by n.created_date desc ");

			$lists_xml = $return->addChild('Lists');

			foreach($lists as $list)
			{
				$list_xml = $lists_xml->addChild('List');
				//콘텐츠 아이디
				$list_xml->addAttribute( 'content_id', $list['content_id']);
				$list_xml->addAttribute( 'title', $list['title']);

				//작업목록 아이디
				$list_xml->addAttribute( 'nps_work_list_id', $list['nps_work_list_id']);
				//파일명
				$list_xml->addAttribute( 'filename', $list['filename']);
				//작업상태
				$list_xml->addAttribute( 'task_status', $list['task_status']);

				$metadatas = $list_xml->addChild('Metas');

				$meta_list = $db->queryAll("select * from VIEW_USR_META where content_id='".$list['content_id']."' order by show_order ");

				foreach($meta_list as $meta)
				{
					if ( $meta['usr_meta_field_type'] == 'container' ) continue;

					$metadata = $metadatas->addChild('Meta' , $meta['usr_meta_value']);
					$metadata->addAttribute( 'USR_META_FIELD_TITLE', $meta['usr_meta_field_title']);
					$metadata->addAttribute( 'USR_META_FIELD_TYPE', $meta['usr_meta_field_type']);
					//$metadata->addAttribute( 'USR_META_VALUE', $meta['usr_meta_value'] );
				}
			}
		break;

		case 'category':

			$user_id = trim($xml->Info['user_id']);

			if( empty($user_id ) )
			{
				$category_info = $db->queryAll("select c.* from bc_category c where  c.parent_id=0 and c.category_id!='4801860'  order by c.category_title");
				//throw new Exception('사용자 정보가 없습니다');
			}else if( $user_id == 'admin' )
			{
				$category_info = $db->queryAll("select c.* from bc_category c where  c.parent_id=0 and c.category_id!='4801860'  order by c.category_title");

			}
			else
			{
				$user_id = strtoupper($user_id);

				$category_info = $db->queryAll("select c.* from user_mapping u , bc_category c where c.category_id=u.category_id and upper(user_id)='$user_id'  order by c.category_title  ");
			}

			foreach($category_info as $ca)
			{
				$category = $return->addChild('Category');
				$category->addAttribute('id', $ca['category_id']);
				$category->addAttribute('parentid', $ca['parent_id']);
				$category->addAttribute('title', $ca['category_title']);
				if(!$ca['no_children'])
				{
					$category->addAttribute('hasChild', 'true' );
				}
				else
				{
					$category->addAttribute('hasChild', 'false' );
				}
			}

		break;

		case 'getMeta':
			$content_id = trim($xml->Info['content_id']);

			$lists = $db->queryAll("select c.*,f.filename,n.status task_status, n.nps_work_list_id from
			nps_work_list n,
			TAPELESS_FILE_LIST f ,
			view_content c
			where
			n.nps_work_list_id=f.nps_work_list_id and c.content_id=f.content_id and c.content_id='$content_id' ");

			if(empty($lists))  throw new Exception('콘텐츠 정보가 없습니다.');

			$lists_xml = $return->addChild('Lists');

			foreach($lists as $list)
			{
				$list_xml = $lists_xml->addChild('List');
				//콘텐츠 아이디
				$list_xml->addAttribute( 'content_id', $list['content_id']);
				$list_xml->addAttribute( 'title', $list['title']);

				//작업목록 아이디
				$list_xml->addAttribute( 'nps_work_list_id', $list['nps_work_list_id']);
				//파일명
				$list_xml->addAttribute( 'filename', $list['filename']);
				//작업상태
				$list_xml->addAttribute( 'task_status', $list['task_status']);

				$metadatas = $list_xml->addChild('Metas');

				$meta_list = $db->queryAll("select * from VIEW_USR_META where content_id='".$list['content_id']."' order by show_order ");

				foreach($meta_list as $meta)
				{
					if ( $meta['usr_meta_field_type'] == 'container' ) continue;

					$metadata = $metadatas->addChild('Meta' , $meta['usr_meta_value']);
					$metadata->addAttribute( 'USR_META_FIELD_TITLE', $meta['usr_meta_field_title']);
					$metadata->addAttribute( 'USR_META_FIELD_TYPE', $meta['usr_meta_field_type']);
					//$metadata->addAttribute( 'USR_META_VALUE', $meta['usr_meta_value'] );
				}
			}




		break;

		default:
			 throw new Exception('정의되지 않은 action입니다.');
		break;

	}


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log', date('Y-m-d H:i:s').$response->asXML()."\n", FILE_APPEND);
	die($response->asXML());

}
catch (Exception $e)
{
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");
	$return = $response->addChild('Result');
		$return->addAttribute('success', 'false');
		$return->addAttribute('msg', $e->getMessage());

	die($response->asXML());
}


?>
