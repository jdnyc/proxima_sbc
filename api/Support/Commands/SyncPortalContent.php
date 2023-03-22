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


/**
 * 포털쪽 코드 배포를 휘한 스케줄 커맨드
 */
class SyncPortalContent extends Command
{

    protected function configure()
    {
        $this->setName('sync:portal')
            ->setDescription('sync schedule.')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'contentid')
            ->addOption('start', 's', InputOption::VALUE_REQUIRED, 'start')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'limit')
            ->addOption('after', 'a', InputOption::VALUE_REQUIRED, 'after');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';
                
        dump( date("Y-m-d H:i:s").' - start');

        $settings = [
            'logging' => false,
            'connections' => [
                [
                    'name' => 'default',
                    'driver' => 'oracle',
                    'host' => '10.10.50.110',
                    'database' => 'cms',
                    'username' => 'ktvcms',
                    'password' => 'xhdqkd67890#',
                    'port' => '1521',
                    'charset' => 'AL32UTF8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' => '',
                    'server_version' => '11g'
                ]
            ],
        ];
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        
        $container = app()->getContainer();
        $contentService = new \Api\Services\ContentService($container);
        //$type = Str::upper($input->getOption('id'));
        $contentId = $input->getOption('id');
        $start = $input->getOption('start');
        $limit = $input->getOption('limit');
        $after = $input->getOption('after');
//SELECT * FROM bc_content WHERE CATEGORY_ID !=202 AND CATEGORY_ID !=203 AND CATEGORY_ID !=204 AND IS_DELETED='N' AND status=2 AND UD_CONTENT_ID IN (2,3,4,5,6) 
        $query = DB::table('bc_content')
            ->whereRaw("CATEGORY_ID !=202 AND CATEGORY_ID !=203 AND CATEGORY_ID !=204 AND status=2 AND UD_CONTENT_ID IN (2) ")          
            ->selectRaw("content_id")->orderBy('content_id');


        $totalCount = $query->count();

       // dd($query->toSql());

        dump('total : '.$totalCount );
        $container['logger']->info('total : '.$totalCount);
        //$start = 0;
        $page = 1;
        $perPage = 1000;
        $createdCount = 0;
        while (true) {
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page)->all();

            if (count($records) === 0) {
                dump($end);
                break;
            }
            dump( 'page: '.$page );
          
            foreach ($records as $row => $record) {              
                $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                dump($numCnt);

                if ( !empty($start) && ( $start > $numCnt ) ) {
                    continue;
                }
                if ( !empty($limit) && ( ($numCnt - $start) > $limit ) ) {
                    die();
                }
               
                $contentId = $record->content_id;

                if( !empty($after) ){
                    if( $after > $contentId){
                        continue;
                    }
                }
     
                $method = 'post';
                $bfUrl ='http://118.42.70.246:8080/ap/restful/contents'; 

                $urlInfo = parse_url($bfUrl);
                $baseUrl = $urlInfo['scheme'] .'://'.$urlInfo['host'];
                if( $urlInfo['port'] ){
                    $baseUrl = $baseUrl .':'.$urlInfo['port'];
                }
                $url = $urlInfo['path'];
                $params = [];
                if( $urlInfo['query'] ){
                    $params = $urlInfo['query'];
                }
                $options = [
                    'headers' => ['Content-type'=>'application/json']
                ];

                $contentMap = $contentService->getContentForPush($contentId);
                
                $options['body'] = json_encode($contentMap);
                                
                $client = new \Api\Core\HttpClient($baseUrl);
                $container['logger']->info(json_encode($contentMap));
                //dump( date("Y-m-d H:i:s").' - '.json_encode($contentMap) );
                $result = $client->request($method, $url, $params, $options);
                dump('contentId: '.$contentId);
            }
            dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
            //$container['logger']->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
            $createdCount = $perPage * $page;
            $page++;
        }   
        

        //dump( date("Y-m-d H:i:s").' - '.print_r($result,true) );
        dump( date("Y-m-d H:i:s").' - '.' end.' );
    }
}
