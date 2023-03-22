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

class PDF extends FPDF
{
	//����Ÿ�� �д´�
	
	function LoadData($file)
	{
		//������ �ٴ����� �о�´�
		$lines=file($file);
		$data=array();
		foreach($lines as $line)
			$data[]=explode(';',chop($line));
		return $data;
	}

	//������ ���̺�
	function BasicTable($header,$data)
	{
		//���
		foreach($header as $col)
			$this->Cell(40,7,$col,1);
		$this->Ln();
		//������
		foreach($data as $row)
		{
			foreach($row as $col)
				$this->Cell(40,6,$col,1);
			$this->Ln();
		}
	}

	//�� ���� ���
	function ImprovedTable($header,$data)
	{
		//Į�� �ʺ�
		$w=array(40,15,70,25);
		//���
		for($i=0;$i < count($header); $i++)
			$this->Cell($w[$i],7,$header[$i],1,0,'C');
		$this->Ln();
		//������
		foreach($data as $row)
		{
			$this->Cell($w[0],6,$row[0],'LR');
			$this->Cell($w[1],6,$row[1],'LR');
			$this->Cell($w[2],6,number_format($row[2]),'LR',0,'R');
			$this->Cell($w[3],6,number_format($row[3]),'LR',0,'R');
			$this->Ln();
		}
		//Closure line
		$this->Cell(array_sum($w),0,'','T');
	}

	//������ �� ���̺�
	function FancyTable($header,$data)
	{
		//����, ���γʺ�, ���� �۾� ����
		$this->SetFillColor(255,0,0);
		$this->SetTextColor(255);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');

		//���
		$w=array(40,35,40,45);
		for($i=0;$i < count($header); $i++)
		{
			$this->Cell($w[$i],7,$header[$i],1,0,'C',1);
		}
		$this->Ln();

		//����� �۲� ������ �������
		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont('');

		//������
		$fill=0;
		foreach($data as $row)
		{
			$this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
			$this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
			$this->Cell($w[2],6,number_format($row[2]),'LR',0,'R',$fill);
			$this->Cell($w[3],6,number_format($row[3]),'LR',0,'R',$fill);
			$this->Ln();
			$fill=!$fill;
		}
		$this->Cell(array_sum($w),0,'','T');
	}
}

$pdf=new PDF();
$pdf->Open();
$pdf->AddPage();
//Į�� ����
$header=array('Country','Capital','Area (sq km)','Pop. (thousands)',"aaa");
$resultset=array(array());

$sizeofaaa = count($array);
echo $sizeofaaa;

//$resultset=array(array());
$rowIndex = 0;
foreach($array as $detailArrays){
	$colIndex = 0;
	foreach($detailArrays as $key => $value){
		//$abc=";";
		//$result=$value.$abc;
		//	$resultset=$result;
		//	echo "$resultset";
		//$pdf->Text(10,10,iconv('UTF-8', 'EUC-KR', $result));
		//echo $value."&nbsp&nbsp&nbsp&nbsp&nbsp";	
		//$pdf->Ln();
		$resultset[$rowIndex][$colIndex] = $value;
		$pdf->$resultset[$rowIndex][$colIndex];
		$colIndex  = $colIndex + 1;
	}
	$rowIndex = $rowIndex + 1;
	echo "<br/>";

}
/*
for($i = 0; $i < $sizeofaaa; $i++){
//foreach($array as $detailArrays){
	
    for($j = 0; $j < 5; $j++){
		
		//$resultset[$i][$j] = $array[$i].$value;
		echo $array[$i].$value[$j];
		//	$resultset=$result;
		//	echo "$resultset";
		//$pdf->Text(10,10,iconv('UTF-8', 'EUC-KR', $result));
		//echo $value."&nbsp&nbsp&nbsp&nbsp&nbsp";	
		//$pdf->Ln();		
	}
	echo "<br/>";

}*/

echo $resultset[0][1];

//������ �б�
//$data=$pdf->LoadData($result);
//$pdf->SetFont('Arial','',14);
//$pdf->AddPage();
//$pdf->BasicTable($header,$result);
//$pdf->AddPage();
//$pdf->ImprovedTable($header,$data);
//$pdf->AddPage();
//$pdf->FancyTable($header,$data);
$pdf->Output();
?>

