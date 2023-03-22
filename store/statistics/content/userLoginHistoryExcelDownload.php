<?php

use Api\Models\Log;
use Api\Services\UserService;
use Psr\Container\ContainerInterface;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

fn_checkAuthPermission($_SESSION);
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php');
try{
    function setCellCenterValue($objPHPExcel,$cellXY,$value){
        $sheet = $objPHPExcel->getActiveSheet();
        $objPHPExcel->getActiveSheet()->setCellValue($cellXY,$value);
        $sheet->getStyle($cellXY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        return $objPHPExcel;
    }
    $fileName = $_POST['file_name'];

    $fileName = $fileName.'_'.date('YmdHis');

    $param = [
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'is_internal' => $_POST['is_internal'],
        'search_value' => $_POST['search_value'],

    ];
    $query = Log::query();
    $query->leftJoin('bc_member as BM', 'bm.user_id' ,'=', 'bc_log.user_id');
    $query->where('bc_log.action','login');
    $query->whereBetween('bc_log.created_date',[$param['start_date'],$param['end_date']]);
    if($param['is_internal'] === 'external') {
        // 외부 사용자
        $query->leftJoin('dd_code_item as dci', 'dci.code_itm_code','=','bm.org_id');
        $query->where('dci.code_set_id',214);
        $query->where('dci.use_yn','Y');
        $query->where('bm.external_yn','Y');
        $query->select('dci.code_itm_nm as org_nm');
    } else {
        // 내부 사용자
        $query->where(function ($query) {
            $query->where('bm.external_yn','N')->orWhereNull('bm.external_yn');
        });
        $query->select('bm.org_id as org_nm');
    }

    if(isset($param['search_value']) && !empty($param['search_value'])) {
        $searchValue = $param['search_value'];
        $query->where(function ($query) use ($searchValue) {
            $query->where('bm.user_id','like',"%{$searchValue}%")
            ->orWhere('bm.user_nm','like',"%{$searchValue}%")
            ->orWhere('bm.dept_nm','like',"%{$searchValue}%");
        });
    }
    $query->select('bc_log.created_date as login_date','bc_log.description as login_ip','bm.user_id','bm.user_nm','bm.dept_nm');
    $query->orderByDESC('bc_log.created_date');

    $userLoginList = $query->get();
    
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->getActiveSheet();
    
    $index = 1;
    $idSeq = 'A';
    $idIndex = 1;
    $nameSeq = 'B';
    $nameIndex = 1;
    $orgNmSeq = 'C';
    $orgNmIndex = 1;
    $deptNmSeq = 'D';
    $deptNmIndex = 1;
    $loginDateSeq = 'E';
    $loginDateIndex = 1;
    $loginIpSeq = 'F';
    $loginIpIndex = 1;
    
    $sheet->getColumnDimension($idSeq)->setWidth(13);
    $sheet->getColumnDimension($nameSeq)->setWidth(13);
    $sheet->getColumnDimension($orgNmSeq)->setWidth(20);
    $sheet->getColumnDimension($deptNmSeq)->setWidth(20);
    $sheet->getColumnDimension($loginDateSeq)->setWidth(20);
    $sheet->getColumnDimension($loginIpSeq)->setWidth(20);
    
    $idXY = $idSeq.$idIndex;
    $nameXY = $nameSeq.$nameIndex;
    $orgNmXY = $orgNmSeq.$orgNmIndex;
    $deptNmXY = $deptNmSeq.$deptNmIndex;
    $loginDateXY = $loginDateSeq.$loginDateIndex;
    $loginIpXY = $loginIpSeq.$loginIpIndex;

    setCellCenterValue($objPHPExcel,$idXY,'아이디');
    $objPHPExcel->getActiveSheet()->getStyle($idXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($idXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    setCellCenterValue($objPHPExcel,$nameXY,'이름');
    $objPHPExcel->getActiveSheet()->getStyle($nameXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($nameXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    setCellCenterValue($objPHPExcel,$orgNmXY,'기관');
    $objPHPExcel->getActiveSheet()->getStyle($orgNmXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($orgNmXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    setCellCenterValue($objPHPExcel,$deptNmXY,'부서');
    $objPHPExcel->getActiveSheet()->getStyle($deptNmXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($deptNmXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    setCellCenterValue($objPHPExcel,$loginDateXY,'로그인일시');
    $objPHPExcel->getActiveSheet()->getStyle($loginDateXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($loginDateXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    setCellCenterValue($objPHPExcel,$loginIpXY,'접속IP');
    $objPHPExcel->getActiveSheet()->getStyle($loginIpXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
    $objPHPExcel->getActiveSheet()->getStyle($loginIpXY)->getFill()->getStartColor()->setRGB('D7D7D7');
    
    foreach($userLoginList as $key => $userLogin){
        $idIndex++;
        $nameIndex++;
        $orgNmIndex++;
        $deptNmIndex++;
        $loginDateIndex++;
        $loginIpIndex++;

        $idXY = $idSeq.$idIndex;
        $nameXY = $nameSeq.$nameIndex;
        $orgNmXY = $orgNmSeq.$orgNmIndex;
        $deptNmXY = $deptNmSeq.$deptNmIndex;
        $loginDateXY = $loginDateSeq.$loginDateIndex;
        $loginIpXY = $loginIpSeq.$loginIpIndex;

        setCellCenterValue($objPHPExcel,$idXY,$userLogin->user_id);
        setCellCenterValue($objPHPExcel,$nameXY,$userLogin->user_nm);
        setCellCenterValue($objPHPExcel,$orgNmXY,$userLogin->org_nm);
        setCellCenterValue($objPHPExcel,$deptNmXY,$userLogin->dept_nm);
        $date = new \Carbon\Carbon($userLogin->login_date);
        setCellCenterValue($objPHPExcel,$loginDateXY,$date->format('Y-m-d H:i:s'));
        $objPHPExcel->getActiveSheet()->getStyle($loginDateXY)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
        setCellCenterValue($objPHPExcel,$loginIpXY,$userLogin->login_ip);
    }
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
    $filepath = $fileName.'.xlsx';
    $full_path    = iconv('utf-8',  $filenameCharset, $filepath);
    header('Content-type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filepath . '"');
    $objWriter->save('php://output');
}catch(Exception $e){
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
        ));
}

