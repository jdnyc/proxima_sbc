<?php
session_start();
//require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/class/fpdf/korean.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/class/fpdf/fpdf.php');


$newData = $_REQUEST['data'];
//$data = DOMElement::getAttribute($data);
//print_r($newData);
//exit;
$array = json_decode($newData, true);
//print_r($array);


//��ü����
$pdf=new PDF_Korean('P', 'mm','A4');
//$pdf_1=new PDF_Korean_f();
$pdf->AddUHCFont();
//��Ʈ �߰� ������ �⺻�� ��Ʈ�� �߰��� ������.

//PDF ���� ����
$pdf->Open();
//�������� �þ�� �ڵ����� 2page ����
$pdf->SetAutoPageBreak(true,15);// 
//�������߰�
$pdf->AddPage();
$pdf->AddUHCFont('����');
$pdf->AddUHCFont('����', 'HYGoThic-Medium-Acro');
$pdf->AddUHCFont('����', 'Dotum');
$pdf->AddUHCFont('����', 'Batang');
$pdf->AddUHCFont('�ü�', 'Gungsuh');
//$pdf->AddUHCFont('����', 'Gulim');
$pdf->AddUHCFont('�Ѱܷ���ü', '�Ѱܷ���ü');
$pdf->AddUHCFont('���±۲�', '���±۲�'); 

//��Ʈ ����
$pdf->SetFont('����','',8);

//�۾��� ù��° �Ķ���� line-height  �ι�° �Ķ���� ���� ����
//$receivedData = new PDF_Korean_f;
//$rowArrayData = array();
//$rowArrayData = $receivedData->getData($newData);
//$pdf->Text(10,10,iconv('UTF-8','EUC-KR','�׽�Ʈ1'));
$pdf->Ln();
//$pdf->Text(10,40,iconv('UTF-8','EUC-KR', $rowArrayData));
//$indexArrayData = count($array);

$rowIndex = 40;
foreach($array as $detailArrays){
	$colIndex = 10;
	foreach($detailArrays as $key => $value){	
		
		$pdf->Text($colIndex,$indexPosition, iconv('UTF-8', 'EUC-KR', $value));
		$colIndex = $colIndex + 35;
		//echo $value."&nbsp&nbsp&nbsp&nbsp&nbsp";	
		//$pdf->Ln();		
	}
	$indexPosition = $indexPosition + 10;
	//echo "<br/>";
	$pdf->Ln();
}
/*$pdf->Ln();
while($element = each($array)){
	echo $element['key'];
	echo "-";
	echo $element['value'];
	echo "<br/>";
}*/
//$pdf->Write(10,$rowArrayData);
//���� ����
$pdf->Ln();

//Į�� ����
//$header=array('Country','Capital','Area (sq km)','Pop. (thousands)');
//$data=$pdf_1->LoadData('countries.txt');
//PDF ���� ����
//$pdf_1->Open();

//�������߰�

//$header = $pdf_1->getHeader( $array );

//$data = $pdf_1->getData( $array );


//$pdf_1->SetFont('Arial','',14);
//$pdf_1->AddPage();
//$pdf_1->BasicTable($header,$data);
//$pdf_1->AddPage();
//$pdf_1->ImprovedTable($header,$data);
//$pdf_1->AddPage();
//$pdf_1->FancyTable($header,$data);


//$pdf->MBMultiCell(600,500,$array, $border=0, $align='L', $fill=false);


//print_r($pdf_1);
//print_r($pdf);
//exit;
//���
$pdf->Output();
$pdf_close($pdf);
?>

