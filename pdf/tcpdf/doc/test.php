<?php
session_start();
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pdf/tcpdf/config/lang/eng.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pdf/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {//Page header
	public function Header(){
		$this->SetFont('kyh', 'B', 20);
		$this->Cell(0, 15, $this->header_title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
	}
}
try
{
	$type = $_REQUEST['type'];
	$content_id = $_REQUEST['content_id'];

	if( empty( $type ) || empty($content_id) ) throw new Exception("Param error");

	switch($type)
	{
		case 'preview':
			$header = array(
				'starttc' => array(
					'name' => 'Start TC',
					'width' => 70
				),
				'endtc' => array(
					'name' => 'End TC',
					'width' => 70
				),
				'note' => array(
					'name' => '내용',
					'width' => 425
				),
				'user_nm' =>  array(
					'name' => '등록자',
					'width' => 70
				)
			);

			$ud_content_id = $db->queryOne("select ud_content_id from bc_content where content_id='$content_id'");

			$_select = array();
			$_from = array();
			$_where = array();
			$_order = array();
			array_push($_select , " c.* " );
			array_push($_from , " view_bc_content c " );
			array_push($_where , " c.content_id='$content_id' " );
			$renQuery = MetaDataClass::createMetaQuery('usr' , $ud_content_id , array(
				'select' => $_select,
				'from' => $_from,
				'where' => $_where,
				'order' => $_order
			) );
			$_select = $renQuery[select];
			$_from = $renQuery[from];
			$_where = $renQuery[where];
			$select = " select ".join(' , ', $_select);
			$from = " from ".join(' , ', $_from);
			if (!empty($_where)){
				$where = " where ".join(' and ', $_where);
			}
			$content = $db->queryRow($select.$from.$where);

			$title = $content[title];
			$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, $title, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
			$pdf->SetFont('kyh', '', 10);
			$pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->setLanguageArray($l);
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
			$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			$pdf->AddPage();

			$title_datas = array(
				array(
					'프로그램명' => $content['usr_program'],
					'회차' => $content['usr_turn']
				)
			);

			$query = "select starttc,endtc,(select user_nm from bc_member where user_id =l.list_user_id) user_nm,note  from ROUGHCUT_PREVIEW_LIST l where content_id='$content_id' and type='$type' order by starttc,list_id " ;
			$datas = $db->queryAll($query);

			$title_html = CreateHTML( $header, $title_datas );
			$pdf->writeHTML($title_html, true, false, true, false, '');

			$html = CreateHTML( $header, $datas );
			$pdf->writeHTML($html, true, false, true, false, '');
			$pdf->Output();
			$pdf->Close();
		break;

		default:
			 throw new Exception("Type error");
		break;
	}

}
catch(Exception $e)
{
	echo $e->getMessage();
}

function CreateHTML($header, $datas)
{
	$dom = new DomDocument();
	$root = $dom->createElement('div');
	$root->setAttribute('style', 'text-align:center');
	$dom->appendChild($root);

	$table_tag = $dom->createElement('TABLE');
	$table_tag->setAttribute('border', '1');
	$table_tag->setAttribute('align', 'center');
	$table_tag->setAttribute('width', '635');

	foreach($datas as $row => $data)
	{
		if($row == 0){
			$tr0_tag = $dom->createElement('TR');
			$tr0_node = $table_tag->appendChild($tr0_tag);
		}
		$trrow_tag = $dom->createElement('TR');
		$trrow_node = $table_tag->appendChild($trrow_tag);
		foreach($data as $key => $val )
		{
			if( $row == 0 ){
				if(empty($header[$key])){
					$td_tag = $dom->createElement('TD', $key );
				}else{
					$td_tag = $dom->createElement('TD', $header[$key]['name'] );
					if(!empty($header[$key]['width'])){
						$td_tag->setAttribute('width', $header[$key]['width'] );
					}
				}
				$td_tag->setAttribute('height', 20);
				$td_tag->setAttribute('align', 'center');
				$td_tag->setAttribute('bgcolor', '#cccccc');
				$td_tag->setAttribute('style', 'text-align:center;');
				$tr0_node->appendChild($td_tag);

			}
			$tdrow_tag = $dom->createElement('TD', $val);
			$tdrow_tag->setAttribute('style', 'text-align:center;');
			$trrow_node->appendChild($tdrow_tag);
		}
	}
	$dom->appendChild($table_tag);
	$str = $dom->saveXML();
	return $str;
}
?>