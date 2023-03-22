<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use \Api\Support\Helpers\MetadataMapper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ConvToDB extends Command
{
    protected function configure()
    {
        $this->setName('mig:conv')
            ->setDescription('Content migration.')
            ->addOption('bscontent', 'b', InputOption::VALUE_OPTIONAL, 'BS content')
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL, 'Category');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';

        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $bsContentId = $input->getOption('bscontent');
        $categoryId = $input->getOption('category');

        dump(date("Y-m-d H:i:s").' - start');

        $migrationService = new \ProximaCustom\services\MigrationService();            
        $contentService = new \Api\Services\ContentService($app->getContainer());
        $mapper = new \Api\Support\Helpers\MetadataMapper($app->getContainer());
        // SELECT "UD_CONTENT_ID", count(*)
        // from "BC_CONTENT" inner join "BC_CONTENT_STATUS" on "BC_CONTENT"."CONTENT_ID" = "BC_CONTENT_STATUS"."CONTENT_ID" 
        // inner join "BC_USRMETA_CONTENT" on "BC_CONTENT"."CONTENT_ID" = "BC_USRMETA_CONTENT"."USR_CONTENT_ID" 
        // where "BC_CONTENT"."IS_DELETED" = 'N' and "BC_CONTENT"."STATUS" = 2 and "BC_CONTENT_STATUS"."BFE_VIDEO_ID" is not NULL
        // and "BC_CONTENT"."UD_CONTENT_ID" IN (2,3,7) GROUP BY "BC_CONTENT"."UD_CONTENT_ID";

        $is_deleted = 'N';
        $status = [2];
        $query = \Api\Models\Content::query();
        $query->where('is_deleted', '=', $is_deleted);
        $query->whereIn('status',  $status);
        $query->whereIn('ud_content_id', [2,3,4,5,7]);
        $query->join('BC_CONTENT_STATUS', "BC_CONTENT.CONTENT_ID" ,'=', "BC_CONTENT_STATUS.CONTENT_ID"  );
        $query->join('BC_USRMETA_CONTENT', "BC_CONTENT.CONTENT_ID", '=' ,"BC_USRMETA_CONTENT.USR_CONTENT_ID"  );

        $query->whereIn('BC_USRMETA_CONTENT.OTHBC_AT',  'Y');
        // $query->where(function ($query) {
        //     $query->where('BC_CONTENT.category_full_path', 'like', '/0/100/200%')
        //           ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/201%')
        //           ->orWhere('BC_CONTENT.category_full_path', 'like', '/0/100/205%');
        // });
        
        if( $categoryId ){
            $query->where('category_id', $categoryId );
        }
        if( $bsContentId ){
            $query->where('bs_content_id', $bsContentId );
        }
        
        $totalCount = $query->count();

        //$query->leftJoin('bc_content_status', 'bc_content.content_id', '=', 'bc_content_status.content_id');
        //$query->leftJoin('bc_usrmeta_content', 'bc_content.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        //$query->leftJoin('bc_sysmeta_movie', 'bc_content.content_id', '=', 'bc_sysmeta_movie.sys_content_id');
        $query->orderBy('bc_content.content_id','desc');
        
        dump('total : '.$totalCount );
 
         $page = 1;
         $perPage = 1500;
         $createdCount = 0;
      
         while (true) {
            //unset($records);
             dump(date("Y-m-d H:i:s").' - start'.$query->toSql());
             $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
             dump(date("Y-m-d H:i:s").' - end');
             if (count($records) === 0) {
                 dump('end');
                 break;
             }
             dump( 'page: '.$page );
             foreach ($records as $row => $record) {               
                 $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                 
                 dump($numCnt);  
                 if( !empty($start) && $start >= $numCnt ){
                    continue;
                }

                //unset($mig);
                //dump($numCnt);
                // $record->usrMeta = \Api\Models\ContentUsrMeta::find($record->content_id);
                // $record->sysMeta = \Api\Models\ContentSysMeta::find($record->content_id); 
                // $record->statusMeta = \Api\Models\ContentStatus::find($record->content_id); 

                // $record = $mapper->contentMapper( $record );

                
                $content = $contentService->getContentForPush($record->content_id);
                if($content){
                    $mig = new \Api\Models\ContentsMig();
                    $mig->id = $record->content_id;
                    $mig->content = json_encode($content['data'][0]);
                    $mig->save();
                }
             }
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
 
             $createdCount = $perPage * $page;
             $page++;
         }         
 
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
