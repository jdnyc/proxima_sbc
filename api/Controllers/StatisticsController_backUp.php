<?php

namespace Api\Controllers;

use Api\Models\Task;
use Api\Models\User;
use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Types\TaskStatus;
use Api\Types\StorageIdMap;
use Api\Services\FileService;
use Api\Services\TaskService;
use Api\Services\ArchiveService;
use Api\Services\ContentService;
use Api\Services\UserContentService;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;

use Api\Services\StatisticsService;
use Api\Support\Helpers\ValidationHelper;
use Illuminate\Database\Capsule\Manager as DB;


use PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StatisticsController extends BaseController
{

    /**
     * 통계 서비스
     *
     * @var \Api\Services\StatisticsService
     */
    private $statisticsService;

    /**
     * 사용자정의 콘텐츠 서비스
     *
     * @var \Api\Services\UserContentService
     */
    private $userContentService;
    /**
     * 생성자
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->statisticsService = new StatisticsService($container);
        $this->userContentService = new UserContentService($container);
    }
    /**
     * 아카이브 일별 통계(밑에껄로 수정 일단 놔둠);
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function dailyArchiveStatisticsTEST(ApiRequest $request, ApiResponse $response, array $args)
    {
        $udContentIdArrays = [0,1,2,3,7,9];
   
        $input = $request->all();
        ValidationHelper::emptyValidate($input, ['start_date', 'end_date']);
        
        $dailyStatistics = $this->statisticsService->dailyArchiveStatisticsList($input);
        
        $dataArrays = array();
        foreach($dailyStatistics as $daily){
           $dataArrays[$daily->created_date][$daily->ud_content_id] = $daily;
           $created = new \Carbon\Carbon($daily->created_date);
           $dataArrays[$daily->created_date]['created_date'] = $created->format('Y-m-d');
      
        }
        $dataArrays = array_values($dataArrays);

        

        /**
         * 콘텐츠 별로 없는 데이터 0으로 채워주기
         */
        $datas = [];
        foreach($dataArrays as $dataArray){
            //데이터가 있으면 true 없으면 false
            foreach($udContentIdArrays as $udContentIdArray){
                if(!array_key_exists($udContentIdArray, $dataArray)){
                    // 비어있는 데이터
                    if($udContentIdArray === 0){
                        // 아직 텔레시네가 없음
                        $data = (object)array(
                            'ud_content_title'=>'텔레시네',
                            'created_date'=>$dataArray['created_date'],
                            'cnt'=>'0',
                            'filesize_gb'=>'0',
                            'filesize_tb'=>'0',
                            'ud_content_id'=>(string)$udContentIdArray
                        );
                    }else{
                        $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
                        $data = (object)array(
                            'ud_content_title'=>$udContentTitle,
                            'created_date'=>$dataArray['created_date'],
                            'cnt'=>'0',
                            'filesize_gb'=>'0',
                            'filesize_tb'=>'0',
                            'ud_content_id'=>(string)$udContentIdArray
                        );
                   
                    }
                    $dataArray[$udContentIdArray] = $data;
                }
            }
            array_push($datas, $dataArray);            
        };
        
    
        /**
         * 중간에 비어있는 날짜가 있을시 데이터는 0으로 넣어준다.
         */
        $statisticsData = [];

        $startDate = substr($input['start_date'],0,8);

        $endDateTimeStamp = strtotime($input['end_date']);
        $endDate = date('Ymd', $endDateTimeStamp);
     
        while(true){
            if($startDate === $endDate){
                break;
            }else{
                $isCheck = true;
                foreach($datas as $data){
                 
                    $created = new \Carbon\Carbon($data['created_date']);
             
                    if($startDate === $created->format('Ymd')){
                        array_push($statisticsData, $data);
                        
                        $isCheck = false;
                    break;
                    }
                }
                if($isCheck){
                    $startDate = new \Carbon\Carbon($startDate);
                    $insertData['created_date'] = $startDate->format('Y-m-d');
                    //콘텐츠별로 값을 0으로 넣어주기
                    foreach($udContentIdArrays as $udContentIdArray){
                        // 비어있는 데이터
                        if($udContentIdArray === 0){
                            // 아직 텔레시네가 없음
                            $data = (object)array(
                                'ud_content_title'=>'텔레시네',
                                'created_date'=>$startDate,
                                'cnt'=>'0',
                                'filesize_gb'=>'0',
                                'filesize_tb'=>'0',
                                'ud_content_id'=>(string)$udContentIdArray
                            );
                        }else{
                            $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
                            $data = (object)array(
                                'ud_content_title'=>$udContentTitle,
                                'created_date'=>$startDate,
                                'cnt'=>'0',
                                'filesize_gb'=>'0',
                                'filesize_tb'=>'0',
                                'ud_content_id'=>(string)$udContentIdArray
                            );
                    
                        }
                        $insertData[$udContentIdArray] = $data;
                    }
                    array_push($statisticsData, $insertData);
                    
                }
            }

            $startDateTimeStamp = strtotime($startDate);
            $startDateTimeStamp = strtotime('+1 day', $startDateTimeStamp);
            $startDate = date('Ymd', $startDateTimeStamp);
        }
    
        return $response->ok($statisticsData);
    }
    /**
     * 아카이브 일별 통계
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function dailyArchiveStatistics(ApiRequest $request, ApiResponse $response, array $args)
    {
        $udContentIdArrays = [0,1,2,3,7,9];

        $input = $request->all();

        $dailyStatistics = $this->statisticsService->dailyArchiveStatisticsList($input);
        ValidationHelper::emptyValidate($input, ['start_date', 'end_date']);
        
       
        $startDtCarbon = new \Carbon\Carbon($input['start_date']);
        $endDtCarbon = new \Carbon\Carbon($input['end_date']);
        $diffDays = $endDtCarbon->diffInDays($startDtCarbon);
        $days = [];
       
        // 날짜별로 분류하기 위해
        $statisticsByDateArray = [];
        foreach($dailyStatistics as $daily){
            $statisticsByDateArray[$daily->created_date][$daily->ud_content_id]=$daily;
        }
                

        // 날짜별 통계 데이터 배열 선언
        $statisticsByDateArray = [];
        foreach($dailyStatistics as $daily){
            $statisticsByDateArray[$daily->created_date][$daily->ud_content_id]=$daily;
        }

        // 검색된 날짜 기간 만큼 1일씩 더한 배열
        for($i=0; $i<$diffDays; $i++) {
            $nowLoopDate = $startDtCarbon->format('Y-m-d');
            $nowLoopDateYmd = $startDtCarbon->format('Ymd');
            $days[$i]['created_date'] = $nowLoopDate;

            // 데이터가 있으면 배열에 넣는다
            $statisticsData = $statisticsByDateArray[$nowLoopDateYmd] ?? null;

            if($statisticsData){
                // 날짜에 맞는 데이터가 있을떄
                foreach($udContentIdArrays as $udContentIdArray){
                    // 날짜에 데이터가 있을때 어떤 데이터가 있는지
                    $statisticsContentData = $statisticsByDateArray[$nowLoopDateYmd][$udContentIdArray] ?? null;
                    if($udContentIdArray == 0){
                        $udContentTitle = '텔레시네';
                    }else{
                        $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
                    }
                    if (is_null($statisticsContentData)) { 
                        $statisticsContentData = (object)array(
                            'ud_content_title'=>$udContentTitle,
                            'created_date'=>$nowLoopDateYmd,
                            'cnt'=>'0',
                            'filesize_gb'=>'0',
                            'filesize_tb'=>'0',
                            'ud_content_id'=>(string)$udContentIdArray
                        );
                    }
                    $days[$i][$udContentIdArray]=$statisticsContentData;
                }

            }else{
                // 날짜에 맞는 데이터가 없을때
                foreach($udContentIdArrays as $udContentIdArray){
                    if($udContentIdArray == 0){
                        $udContentTitle = '텔레시네';
                    }else{
                        $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
                    }
                    $statisticsContentData = (object)array(
                        'ud_content_title'=>$udContentTitle,
                        'created_date'=>$nowLoopDateYmd,
                        'cnt'=>'0',
                        'filesize_gb'=>'0',
                        'filesize_tb'=>'0',
                        'ud_content_id'=>(string)$udContentIdArray
                    );

                    $days[$i][$udContentIdArray]=$statisticsContentData;
                }
                    
            }
            // 날짜가 1일씩 늘어난다.
            $startDtCarbon = $startDtCarbon->addDays(1);
        }

        

            // 데이터가 있으면 배열에 데이터를 넣는다
            // $statisticsData = $statisticsByDateArray[$nowLoopDateYmd] ?? null;
            // if($statisticsData){
            //     // 데이터가 있으면 배열에 데이터를 넣는다
            //     foreach($udContentIdArrays as $udContentIdArray){
            //         // 없는 데이터는 0으로 넣어준다.
            //         $statisticsContentData = $statisticsByDateArray[$nowLoopDateYmd][$udContentIdArray] ?? null;
            //         if ($statisticsContentData) { 
            //         }else{
            //             if ($udContentIdArray === 0) {
            //                 $statisticsContentData = (object)array(
            //                     'ud_content_title'=>'텔레시네',
            //                     'created_date'=>$nowLoopDateYmd,
            //                     'cnt'=>'0',
            //                     'filesize_gb'=>'0',
            //                     'filesize_tb'=>'0',
            //                     'ud_content_id'=>(string)$udContentIdArray
            //                 );
            //             } else {
            //                 $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
            //                 $statisticsContentData = (object)array(
            //                     'ud_content_title'=>$udContentTitle,
            //                     'created_date'=>$nowLoopDateYmd,
            //                     'cnt'=>'0',
            //                     'filesize_gb'=>'0',
            //                     'filesize_tb'=>'0',
            //                     'ud_content_id'=>(string)$udContentIdArray
            //                 );
            //             }
            //         }
            //         $days[$i][$udContentIdArray]=$statisticsContentData;
            //     }
            // }else{
            //     // 없는데이터는 콘텐츠 별로 0으로 넣어준다
            //     foreach ($udContentIdArrays as $udContentIdArray) {
            //         if ($udContentIdArray === 0) {
            //             $statisticsContentData = (object)array(
            //                 'ud_content_title'=>'텔레시네',
            //                 'created_date'=>$nowLoopDateYmd,
            //                 'cnt'=>'0',
            //                 'filesize_gb'=>'0',
            //                 'filesize_tb'=>'0',
            //                 'ud_content_id'=>(string)$udContentIdArray
            //             );
            //         } else {
            //             $udContentTitle = $this->userContentService->getUdContentByUdContentId($udContentIdArray)->ud_content_title;
            //             $statisticsContentData = (object)array(
            //                 'ud_content_title'=>$udContentTitle,
            //                 'created_date'=>$nowLoopDateYmd,
            //                 'cnt'=>'0',
            //                 'filesize_gb'=>'0',
            //                 'filesize_tb'=>'0',
            //                 'ud_content_id'=>(string)$udContentIdArray
            //             );
            //         }

            //         $days[$i][$udContentIdArray]=$statisticsContentData;
            //     }
            // }
            // 날짜가 1일씩 늘어난다.
            // $startDtCarbon = $startDtCarbon->addDays(1);
        // };

       
        return $response->ok($days);
    }
    /**
     * 아카이브 주간 통계
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function weekArchiveStatistics(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $statistics = $this->statisticsService->weekArchiveStatisticsList($input);
        return $response->ok($statistics);
    }
    /**
     * 운영 통계
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function operationStatistics(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $operationStatistics = $this->statisticsService->operationStatisticsList($input);
        return $response->ok($operationStatistics);
    }
    /**
     * 엑셀 다운로드(운영 통계)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function exportToExcelOperation(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $filenameCharset = 'euc-kr';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    }
        /**
     * 엑셀 다운로드(주간 아카이브)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function exportToExcelWeekArchive(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
    
        $filenameCharset = empty($input['charset']) ? 'euc-kr' : $input['charset'];
        $title = '아카이브_주간_통계';
        $statisticsList = $this->statisticsService->weekArchiveStatisticsList($input);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 합계 계산
        $totalCnt = 0;
        $totalFileSizeTb = 0;
        foreach($statisticsList as $statistics){
            if(!is_null($statistics->ud_content_id)){
                $cnt = (int)$statistics->cnt;
                $fileSizeTb = (float)$statistics->filesize_tb;
                $totalCnt = $totalCnt+$cnt;
                $totalFileSizeTb = $totalFileSizeTb+$fileSizeTb;
            };
        }

        // 항목 추가
        $headerAdd = (object)array(
            'ud_content_title'=>'항목',
            'cnt'=>'수량(건)',
            'filesize_tb'=>'용량'
        );
        array_unshift($statisticsList,$headerAdd);  
        //합계 추가
        $headerAdd = (object)array(
            'ud_content_title'=>'금주 합계',
            'cnt'=>$totalCnt,
            'filesize_tb'=>$totalFileSizeTb,
            'type' => 'total'
        );
        array_push($statisticsList,$headerAdd);  


        // create header
        $headerIdx = 3;
        // 67:'C'
        $headerChr = 66;
        $firstHeaderChr = $headerChr;
        $statisticsIdx = 1;
        foreach($statisticsList as $key => $statistics){
            $rowIdx = $headerIdx+1;
            
            // 헤더 부분 
            $header = $statistics->ud_content_title;
            $headerCell = chr($headerChr).$headerIdx;
            $sheet->setCellValue($headerCell, $header);
            $sheet->getStyle($headerCell)->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10, 'bold' => true]);
            if($statistics->type === 'total'){
                $cellWidth = 15;
            }else{
                $cellWidth = 12;
            };

            // 첫번쨰, 그리고 마지막이 아닐때 데이터 부분이다.
            // 건수 
            if($statisticsIdx === 1){
                // 첫번쨰 일때는 항목 컬럼->수량(건)
                $sheet->setCellValue(chr($headerChr).$rowIdx, $statistics->cnt);
                $sheet->getStyle(chr($headerChr).$rowIdx)->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10, 'bold' => true]);
                $rowIdx++;
            }else{
                $sheet->setCellValue(chr($headerChr).$rowIdx, $statistics->cnt.'건');
                $rowIdx++;
            }
      
      //////////////////////////////////////////////////////////////////////////////////////////////

            if($statisticsIdx === 1){
                // 첫번쨰 일때는 항목 컬럼->용량
                $sheet->setCellValue(chr($headerChr).$rowIdx, $statistics->filesize_tb);
                $sheet->getStyle(chr($headerChr).$rowIdx)->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10, 'bold' => true]);
            }else{
                // 용량
                if($statistics->filesize_tb == '0'){
                    $sheet->setCellValue(chr($headerChr).$rowIdx, '0.00'.' TB');
                }else{
                    $sheet->setCellValue(chr($headerChr).$rowIdx, $statistics->filesize_tb.' TB');
                }
            }
     
            $sheet->getColumnDimension(chr($headerChr))->setWidth($cellWidth);
   

            if(count($statisticsList) == $statisticsIdx){
                // 타이틀 부분
                $titleRowIdx = $headerIdx-1;
                $titleStartCell = chr($firstHeaderChr).$titleRowIdx;
                $titleEndCell = chr($headerChr).$titleRowIdx;
                $titleMergeCell = $titleStartCell.':'.$titleEndCell;
                $spreadsheet->getActiveSheet()->mergeCells($titleMergeCell)->setCellValue($titleStartCell,'아카이브 통계');
                $sheet->getStyle($titleMergeCell)->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 12, 'bold' => true]);
                

                $allStartCell = chr($firstHeaderChr).$titleRowIdx;
                $allEndCell = chr($headerChr).$rowIdx;
                $allCell = $allStartCell.':'.$allEndCell;
                $sheet->getStyle($allCell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);                

                $sheet->getStyle($allCell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
            }

            $statisticsIdx++;
            $headerChr++;
        }
        
        $full_path = $title . '_' . date("Y-m-d") . '.xlsx';
        if ($filenameCharset == 'euc-kr') {
            $full_path    = iconv('utf-8',  $filenameCharset, $full_path);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $full_path . '"');
        $writer->save('php://output');
    }
}