<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\DataDicWord;
use Api\Services\BaseService;
use Api\Services\DTOs\DataDicWordDto;
use Api\Traits\DataDicDomainIncludeTrait;
use Api\Models\CodeType;
use Api\Models\Code;
use Api\Services\DTOs\DataDicWordSearchParams;

/**
 * 데이터 사전 표준용어 서비스
 */
class DataDicWordService extends BaseService
{
    /**
     * 데이터 사전 표준용어 목록 조회
     *
     * @param DataDicWordSearchParams $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    use DataDicDomainIncludeTrait;

    public function list(DataDicWordSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicWord::query();

        if ($is_deleted) {
            $query->onlyTrashed();
        }

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeDomain($query);



        // 검색
        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('word_nm', 'like', "%{$keyword}%")
                    ->orWhere('word_eng_nm', 'like', "%{$keyword}%")
                    ->orWhere('word_eng_abrv_nm', 'like', "%{$keyword}%");
            });
        }

        $words = paginate($query);


        return $words;
    }
    /**
     * 순번 최댓값 
     *
     * @return void
     */
    public function noMaxValue()
    {
        $query = DataDicWord::query();
        $maxNo = $query->max('no');

        return $maxNo;
    }

    public function searchByName($keyword, $key = 'word_nm')
    {
        $query = DataDicWord::query();
        $query->where($key, '=', "{$keyword}");
        return $query->get();
    }

    public function find($id)
    {
        $query = DataDicWord::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');
        $this->includeDomain($query);

        return $query->find($id);
    }

    public function findOrFail($id)
    {
        $word = $this->find($id);
        if (!$word) {
            api_abort_404('DataDicWord');
        }
        return $word;
    }

    /**
     * 표준용어 생성
     *
     * @param \Api\Services\DTOs\DataDicWordDto $data 표준용어 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicWord 생성된 표준용어 객체
     */
    public function create(DataDicWordDto $dto, User $user)
    {
        $word = new DataDicWord();
        if (!empty($dto->no)) {
            $word->no = $dto->no;
        } else {
            $word->no = $this->noMaxValue() + 1;
        }
        $word->domn_id = $dto->domn_id;
        $word->word_se = $dto->word_se;
        $word->word_nm = $dto->word_nm;
        $word->word_eng_nm = $dto->word_eng_nm;
        $word->word_eng_abrv_nm = $dto->word_eng_abrv_nm;
        $word->sttus_code = $dto->sttus_code;
        $word->thema_relm = $dto->thema_relm;
        $word->dc = $dto->dc;
        $word->regist_user_id = $user->user_id;
        $word->updt_user_id = $user->user_id;
        $word->save();
        return $word;
    }

    /**
     * 데이터 사전 표준용어 수정
     *
     * @param integer 수정할 표준용어 아이디
     * @param \Api\Services\DTOs\DataDicWordDto $dto 용어사전 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicWord 수정된 용어사전 객체
     */
    public function update(int $wordId, DataDicWordDto $dto, User $user)
    {
        $word = $this->findOrFail($wordId);
        $word->no = $dto->no;
        $word->domn_id = $dto->domn_id;
        $word->word_se = $dto->word_se;
        $word->word_nm = $dto->word_nm;
        $word->word_eng_nm = $dto->word_eng_nm;
        $word->word_eng_abrv_nm = $dto->word_eng_abrv_nm;
        $word->sttus_code = $dto->sttus_code;
        $word->dc = $dto->dc;
        $word->regist_user_id = $user->user_id;
        $word->updt_user_id = $user->user_id;
        $word->save();

        return $word;
    }

    /**
     * 표준용어 삭제
     *
     * @param \Api\Models\DataDicWord 삭제할 표준용어
     * @param array $data 표준용어 생성 데이터
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $wordId, User $user)
    {
        $word = $this->findOrFail($wordId);
        $ret = $word->delete();
        return $ret;
    }

    /**
     * 데이터 사전 용어사전 복원
     *
     * @param integer $wordId 복원할 용어사전 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $wordId)
    {
        $word = DataDicWord::onlyTrashed()
            ->where('id', $wordId)
            ->first();

        if (!$word) {
            api_abort_404('DataDicWord');
        }

        $ret = $word->restore();

        return $ret;
    }

    /**
     * DD_CODETYP TABLE  코드 유효값
     *
     * @return void
     */
    public function sysCodes()
    {
        $code_type_id = CodeType::query()->where('code', 'DD_WORD_SE')->first()->id;

        $codes = Code::query()->where('code_type_id', $code_type_id)->get()->toArray();
        return $codes;
    }


    /**
     * 영문용어 배열 조합 함수
     *
     * @param [type] $firArray
     * @param [type] $secArray
     * @return array
     */
    public function getListNumberOfCase($firArray, $secArray)
    {

        $lists = [];
        for ($i = 0; $i < count($firArray); $i++) {
            $first = $firArray[$i];
            for ($j = 0; $j < count($secArray); $j++) {
                $lists[] = $first . '_' . $secArray[$j];
            }
        }
        return $lists;
    }


    /**
     * 영문용어 조합 함수
     *
     * @param [type] $array 2차
     * @return array
     */
    public function getCombineWords($array)
    {

        // $array = array(
        //     array(
        //         'name11',
        //         'name12'
        //     ),
        //     array(
        //         'name21',                
        //         'name24' 
        //     )
        // );         
        $lists = [];
        $length = count($array);
        if ($length > 0) {
            //문자 1개 이상
            $lists = $array[0];
            for ($i = 1; $i < $length; $i++) {
                $lists = $this->getListNumberOfCase($lists, $array[$i]);
            }
        }
        return $lists;
    }
}
