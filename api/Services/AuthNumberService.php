<?php

namespace Api\Services;

use Api\Models\AuthNumber;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
class AuthNumberService extends BaseService
{   
    /**
     * 인증번호 추가
     *
     * @param string $userId 유저아이디
     * @param integer $authNum 인증번호
     * @return \Api\Models\AuthNumber
     */
    public function create($userId,$authNum)
    {
        // 이전에 남아있는 인증번호는 지운다.
        $this->deleteByUserId($userId);

        $authNumber = new AuthNumber();
        
        $authNumber->user_id = $userId;
        $authNumber->auth_number = $authNum;
        $authNumber->save();
        return $authNumber;
    }
    /**
     * 인증번호 조회
     *
     * @param string $userId
     * @param string $authNumber
     * @return void
     */
    public function getByAuthNumberOrUserId($userId,$authNumber)
    {
        $nowDate = Carbon::now()->format('YmdHis');

        $AuthNumberTable = "(SELECT * FROM AUTH_NUMBER WHERE user_id = '{$userId}' AND auth_number = '{$authNumber}' ORDER BY id DESC)";
        $authTime = "(ABS((TO_DATE('{$nowDate}','YYYYMMDDHH24MISS')-(TO_DATE(regist_dt,'YYYYMMDDHH24MISS'))))*24*60*60)";
        $query = DB::table(DB::raw($AuthNumberTable));
        $query->select(
            'id',
            'regist_dt',
            'auth_number',
            'user_id',
            DB::raw("{$authTime} as auth_time")
        );
        
        $authTableQuery = $query->toSql();
        $authQuery = DB::table(DB::raw("({$authTableQuery})"));
        $authQuery->where('auth_time', '<=', '180');
        $auth = $authQuery->first();
        
        
        return $auth;
    }
    /**
     * 유저아이디로 조회후 삭제
     *
     * @param string $userId
     * @return integer 삭제된 레코드 카운트
     */
    public function deleteByUserId($userId){
        $auth = AuthNumber::where('user_id', $userId)->delete();
        return $auth;  
    }

    public function findByUserId($userId){
        $auth = AuthNumber::where('user_id', $userId)->first();
        return $auth; 
    }


}