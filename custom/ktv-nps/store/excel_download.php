<?php 


require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

// require_once __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$col = $_POST['column_record'];   // column name
$row = $_POST['data_record'];   // row data

$col_data = json_decode($col, true);
$row_data = json_decode($row, true);
$colist = [];
$title = '컨텐츠 목록';
$column=[];  
$filenameCharset = 'euc-kr';
try{
if(!empty($col_data)){
    foreach($col_data as $key=>$k){

        $id = $k['id']; 
        $header = $k['header'];
        $new_array = array(
        $id => $header
        );
        array_push($colist, $new_array);
    }
}

if(!empty($col_data)){        //셀 넓이값
    foreach($col_data as $key=>$k){

        $header = $k['header']; 
        $width = $k['width'];
        $new_array = array(
        $header => $width
        );
        array_push($column, $new_array);
    }
}

$test_data = array_reduce($column, 'array_merge', array());
unset($test_data[""]);    // 엑셀출력시 필요없는 컬럼 삭제
unset($test_data["아이콘"]); // 엑셀출력시 필요없는 컬럼 삭제
unset($test_data["콘텐츠 상태"]); // 엑셀출력시 필요없는 컬럼 삭제
unset($test_data["썸네일"]); // 엑셀출력시 필요없는 컬럼 삭제

$new_col_data = array_reduce($colist, 'array_merge', array());
unset($new_col_data['numberer']);    // 엑셀출력시 필요없는 컬럼 삭제
unset($new_col_data['icons_grid']); // 엑셀출력시 필요없는 컬럼 삭제
unset($new_col_data['content_status_nm']); // 엑셀출력시 필요없는 컬럼 삭제
unset($new_col_data['thumb']); // 엑셀출력시 필요없는 컬럼 삭제

$i = 0;
$new_array = [];    // 인덱스 부여하기위한 배열
foreach($new_col_data as $key=>$value){

    $new_array[$key]=$i;
    $i++;  
    
}
// var_dump($new_col_data);

$spreadsheet = new Spreadsheet();
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle($title);
$rownum =1;
$colIdx =0;

foreach($test_data as $key => $val){
    

    if(!empty($test_data[$key])){
    $celname = chr(65 + $colIdx);
    $colIdx++;

    $sheet1->setCellValue($celname.$rownum, $key);
    $getStyle= $sheet1->getStyle($celname.$rownum);
    
    $getStyle->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10, 'bold' => true]);
    $getStyle->applyFromArray(['fill'=> ['fillType'=>Fill::FILL_SOLID],
                                'borders'=> ['bottom'=> ['borderStyle'=> Border::BORDER_THIN]]]);
    $getStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $getStyle->getALignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    $getStyle->getAlignment()->setWrapText(false);
    // $sheet1->getColumnDimension($celname)->setAutoSize(true);
    $sheet1->getColumnDimension($celname)->setWidth($val/7);
  
    }
}
$rownum++;

// row데이터중 필요한 col 데이터만 뽑아서 새로운 배열 생성
// foreach($row_data as $row ){
//     foreach($row as $key =>$val){
//         if($key=='created_date'){
//             $val = substr($val, 0, -9);
//             var_dump($val);
//         }
//     }
// }
// ;

$newRows = [];
foreach($row_data as $row)
{
    $newRow = [];

    foreach($row as $key => $val)
    {
        
        if( !empty($new_col_data[$key]) ){
      
       
            $newRow[ $new_array[$key] ] =  $val;
        
        }
    }
      $newRows [] = $newRow;     
}
    foreach($newRows as $rows){
        $colIdx = 0; 
        foreach($rows as $key=> $val)
        
        {
            $celname = chr(65 + $key);// 열 A,B,C,D
            // $colIdx++;  
            $sheet1->setCellValue($celname.$rownum, $val);                 
            $getStyle= $sheet1->getStyle($celname.$rownum);
            $getStyle->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10]);
            // $sheet1->getColumnDimension($celname)->setAutoSize(true);
        }
        $rownum++;
    }
    // $sheet1->calculateColumnWidths();

$writer = new Xlsx($spreadsheet);
$full_path = $title . '_' . date("Y-m-d") . '.xlsx';
if ($filenameCharset == 'euc-kr') {
    $full_path    = iconv('utf-8',  $filenameCharset, $full_path);
}


header('Content-type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $full_path . '"');
$writer->save('php://output');





} catch (Exception $e){
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
        ));
}



// $columnList = array(
//     "no" => "NO",
//     "word_se_nm" => "구분",
//     "word_nm" => "용어명",
//     "word_eng_nm" => "용어영문명",
//     "word_eng_abrv_nm" => "영문약어명",
//     "dc" => "정의",
//     "thema_relm" => "주제영역",
//     "regist_dt" => "등록일",
//     "word_st_code" => "상태"
// );
// var_dump($columnList);
// // var_dump($row);
// die();
// try{

//     $columnList = array(
//         "title"=>"제목",
//         "thumb"=>"썸네일",
//         "category_title"=>"카테고리",
//         "crated_date"=>"등록일자",
//         "reg_user_nm"=>"등록자",
//         "media_id"=>"미디어ID",
//         "progrm_nm"=>"프로그램명",
//         "tme_no"=>"회차번호",
//         "subtl"=>"부제",
//         "brdcst_stle_se"=>"방송형태구분",
//         "cn"=>"내용",
//         "kwrd"=>"키워드",
//         "matr_knd"=>"소재종류",
//         "brdcst_de"=>"방송일자",
//         "sys_video_rt"=>"재생길이",
//         "sys_video_codec"=>"비디오포맷",
//         "sys_video_bitrate"=>"비디오비트레이트",
//         "sys_display_size"=>"비디오해상도",
//         "sys_frame_rate"=>"비디오FPS",
//         "sys_audio_bitrate"=>"오디오비트레이트",
//         "sys_audio_channel"=>"오디오채널"
//     );
//     $spreadsheet = new Spreadsheet();
//     $sheet1 = $spreadsheet->getActiveSheet();
//     $sheet1->setTitle($title);
//     $rownum = 1;
    
//     $colIdx = 0;

//     foreach ($columnList as $key=>$val){
//         $celname = chr(65 + $colIdx);
//         $colIdx++;
//         $sheet1->setCellValue($celname.$rownum, $val);
//         $getStyle = $sheet1->getStyle($celname.$rownum);
//         $getStyle->getFont()->applyFromArray(['name'=>'맑은 고딕', 'size'=>10, 'bold'=> true]);
//         $getStyle->applyFromArray(
//             [
//                 'fill'=> [
//                     'fillType'=> Fill::FILL_SOLID
//                 ],
//                 'borders'=> [
//                     'bottom'=> ['borderStyle' => Border::BORDER_THIN],
//                     'right'=> ['borderStyle'=> BORDER::BORDER_THIN]
//                 ]
//             ]
//                 );
//             $getStyle->getAlignment()->setWrapText(false);
//             $cellWidth = 45;
//             $sheet1->getColumnDimension($celname)->setWidth($cellWidth);
//     }
//     $rownum++;

//         $writer = new Xlsx($spreadsheet);
//         $full_path = $title.'_'. date("Y-m-d"). '.xlsx';
//         header('Content-type: application/vnd.ms-excel');
//         header('Content-Disposition: attachment; filename="' . $full_path . '"');
//         $writer->save('php://output');


//         echo json_encode(array(
//             'success' => true,
//             'result'=> 'success'
//         ));
// } catch(Exception $e){
//     die(json_encode(array(
//         'success'=> false,
//         'result'=> 'failure',
//         'msg'=> $e->getMessage()
//     )));

// }

?>
