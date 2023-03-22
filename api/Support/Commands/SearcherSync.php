<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SearcherSync extends Command
{
    protected function configure()
    {
        $this->setName('searcher:sync')
            ->setDescription('Searcher Sync.')
            ->addOption('content', 'c', InputOption::VALUE_OPTIONAL, 'ContentId')
            ->addOption('udcontent', 'u', InputOption::VALUE_OPTIONAL, 'UdcontentId')
            ->addOption('isdel', 'd', InputOption::VALUE_OPTIONAL, 'isDelete')
            ->addOption('dir', 'r', InputOption::VALUE_OPTIONAL, 'dir');
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
    
        $contentId = $input->getOption('content');
        $udContentId = $input->getOption('udcontent');
        $isDel = Str::upper($input->getOption('isdel'));
        $dir = $input->getOption('dir');
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $search =  $container['searcher'];        
        $contentService = new \Api\Services\ContentService($container);

        $query = \Api\Models\Content::query();

        if( !empty($contentId) ){
            $query->where('content_id',  $contentId);
        }
        if( !empty($udContentId) ){
            $query->where('ud_content_id',  $udContentId);
        }
        if( !empty($isDel) ){
            $query->where('is_deleted',  $isDel);
        }
        $query->select('content_id','is_deleted');
        
        $totalCount = $query->count();

        //$query->leftJoin('bc_content_status', 'bc_content.content_id', '=', 'bc_content_status.content_id');
        //$query->leftJoin('bc_usrmeta_content', 'bc_content.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        //$query->leftJoin('bc_sysmeta_movie', 'bc_content.content_id', '=', 'bc_sysmeta_movie.sys_content_id');
        if ($dir == 1) {
            $query->orderBy('content_id', 'desc');
        }else{
            $query->orderBy('content_id', 'asc');
        }
        dump('total : '.$totalCount );
 
         $page = 1;
         $perPage = 500;
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

             $updateIds = [];
             $deleteIds = [];
             foreach ($records as $row => $record) {               
                 $numCnt = $row + 1 + ( ($page - 1) * $perPage );

                if( $record->is_deleted == 'Y'){
                    dump($totalCount.' / '.$numCnt.'] delete ]'. $record->content_id );
                    $search->delete($record->content_id);
                }else{
                    dump($totalCount.' / '.$numCnt.'] update ]'. $record->content_id );
                    //$search->update($record->content_id);
                    $updateIds [] = $record->content_id;
                }
             }
             dump(date("Y-m-d H:i:s").'] '.'update batch start');
             $search->update($updateIds);
             dump(date("Y-m-d H:i:s").'] '.'update batch end');
             dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
 
             $createdCount = $perPage * $page;
             $page++;
         }
 
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
