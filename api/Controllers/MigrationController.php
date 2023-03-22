<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class MigrationController extends BaseController
{
    /**
     * 테스트용
     *
     */


    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container->get('db');
    }
    public function index(ApiRequest $request, ApiResponse $response, array $args){
        
        $tableName = $args['table'];
        $validTable = 'TAPE_';
        if(!strstr($tableName,$validTable )){
            api_abort(400);
        }


        $list = DB::table($tableName.' as M')
        ->leftJoin('bc_task as TM',function($join){
            $join->on('m.content_id', '=', 'TM.src_content_id');
            $join->where('TM.type','60');
        })->leftJoin('bc_task as DTL',function($join){
            $join->on('m.content_id', '=', 'DTL.src_content_id');
            $join->where('DTL.type','110');
        })->leftJoin('bc_content as C',function($join){
            $join->on('m.content_id', '=', 'c.content_id');
        })
        ->whereNotNull('m.f_43')
        ->select(['m.f_1','m.content_id','tm.SOURCE', 'tm.status AS tm_status' , 'dtl.status as dtl_status'])
        ->orderBy('m.id','asc')
        ->get()->all();

//         SELECT m.f_1,m.content_id,c.title,tm.SOURCE, tm.status , dtl.status 
// FROM TAPE_116A_001 m 
// LEFT OUTER JOIN (SELECT * FROM bc_content t  ) c ON (m.content_id=c.content_id)
// LEFT OUTER JOIN (SELECT * FROM bc_content t  ) c ON (m.content_id=c.content_id)
// LEFT OUTER JOIN (SELECT * FROM bc_task t WHERE TYPE='60' ) tm ON (m.content_id=tm.src_content_id)
// LEFT OUTER JOIN  (SELECT * FROM bc_task t WHERE TYPE='110' ) dtl ON (m.content_id=dtl.SRC_CONTENT_ID) ORDER BY m.id ;

                
        array_unshift( $list, [
            'TAPENO','CONTENT_ID','FILE','TM STATUS','ARCHIVE STATUS'
        ]);

        $body = self::jsonToDebug(json_encode($list));
        return $response->write($body);
    }
    public function count(ApiRequest $request, ApiResponse $response, array $args){
              
        $tableName = $args['table'];
        $validTable = 'TAPE_';
        if(!strstr($tableName,$validTable )){
            api_abort(400);
        }


        $list = DB::table($tableName.' as M')
        ->leftJoin('bc_task as TM',function($join){
            $join->on('m.content_id', '=', 'TM.src_content_id');
            $join->where('TM.type','60');
        })->leftJoin('bc_task as DTL',function($join){
            $join->on('m.content_id', '=', 'DTL.src_content_id');
            $join->where('DTL.type','110');
        })
        ->whereNotNull('m.f_43')
        ->groupBy(['TM.status','DTL.status'])
        ->selectRaw('TM.status AS tm_status,DTL.status AS dtl_status,COUNT(m.ID) AS cnt')
       //->toSql();
         ->get()->all();
       // dd($list);
//         SELECT tm.status AS "입수상태",dtl.status AS "DTL 아카이브상태",count(*) AS "건수"
// FROM TAPE_116A_001 m 
// LEFT OUTER JOIN (SELECT * FROM bc_content t  ) c ON (m.content_id=c.content_id)
// LEFT OUTER JOIN (SELECT * FROM bc_content t  ) c ON (m.content_id=c.content_id)
// LEFT OUTER JOIN (SELECT * FROM bc_task t WHERE TYPE='60' ) tm ON (m.content_id=tm.src_content_id)
// LEFT OUTER JOIN  (SELECT * FROM bc_task t WHERE TYPE='110' ) dtl ON (m.content_id=dtl.SRC_CONTENT_ID) GROUP BY tm.status,dtl.status;
        $body = "";

        //  array_unshift( $list, [
        //      'TM STATUS','ARCHIVE STATUS','TOTAL'
        //  ]);
        // foreach($list as $row)
        // {
        //     foreach($row as $key => $val)
        //     {
        //         $body .= $val.' | ';
        //     }
        //     $body .= "\n";
        // }
        // $newResponse = $response->withHeader(
        //     'Content-type',
        //     'charset=utf-8'
        // );

        array_unshift( $list, [
              'TM STATUS','ARCHIVE STATUS','TOTAL'
          ]);
    
        $body = self::jsonToDebug(json_encode($list));
        return $response->write($body);
    }

    public static function jsonToDebug($jsonText = '')
    {
        $arr = json_decode($jsonText, true);

        $html = "<table><tbody>";
        if ($arr && is_array($arr)) {
            $html .= self::_arrayToHtmlTableRecursive($arr);
        }
        $html .= "</tbody></table>";

        
        $body ="<!DOCTYPE html>
        <html>
        <head>
        <style>
        table {
          font-family: arial, sans-serif;
          border-collapse: collapse;
          width: 100%;
        }
        
        td, th {
          border: 1px solid #dddddd;
          text-align: left;
          padding: 8px;
        }
        
        tr:nth-child(even) {
          background-color: #dddddd;
        }
        </style>
        </head>
        <body>".$html."</body></html>";
        return $body;
    }

    private static function _arrayToHtmlTableRecursive($arr) {
        $str = "";
        foreach ($arr as $rk => $row) {
            $str .= "<tr>";
            //$str .= "<td>$key</td>";
            $str .= "<td>";

            foreach ($row as $key => $val) {

                $str .= "<td><strong>$val</strong></td>";
            }
            $str .= "</tr>";
        }
        $str .= "";

        return $str;
    }
}
