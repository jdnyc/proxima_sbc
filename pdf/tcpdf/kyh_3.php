<?php
session_start();
//require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/class/fpdf/korean_1.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/class/fpdf/fpdf.php');


$newData = $_REQUEST['data'];
$array = json_decode($newData, true);

class PDF extends FPDF
{
	//데이타를 읽는다
	
	function LoadData($file)
	{
		//파일을 줄단위로 읽어온다
		$lines=file($file);
		$data=array();
		foreach($lines as $line)
		$data[]=explode(';',chop($line));
		


		return $data;
	}

	//간단한 테이블
	function BasicTable($header,$data)
	{
		//헤더
		foreach($header as $col)
			$this->Cell(40,7,$col,1);
		$this->Ln();
		//데이터
		foreach($data as $row)
		{
			foreach($row as $col)
				$this->Cell(40,6,$col,1);
			$this->Ln();
		}
	}

	//더 나은 모양
	function ImprovedTable($header,$data)
	{
		//칼럼 너비
		$w=array(40,15,70,25);
		//헤더
		for($i=0;$i < count($header); $i++)
			$this->Cell($w[$i],7,$header[$i],1,0,'C');
		$this->Ln();
		//데이터
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

	//색상이 들어간 테이블
	function FancyTable($header,$data)
	{
		//색상, 라인너비, 굵은 글씨 설정
		$this->SetFillColor(255,0,0);
		$this->SetTextColor(255);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');

		//헤더
		$w=array(40,35,40,45);
		for($i=0;$i < count($header); $i++)
		{
			$this->Cell($w[$i],7,$header[$i],1,0,'C',1);
		}
		$this->Ln();

		//색상과 글꼴 설정을 원래대로
		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont('');

		//데이터
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

//$pdf=new PDF();

//칼럼 제목
$header=array('Country','Capital','Area (sq km)','Pop. (thousands)',"aaa");

$sizeofaaa = count($array);
echo $sizeofaaa;
$rowindex=1;
foreach($array as $detailArrays){
	foreach($detailArrays as $key => $value){
		$abc="/";
		$result=$value.$abc;
			$resultset=$result;
			$kyh[$rowindex]=iconv('UTF-8', 'EUC-KR', $result);
			$rowindex=$rowindex+1;
			//$arr=explode("/",$resultset);
			//echo $resultset;
		//$pdf->Text(10,10,iconv('UTF-8', 'EUC-KR', $result));
		//echo $value."&nbsp&nbsp&nbsp&nbsp&nbsp";	
		//$pdf->Ln();		
	}
	//echo $arr[0];
//echo $arr[1];
	
}

// 1차원 배열을 2차원 배열로 만들기

// 배열크기 출력


$loop_product_list = array();
$row_idx = 0;
$col_idx = 0;
$item_count = 0;
foreach($kyh as $item)
{
 $loop_product_list[$row_idx][$col_idx] = $item;
 $item_count++; // $item을 한개씩 복사할때마다 증가처리
 $col_idx++; // 0~1의 값을 가진다.
 
 if ($item_count % 5 == 0)
 {
  $row_idx++; // 5개씩 복사될때마다 증가된다.
  $col_idx = 0;
 }
}
echo 'Test :'.$loop_product_list[0][0];
// 출력한다.
$i = 0;
while($row = $loop_product_list[$i++])
{
 foreach($row as $col)
 {
 // echo $col . '&nbsp;&nbsp;&nbsp;&nbsp;';
 }
// echo "<br/>";
}
//$kor = '한글';
//$kor = iconv('UTF-8', 'EUC-KR', $kor);
/*$loop_product_list = array(
	array(
	$kor, '1'
	),
	array(
	$kor, '1'
	)
);
*/
//$loop_product_list=iconv('UTF-8', 'EUC-KR', $loop_product_list);
//for($i=0;$i<count($data);$i++){
//	echo $data[$i].$value;
//}

//echo $resultset[0][1];

//데이터 읽기
//$data=$pdf->LoadData($result);


$pdf->SetFont('Arial','',10);
$pdf->AddPage();
$pdf->BasicTable($header,$loop_product_list);
$pdf->AddPage();
$pdf->ImprovedTable($header,$loop_product_list);
$pdf->AddPage();
$pdf->FancyTable($header,$loop_product_list);
$pdf->Output();
?>

