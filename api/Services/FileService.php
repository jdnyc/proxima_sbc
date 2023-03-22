<?php

namespace Api\Services;

use Carbon\Carbon;
use Api\Models\File;
use Api\Services\BaseService;
use Illuminate\Database\Capsule\Manager as DB;

class FileService extends BaseService
{
    //기본 만료일
    private $expiredAt;

    //기본 갱신 만료일
    public $nextExpiredAt = 7;

    public function __construct()
    {
        $this->expiredAt = Carbon::create('9999', '12', '31');
    }

    public function list($params)
    {
        $query = File::query();
        $lists = paginate($query);
        return $lists;
    }

    /**
     * 파일 조회
     *
     * @param integer $id
     * @return File
     */
    public function find(int $id)
    {
        $query = File::query();
        return $query->find($id);
    }

    /**
     * 파일 조회
     *
     * @param integer $id
     * @return File
     */
    public function findByMediaId(int $mediaId)
    {
        $query = File::where('media_id', $mediaId);
        return $query->get();
    }

    /**
     * 파일 조건 조회
     *
     * @param integer $id
     * @return File
     */
    public function findByMediaIdAndStorageId(int $mediaId, int $storageId = null)
    {
        $query = File::where('media_id', $mediaId);
        $query->where('storage_id', $storageId);
        return $query->first();
    }

    /**
     * 조회 또는 실패 처리
     *
     * @param integer $id
     * @return file
     */
    public function findOrFail(int $id)
    {
        $file = $this->find($id);
        if (!$file) {
            api_abort_404(File::class);
        }
        return $file;
    }

    /**
     * 생성
     *
     * @param $data 배열 데이터
     * @return \Api\Models\file 생성된 컬럼 객체
     */
    public function create($data)
    {
        if (empty($data['expired_at'])) {
            $data['expired_at'] = $this->expiredAt;
        }
        $file = new File($data);
        $file->save();
        return $file;
    }

    /**
     * 수정
     *
     * @param integer 수정할 컬럼 아이디
     * @param $data 배열 데이터
     * @return \Api\Models\file 수정된 컬럼 객체
     */
    public function update(int $id, $data)
    {
        $file = $this->findOrFail($id);
        $file->update($data);

        return $file;
    }

    /**
     * 삭제
     *
     * @param integer $id 삭제할 컬럼 아이디
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $id)
    {
        $file = $this->findOrFail($id);
        $ret = $file->delete();
        return $ret;
    }

    /**
     * 컬럼 복원
     *
     * @param integer $columnId 복원할 컬럼 아이디
     * @return bool|null 복원 성공여부
     */
    public function restore(int $id)
    {
        $file = File::onlyTrashed()
            ->where('id', $id)
            ->first();

        if (!$file) {
            api_abort_404('not found file');
        }

        $ret = $file->restore();

        return $ret;
    }
}
