<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

fn_checkAuthPermission($_SESSION);
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php');
try{
    $statisticRecords = json_decode($_POST['statistic_records']);
    $statisticTotal = json_decode($_POST['statistic_total']);
    $statisticsType = $_POST['statistics_type'];
    $fileName = $_POST['file_name'];

    $fileName = $fileName.'_'.date('YmdHis');

    $statisticsDynamicColumn = json_decode($_POST['statistics_dynamic_column']);

    /**
     * 가운데 정렬 셀 값 넣기
     * @return PHPExcel
     */
    function setCellCenterValue($objPHPExcel,$cellXY,$value){
        $sheet = $objPHPExcel->getActiveSheet();
        $objPHPExcel->getActiveSheet()->setCellValue($cellXY,$value);
        $sheet->getStyle($cellXY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        return $objPHPExcel;
    }
    /**
     * 셀 배경 색 변경
     * @return PHPExcel
     */
    function backgroundCellColor($objPHPExcel,$cellXY,$color){
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->getStyle($cellXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
        $sheet->getStyle($cellXY)->getFill()->getStartColor()->setRGB($color);
        return $objPHPExcel;
    }



    /**
     * 일별 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function dailyExcelFile($statisticRecords,$statisticTotal){
        // 순서 or 콘텐츠 목록
        $udContentIds = ["1","7","9","3","2","0"];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 3;
        $rowColumnY = 'A';

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->created_date;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);
       

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $columnSeq2 = 'B';
            $totalSeq = 'B';
            foreach($udContentIds as $udContentId){
                // header 컬럼 부분
                $data = $statisticRecord->$udContentId;
                $columnXY = $columnSeq.'1';
                
                $columnMergeXY = (++$columnSeq).'1';
                $columnMergeCellXY = $columnXY.':'.$columnMergeXY;

                $objPHPExcel->getActiveSheet()->mergeCells($columnMergeCellXY)->setCellValue($columnXY, $data->ud_content_title);
                $sheet->getStyle($columnMergeCellXY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                // 수량(건) ,용량  => data
                $column2XY = $columnSeq2.'2';
                $dataXY = $columnSeq2.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$column2XY,'수량(건)');
                setCellCenterValue($objPHPExcel,$dataXY,$data->cnt);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq2.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$udContentId->cnt);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq2++;

                $column2XY = $columnSeq2.'2';
                $dataXY = $columnSeq2.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$column2XY,'용량');
                setCellCenterValue($objPHPExcel,$dataXY,number_format($data->filesize_gb, 2).' GB');
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 용량
                    $totalDataXY = $columnSeq2.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,number_format($statisticTotal->$udContentId->total_tb, 2).' TB');
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');
                }
                $columnSeq2++;

                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }
    /**
     * 운영 통계
     *
     * @param [type] $statisticRecords
     * @return PHPExcel
     */
    function operationExcelFile($statisticRecords){
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();

        $typeSeq = 'B';
        $typeIndex = 2;
        $countSeq = 'C';
        $countIndex = 2;
        
        // $header
        $typeXY = $typeSeq.$typeIndex;
        $countXY = $countSeq.$countIndex;
        $headerXY = $typeXY.':'.$countXY;
        $sheet->getColumnDimension($typeSeq)->setWidth(20);
        $sheet->getColumnDimension($countSeq)->setWidth(20);
        $objPHPExcel->getActiveSheet()->mergeCells($headerXY)->setCellValue($typeXY, '주간 CMS(콘텐츠관리시스템) 운영 결과');
        $sheet->getStyle($headerXY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerXY)->getBorders()->getOutline()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);


        // column
        $typeIndex++;
        $countIndex++;
        $typeXY = $typeSeq.$typeIndex;
        $countXY = $countSeq.$countIndex;
        setCellCenterValue($objPHPExcel,$typeXY,'항목');
        setCellCenterValue($objPHPExcel,$countXY,'건수');

        $borderX = $typeXY;

        // data
        $count = count($statisticRecords);
        $index = 1;
        foreach($statisticRecords as $statisticRecord){
        ++$typeIndex;
        ++$countIndex;
        $typeXY = $typeSeq.$typeIndex;
        $countXY = $countSeq.$countIndex;
        setCellCenterValue($objPHPExcel,$typeXY,$statisticRecord->type);
        setCellCenterValue($objPHPExcel,$countXY,$statisticRecord->count);
            if ($index === $count){
                // 마지막 테두리 생성
                $borderY = $countXY;
                $borderXY = $borderX.':'.$borderY;
                $sheet->getStyle($borderXY)->getBorders()->getOutline()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
            };
            $index++;
        }

        return $objPHPExcel;
    }

    /**
     * 다운로드 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function downloadExcelFile($statisticRecords,$statisticTotal){
        $proxys = [
            'proxy15m1080' => '전송용 (15M)',
            'proxy2m1080'=> '고해상도 (2M)', 
            'proxy' => '중해상도 (1M)', 
            'proxy360' => '저해상도 (1M)', 
            'original' => '원본',
        ];

        $types = [
            'inside' => '내부 다운로드',
            'outside' => '외부 다운로드',
            'inside_logo' => '내부(로고) 다운로드',
            'outside_logo' => '외부(로고) 다운로드'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 3;
        $rowColumnY = 'A';

        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '구분');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            $rowColumnProxy = $statisticRecord->type;
            
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnProxyName = $types[$rowColumnProxy];
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnProxyName);
            $sheet->getColumnDimension($rowColumnY)->setWidth(30);
            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $columnSeq2 = 'B';
            $totalSeq = 'B';
            foreach($proxys as $key => $proxy){
                // header 컬럼 부분
                $data = $statisticRecord->$key;

                $columnXY = $columnSeq.'1';
                
                $columnMergeXY = (++$columnSeq).'1';
                $columnMergeCellXY = $columnXY.':'.$columnMergeXY;

                $objPHPExcel->getActiveSheet()->mergeCells($columnMergeCellXY)->setCellValue($columnXY, $proxy);
                $sheet->getStyle($columnMergeCellXY)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                // 수량(건) ,용량  => data
                $column2XY = $columnSeq2.'2';
                $dataXY = $columnSeq2.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$column2XY,'수량(건)');
                setCellCenterValue($objPHPExcel,$dataXY,$data->count);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq2.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key->count);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq2++;

                $column2XY = $columnSeq2.'2';
                $dataXY = $columnSeq2.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$column2XY,'용량');
                setCellCenterValue($objPHPExcel,$dataXY,number_format($data->filesize, 2).' GB');
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 용량
                    $totalDataXY = $columnSeq2.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,number_format($statisticTotal->$key->filesize, 2).' GB');
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');
                }
                $columnSeq2++;

                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    function downloadExcelFile_back($statisticRecords, $statisticTotal){
        $proxys = [
            'proxy15m1080' => '전송용 (15M)',
            'proxy2m1080'=> '고해상도 (2M)', 
            'proxy' => '중해상도 (1M)', 
            'proxy360' => '저해상도 (1M)', 
            'original' => '원본',
        ];

        $types = [
            'inside' => '내부 다운로드',
            'outside' => '외부 다운로드',
            'inside_logo' => '내부(로고) 다운로드',
            'outside_logo' => '외부(로고) 다운로드'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '구분');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            $rowColumnProxy = $statisticRecord->type;

            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnProxy = $types[$rowColumnProxy];
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnProxy);

            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(30);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($proxys as $key => $proxy){
                // header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(30);
                setCellCenterValue($objPHPExcel,$columnXY,$proxy);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                if (strpos($statisticRecord->type, 'logo') && ($key === 'proxy15m1080' || $key === 'proxy2m1080')) {
                    $logoKey = $key.'logo';
                    $data = $statisticRecord->$logoKey;
                }
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 영상변환 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function videoConvertExcelFile($statisticRecords, $statisticTotal) {
        $proxys = [
            'proxy15m1080' => '전송용 (15M)',
            'proxy2m1080'=> '고해상도 (2M)', 
            'proxy' => '중해상도 (1M)', 
            'proxy360' => '저해상도 (1M)'
        ];
        // let proxys = ['proxy','proxy2m1080', 'proxy15m1080', 'proxy360'];

        $types = [
            'logo' => '영상변환(로고)',
            'manual' => '영상변환(수동)',
            'auto' => '영상변환(자동)'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '구분');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            $rowColumnProxy = $statisticRecord->type;

            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnProxy = $types[$rowColumnProxy];
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnProxy);

            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(30);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($proxys as $key => $proxy){
                // header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(30);
                setCellCenterValue($objPHPExcel,$columnXY,$proxy);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '영상변환(합계)');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 영상변환 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function contentExcelFile($statisticRecords) {
        $headers = [
            'origin' => '원본',
            'clip' => '클립본',
            'news' => '뉴스편집본',
            'master' => '마스터본', 
            'clean'=> '클린본', 
            'rowTotal' => '합계'
        ];

        $types = [
            'total' => '요청',
            'request' => '대기',
            'reject' => '반려',
            'deleted' => '삭제',
            'approval' => '승인'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '구분');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDivision = isset($statisticRecord->total) ? 'total' : $statisticRecord->type;

            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDivision = $types[$rowColumnDivision];
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDivision);

            $sheet->getColumnDimension($rowColumnY)->setWidth(30);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($headers as $key => $header){
                // header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(30);
                setCellCenterValue($objPHPExcel,$columnXY,$header);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                $columnSeq++;

            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 접속자 로그인 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function loginUserExcelFile($statisticRecords,$statisticTotal){
        
        $hourArr = [
            '1' => '01시',
            '2' => '02시',
            '3' => '03시',
            '4' => '04시',
            '5' => '05시',
            '6' => '06시',
            '7' => '07시',
            '8' => '08시',
            '9' => '09시',
            '10' => '10시',
            '11' => '11시',
            '12' => '12시',
            '13' => '13시',
            '14' => '14시',
            '15' => '15시',
            '16' => '16시',
            '17' => '17시',
            '18' => '18시',
            '19' => '19시',
            '20' => '20시',
            '21' => '21시',
            '22' => '22시',
            '23' => '23시',
            '24' => '24시',
            'total' => '합계',
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '구분');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            // 열
            $rowColumnDept = $statisticRecord->dept_nm;
            $dateXY = $rowColumnY.$dataSeq;
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDept);

            // 부서/기관 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(15);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($hourArr as $key => $hour){
                // 행 / header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(13);
                setCellCenterValue($objPHPExcel,$columnXY,$hour);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                $dataXY = $columnSeq.$dataSeq;
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY, $statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 제작폴더 신청 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return void
     */
    function folderRequestExcelFile($statisticRecords,$statisticTotal)
    {
        $headers = [
            'request' => '요청',
            'approval' => '승인',
            'reject' => '반려',
        ];
        
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '요청 일자');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            // 열
            $rowColumnDept = $statisticRecord->request_date;
            $dateXY = $rowColumnY.$dataSeq;
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDept);

            // 부서/기관 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(15);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';

            foreach($headers as $key => $header){
                // 행 / header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(13);
                setCellCenterValue($objPHPExcel,$columnXY,$header);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                $dataXY = $columnSeq.$dataSeq;
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY, $statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');
                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 방송 심의 엑셀
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return void
     */
    function reviewExcelFile($statisticRecords,$statisticTotal)
    {
        $headers = [
            'request' => '요청',
            'approval' => '승인',
            'reject' => '반려',
        ];
        
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        // 구분 컬럼
        setCellCenterValue($objPHPExcel, $rowColumnY.$index, '요청 일자');
        backgroundCellColor($objPHPExcel, $rowColumnY.$index, 'D7D7D7');

        foreach($statisticRecords as $statisticRecord){
            // 열
            $rowColumnDept = $statisticRecord->request_date;
            $dateXY = $rowColumnY.$dataSeq;
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDept);

            $sheet->getColumnDimension($rowColumnY)->setWidth(15);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';

            foreach($headers as $key => $header){
                // 행 / header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(13);
                setCellCenterValue($objPHPExcel,$columnXY,$header);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                $dataXY = $columnSeq.$dataSeq;
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY, $statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');
                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

        /**
     * 사용신청 승인
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function userApprovalExcelFile($statisticRecords, $statisticTotal) {
        $depts = [
            'inside' => '내부(건)',
            'outside' => '외부(건)',
            'c104176100102' => '방송영상부',
            'c104176100101'=> '방송기술부',
            'c104176100104' => '방송제작부',
            'c104176100103' => '방송보도부',
            'c104176100100' => '기획편성부',
            'c104176100105' => '운영관리부',
            'c104176100106' => '온라인콘텐츠부',
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->created_date;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($depts as $key => $dept){
                // header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$dept);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data->cnt);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key->cnt);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 운영 > 의뢰
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function requestExcelFile($statisticRecords, $statisticTotal) {
        $depts = [
            
            'request' => '요쳥',
            'working' => '진행중',
            'complete' => '완료',
            'cancel' => '취소',
            'totalCnt' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->created_date;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($depts as $key => $dept){
                // header 컬럼 부분
                $data = $statisticRecord->$key;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$dept);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data->cnt);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key->cnt);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 콘텐츠 입수 > 유형별
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function contentTypeExcelFile($statisticRecords, $statisticTotal) {
        $columns = [
            'origin' => '원본',
            'clip' => '클립본',
            'news' => '뉴스편집본',
            'master' => '마스터본',
            'clean'=> '클린본',
            'image' => '이미지',
            'audio' => '오디오',
            'cg' => 'CG',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    /**
     * 콘텐츠 삭제 통계
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return void
     */
    function contentDeletedExcelFile($statisticRecords, $statisticTotal)
    {
        $columns = [
            'origin' => '원본',
            'clip' => '클립본',
            'news' => '뉴스편집본',
            'master' => '마스터본',
            'clean'=> '클린본',
            'image' => '이미지',
            'audio' => '오디오',
            'cg' => 'CG',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

        /**
     * 콘텐츠 입수 > 부서별
     *
     * @param Array $statisticRecords
     * @param Array $statisticTotal
     * @return PHPExcel
     */
    function contentDepartmentExcelFile($statisticRecords, $statisticTotal) {
        $columns = [
            '방송영상부',
            '방송기술부',
            '방송제작부',
            '방송보도부',
            '기획편성부',
            '운영관리부',
            '온라인콘텐츠부',
            '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';

        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $dateXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            // 날짜 데이터 컬럼
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            // 날짜 컬럼 넓이 
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$column ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$column);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    // 콘텐츠 입수 > 프로그램별 엑셀
    function contentProgramExcelFile($statisticRecords, $statisticTotal)
    {
        $columns = [
            'origin' => '원본',
            'clip' => '클립본',
            'news' => '뉴스편집본',
            'master' => '마스터본',
            'clean'=> '클린본',
            'image' => '이미지',
            'audio' => '오디오',
            'cg' => 'CG',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->category_title;
            $categoryXY = $rowColumnY.$dataSeq;
            
            setCellCenterValue($objPHPExcel,$categoryXY, $rowColumnDate);
            
            $sheet->getColumnDimension($rowColumnY)->setWidth(40);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    // 콘텐츠 입수 > 회차별
    function contentEpisodeExcelFile($statisticRecords, $statisticTotal)
    {
        $columns = [
            'origin' => '원본',
            'clip' => '클립본',
            'news' => '뉴스편집본',
            'master' => '마스터본',
            'clean'=> '클린본',
            'image' => '이미지',
            'audio' => '오디오',
            'cg' => 'CG',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->tme_no;
            $categoryXY = $rowColumnY.$dataSeq;
            
            setCellCenterValue($objPHPExcel,$categoryXY, $rowColumnDate);
            
            $sheet->getColumnDimension($rowColumnY)->setWidth(20);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }


    // 콘텐츠 입수 > 포맷별
    function contentFormatExcelFile($statisticRecords, $statisticTotal, $statisticsDynamicColumn)
    {
        $columns = [];
        if(!empty($statisticsDynamicColumn)) {
            // foreach ($statisticsDynamicColumn as $key => $value) {
            //     $columns
            // }
            $columns = $statisticsDynamicColumn;
        } else {
            // 수동 입력
            // $columns = [
            //     'origin' => '원본',
            //     'clip' => '클립본',
            //     'news' => '뉴스편집본',
            //     'master' => '마스터본',
            //     'clean'=> '클린본',
            //     'image' => '이미지',
            //     'audio' => '오디오',
            //     'cg' => 'CG',
            //     'rowTotal' => '합계'
            // ];
        }

        if(empty($columns)) return 'error';
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $categoryXY = $rowColumnY.$dataSeq;
            $rowColumnDate = date('Y-m-d',strtotime($rowColumnDate));
            setCellCenterValue($objPHPExcel,$categoryXY, $rowColumnDate);
            
            $sheet->getColumnDimension($rowColumnY)->setWidth(13);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                
                $sheet->getColumnDimension($columnSeq)->setWidth(40);
                // dd($column);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                // dd(1);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $column2XY = $columnSeq.'2';
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    function restoreExcelFile($statisticRecords, $statisticTotal)
    {
        $columns = [
            'file_restore' => '니어라인 리스토어',
            'file_restore_copy' => '니어라인 변환 리스토어',
            'dtl_restore_copy' => 'DTL 리스토어',
            'dtl_restore' => 'DTL 변환 리스토어',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $dateXY = $rowColumnY.$dataSeq;
            
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            
            $sheet->getColumnDimension($rowColumnY)->setWidth(20);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                $sheet->getColumnDimension($columnSeq)->setWidth(30);
                
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    function contentSourceExcelFile($statisticRecords, $statisticTotal)
    {
        $columns = [
            'fileingest' => '파일인제스트',
            'fcp' => 'FCP',
            'fcpx' => 'FCP X',
            'regist_clip' => '클립콘텐츠등록',
            'vs2ingest'=> '인제스트',
            'regist_portal' => '포털등록',
            'fileingest_web' => '파일인제스트 웹',
            'rowTotal' => '합계'
        ];

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        
        $index = 1;
        $dataSeq = 2;
        $rowColumnY = 'A';
        
        foreach($statisticRecords as $statisticRecord){
            $rowColumnDate = $statisticRecord->dt;
            $dateXY = $rowColumnY.$dataSeq;
            
            setCellCenterValue($objPHPExcel,$dateXY, $rowColumnDate);
            
            $sheet->getColumnDimension($rowColumnY)->setWidth(20);

            $statisticRecordsCount = count($statisticRecords);

            $columnSeq = 'B';
            $totalSeq = 'B';
            foreach($columns as $key => $column){
                // header 컬럼 부분
                $data = $statisticRecord->$key ?? 0;
                $columnXY = $columnSeq.'1';
                $sheet->getColumnDimension($columnSeq)->setWidth(20);
                setCellCenterValue($objPHPExcel,$columnXY,$column);
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill ()->setFillType ( PHPExcel_Style_Fill::FILL_SOLID );
                $objPHPExcel->getActiveSheet()->getStyle($columnXY)->getFill()->getStartColor()->setRGB('D7D7D7');

                // 수량(건)  => data
                $dataXY = $columnSeq.$dataSeq;
                
                setCellCenterValue($objPHPExcel,$dataXY,$data);
                if ($statisticRecordsCount === $index) {
                    // 토탈 데이터 수량(건)
                    $totalDataXY = $columnSeq.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalDataXY,$statisticTotal->$key);
                    backgroundCellColor($objPHPExcel, $totalDataXY, 'F4FA46');

                }
                $columnSeq++;

                if($statisticRecordsCount === $index){
                    // 합계 컬럼
                    $totalXY = $rowColumnY.($dataSeq+1);
                    setCellCenterValue($objPHPExcel,$totalXY, '합계');
                    backgroundCellColor($objPHPExcel, $totalXY, 'F4FA46');
                }
            }
            $dataSeq++;
            $index++;
        }
        return $objPHPExcel;
    }

    switch($statisticsType){
        case 'daily':
            $objPHPExcel = dailyExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'operation':
            $objPHPExcel = operationExcelFile($statisticRecords);
            break;
        case 'download':
            $objPHPExcel = downloadExcelFile($statisticRecords, $statisticTotal);
            break;
        case 'convert': 
            $objPHPExcel = videoConvertExcelFile($statisticRecords, $statisticTotal);
            break;
        case 'content':
            $objPHPExcel = contentExcelFile($statisticRecords, $statisticTotal);
            break;
        case 'login_user':
            $objPHPExcel = loginUserExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'folder_request':
            $objPHPExcel = folderRequestExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'review':
            $objPHPExcel = reviewExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'user_approval':
            $objPHPExcel = userApprovalExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'request':
            $objPHPExcel = requestExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_type':
            $objPHPExcel = contentTypeExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_review':
            $objPHPExcel = reviewExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_deleted':
            $objPHPExcel = contentDeletedExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_department':
            $objPHPExcel = contentDepartmentExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_program':
            $objPHPExcel = contentProgramExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_episode':
            $objPHPExcel = contentEpisodeExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_format':
            $objPHPExcel = contentFormatExcelFile($statisticRecords,$statisticTotal, $statisticsDynamicColumn);
            break;
        case 'restore':
            $objPHPExcel = restoreExcelFile($statisticRecords,$statisticTotal);
            break;
        case 'content_source':
            $objPHPExcel = contentSourceExcelFile($statisticRecords,$statisticTotal);
            break;
        default:
            throw new Exception('엑셀파일로 다운로드 할 수 없습니다.');
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

