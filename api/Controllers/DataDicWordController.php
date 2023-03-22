<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DataDicWordService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\DataDicWordDto;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\DTOs\DataDicWordSearchParams;

use PhpOffice\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DataDicWordController extends BaseController
{

    private  $dataDicWordService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->dataDicWordService = new DataDicWordService($container);
    }

    /**
     * 표준용어 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new DataDicWordSearchParams($input);
        $words = $this->dataDicWordService->list($params);

        // 코드 아이템 가져오기
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);
        $selectCodeFields = ['id', 'code_itm_code', 'code_itm_nm'];
        $sysCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_WORD_SE')
            ->codeItems()
            ->get($selectCodeFields);
        $sysCodeItemsSt = $dataDicCodeSetService->findByCodeOrFail('DD_STATUS')
            ->codeItems()
            ->get($selectCodeFields);


        foreach ($words as $word) {
            $wordSeName = $word->word_se;
            $word->word_se_nm = DataDicCodeItemService::getCodeItemByCode($sysCodeItems, $wordSeName);
        }
        foreach ($words as $word) {
            $wordStatusCode = $word->sttus_code;
            $word->word_st_code = DataDicCodeItemService::getCodeItemByCode($sysCodeItemsSt, $wordStatusCode);
        }

        return $response->ok($words);
    }
    /**
     * 표준용어 이름과 동일한 표준용어 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function searchByName(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = new DataDicWordSearchParams($input);

        $keywords = explode(' ', $params->keyword);
        $words = [];
        $emptyWords = [];

        foreach ($keywords as $keyword) {
            if (empty($keyword)) {
                //공백제거
                continue;
            }
            $findWords = [];
            $elseWords = [];
            $lists = $this->dataDicWordService->searchByName($keyword)->toArray();
            if (empty($lists)) {
                $emptyWords[] = $keyword;
            } else {
                foreach ($lists as $list) {
                    $sttus_code = $list['sttus_code'];
                    if ($sttus_code == 'ACCEPT') {
                        $findWords[] = $list['word_eng_abrv_nm'];
                    } else {
                        $elseWords[] = $list['word_eng_abrv_nm'];
                    }
                }
                if (empty($findWords)) {
                    $emptyWords[] = $keyword;
                }
            }
            $words[] = $findWords;
        }

        if (!empty($emptyWords)) {
            if (!empty($elseWords)) {
                return $response->error("표준용어 ( " . join(', ', $elseWords) . " )가 승인된 용어가 아닙니다", null, 200);
            } else {
                return $response->error("표준용어 ( " . join(', ', $emptyWords) . " )가 없습니다", null, 200);
            }
        }

        $combineWords = $this->dataDicWordService->getCombineWords($words);
        return $response->ok($combineWords);
    }

    /**
     * 표준용어 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $wordId = $args['word_id'];
        $word = $this->dataDicWordService->find($wordId);
        return $response->ok($word);
    }

    /**
     * 표준용어 등록
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicWordDto($data);
        $dto->createValidate();

        $word = $this->dataDicWordService->create($dto, $user);
        return $response->ok($word, 201);
    }

    /**
     * 표준용어 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $wordId = $args['word_id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new DataDicWordDto($data);
        $dto->createValidate();

        $word = $this->dataDicWordService->update($wordId, $dto, $user);
        return $response->ok($word);
    }

    /**
     * 표준용어 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $wordId = $args['word_id'];
        $user = auth()->user();

        $this->dataDicWordService->delete($wordId, $user);
        return $response->ok();
    }

    /**
     * 표준용어 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $wordId = $args['word_id'];
        $user = auth()->user();

        $this->dataDicWordService->restore($wordId, $user);
        return $response->ok();
    }

    /**
     * 표준용어 엑셀 임포트
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function importFromExcel(ApiRequest $request, ApiResponse $response, array $args)
    {
        set_time_limit(600);
        $uploadedFiles = request()->getUploadedFiles();

        //단건만 처리
        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                //임시파일경로
                $filePath = $uploadedFile->file;
                $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                $extension = strtolower($extension);
                //$files->getStream();
                // dump($uploadedFile->getClientFilename());
                // dump($uploadedFile->getClientMediaType());
                // dump($uploadedFile->getSize());
                break;
            }
        }

        $allowExtension  = array(
            'xls', 'xlsx'
        );

        if (!in_array($extension, $allowExtension)) {
            return $response->error("허용확장자가 아닙니다 ");
        }
        $colMap = [
            'no',
            'word_se',
            'word_nm',
            'word_eng_nm',
            'word_eng_abrv_nm',
            'dc',
            'thema_relm',
            null,
            'sttus_code'
        ];

        $sttus_code_code = [
            '사용중' => 'ACCEPT'
        ];
        $word_se_code = [
            '표준어' => 'STDLNG',
            '동의어' => 'SYNONM'
        ];

        $user = auth()->user();

        $allowExtensionMap = array(
            'xls' => 'Xls',
            'xlsx' => 'Xlsx'
        );


        $reader = PhpSpreadsheet\IOFactory::createReader($allowExtensionMap[$extension]);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getSheet(0);
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5

        $colNameRowNum = 2;
        $dataRowNum = 3;

        // $table = new DataDicWord();
        // $return = $table->all();

        for ($row = 2; $row <= $highestRow; ++$row) {


            if ($colNameRowNum == $row) {
                continue;
            }

            if ($dataRowNum <= $row) {
                $createData = [];
                $createData['tmpr_yn'] = 'Y';
                for ($col = 1; $col <= $highestColumnIndex; ++$col) {

                    $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();

                    $columnName = empty($colMap[$col - 1]) ? null : $colMap[$col - 1];
                    if (!empty($columnName)) {

                        if ($columnName == 'sttus_code') {
                            $value = empty($sttus_code_code[$value]) ? $value : $sttus_code_code[$value];
                            $value = "REQUEST";
                        }
                        if ($columnName == 'word_se') {
                            $value = empty($word_se_code[$value]) ? $value : $word_se_code[$value];
                        }
                        if ($columnName == 'no') {
                            $value = (int) $value;
                        }
                        $createData[$columnName] = $value;
                    }
                }

                //dd($createData);

                $dto = new DataDicWordDto($createData);

                $exist = $this->dataDicWordService->searchByName($createData['no'], 'no');
                if (count($exist) >  0) {
                    continue;
                }
                $dto->createValidate();
                $word = $this->dataDicWordService->create($dto, $user);
            }
        }

        return $response->ok();
    }

    public function exportToExcel(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        set_time_limit(600);

        $title = '표준용어';
        request()->sort = 'no';
        request()->dir = 'ASC';
        $filenameCharset = empty($input['charset']) ? 'euc-kr' : $input['charset'];
        unset($input['charset']);

        $params = new DataDicWordSearchParams($input);

        // 코드 아이템 가져오기
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);
        $selectCodeFields = ['code_itm_code', 'code_itm_nm'];
        $sysCodeItems = $dataDicCodeSetService->findByCodeOrFail('DD_WORD_SE')
            ->codeItems()
            ->get($selectCodeFields);
        $sysCodeItemsSt = $dataDicCodeSetService->findByCodeOrFail('DD_STATUS')
            ->codeItems()
            ->get($selectCodeFields);

        //용어 전체 조회
        request()->limit = 1;
        $words = $this->dataDicWordService->list($params);
        $total = $words->total();

        //엑셀 양식 생성
        $columnList = array(
            "no" => "NO",
            "word_se_nm" => "구분",
            "word_nm" => "용어명",
            "word_eng_nm" => "용어영문명",
            "word_eng_abrv_nm" => "영문약어명",
            "dc" => "정의",
            "thema_relm" => "주제영역",
            "regist_dt" => "등록일",
            "word_st_code" => "상태"
        );
        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle($title);

        $rownum = 1;

        // $spreadsheet->getActiveSheet()->mergeCells('A1:I1');
        // $sheet1->setCellValue("A1", "행정표준용어현황");    
        // $rownum++;

        //헤더 입력
        $colIdx = 0;
        foreach ($columnList as $key => $val) {

            $celname = chr(65 + $colIdx);
            $colIdx++;
            //값 입력
            $sheet1->setCellValue($celname . $rownum, $val);
            $getStyle = $sheet1->getStyle($celname . $rownum);
            $getStyle->getFont()->applyFromArray(['name' => '맑은 고딕', 'size' => 10, 'bold' => true]);
            $getStyle->applyFromArray(
                [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID
                        //,'color' => ['rgb' => '#d9d9d9']
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_THIN],
                        'right' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]
            );
            $getStyle->getAlignment()->setWrapText(false);
            if ($key == 'dc') {
                $cellWidth = 45;
            } else {
                $cellWidth = 25;
            }
            $sheet1->getColumnDimension($celname)->setWidth($cellWidth);
        }
        $rownum++;

        $loofLimit = 1000;
        for ($loofStart = 0; $loofStart < $total; $loofStart += $loofLimit) {
            //용어 전체 조회          
            request()->limit = $loofLimit;
            request()->start = $loofStart;
            $words = $this->dataDicWordService->list($params);

            foreach ($words as $word) {
                $wordSeName = $word->word_se;
                $word->word_se_nm = DataDicCodeItemService::getCodeItemByCode($sysCodeItems, $wordSeName);
            }
            foreach ($words as $word) {
                $wordStatusCode = $word->sttus_code;
                $word->word_st_code = DataDicCodeItemService::getCodeItemByCode($sysCodeItemsSt, $wordStatusCode);
            }

            foreach ($words as $key => $row) {
                $rowdata = array(
                    'no'                    => $row->no,
                    'word_se_nm'            => $row->word_se_nm->code_itm_nm,
                    'word_nm'               => $row->word_nm,
                    'word_eng_nm'           =>  $row->word_eng_nm,
                    'word_eng_abrv_nm'      => $row->word_eng_abrv_nm,
                    'dc'                    => $row->dc,
                    'thema_relm'            => $row->thema_relm,
                    'regist_dt'             => date("Y-m-d", strtotime($row->regist_dt)),
                    'word_st_code'          => $row->word_st_code->code_itm_nm
                );
                //로우 입력            
                $colIdx = 0;
                foreach ($rowdata as $rowkey => $val) {
                    $celname = chr(65 + $colIdx);
                    $colIdx++;
                    //값 입력
                    $sheet1->setCellValue($celname . $rownum, $val);
                }
                $rownum++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        $full_path = $title . '_' . date("Y-m-d") . '.xlsx';
        if ($filenameCharset == 'euc-kr') {
            $full_path    = iconv('utf-8',  $filenameCharset, $full_path);
        }


        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $full_path . '"');
        $writer->save('php://output');
    }
}
