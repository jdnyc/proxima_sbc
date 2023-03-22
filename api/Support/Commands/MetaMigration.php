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
class MetaMigration extends Command
{

    protected function configure()
    {
        $this->setName('mig:meta')
            ->setDescription('meta MetaMigration.')
            ->addOption('code', 'c', InputOption::VALUE_REQUIRED, 'code')
            ->addOption('before', 'b', InputOption::VALUE_REQUIRED, 'before')
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
        $code = $input->getOption('code');
        $before = $input->getOption('before');
        $after = $input->getOption('after');

        $user = new \Api\Models\User();
        $user->user_id ='admin';

        if($code == 'instt'){
           // SELECT instt FROM BC_CONTENT c JOIN BC_USRMETA_CONTENT uc ON c.CONTENT_ID=uc.USR_CONTENT_ID WHERE c.IS_DELETED='N' AND uc.INSTT LIKE '%c1352159%';
            //--c1352159 => c1790387
            $query = DB::table('BC_CONTENT as c')
            ->join('BC_USRMETA_CONTENT as m', 'c.content_id', '=', 'm.usr_content_id')
            ->where("c.IS_DELETED",'N')
            ->where("m.INSTT",  'like', "%{$before}%" )         
            ->selectRaw("c.content_id,c.status,c.is_deleted,c.title,m.{$code}")->orderBy('content_id');

            $totalCount = $query->count();
            // dd($query->toSql());
            dump('total : '.$totalCount );
            $container['logger']->info('total : '.$totalCount);
            //$start = 0;
            $page = 1;
            $perPage = 1000;
            $createdCount = 0;
            while (true) {
                $records = $query->simplePaginate($perPage, ['*'], 'page', $page);
    
                if (count($records) === 0) {
                    dump($end);
                    break;
                }
                dump( 'page: '.$page );
            
                foreach ($records as $row => $record) {              
                    $numCnt = $row + 1 + ( ($page - 1) * $perPage );
                                  
                    $contentId = $record->content_id;
                 
                    dump($numCnt.':'.$contentId);   
                    dump('instt:'.$record->instt);    

                    $newMeta = str_replace($before, $after, $record->instt);
                    $usrMeta = [
                        'instt' => $newMeta
                    ];
                    dump( $usrMeta);    
                    $content = $contentService->updateUsingArray($contentId, [],[],[],$usrMeta , $user);
            
                    // $method = 'post';
                    // $bfUrl ='http://118.42.70.246:8080/ap/restful/contents'; 
    
                    // $urlInfo = parse_url($bfUrl);
                    // $baseUrl = $urlInfo['scheme'] .'://'.$urlInfo['host'];
                    // if( $urlInfo['port'] ){
                    //     $baseUrl = $baseUrl .':'.$urlInfo['port'];
                    // }
                    // $url = $urlInfo['path'];
                    // $params = [];
                    // if( $urlInfo['query'] ){
                    //     $params = $urlInfo['query'];
                    // }
                    // $options = [
                    //     'headers' => ['Content-type'=>'application/json']
                    // ];
    
                    // $contentMap = $contentService->getContentForPush($contentId);
                    // if ($contentMap) {
                    //     $options['body'] = json_encode($contentMap);
                    //     $client = new \Api\Core\HttpClient($baseUrl);
                    //     $container['logger']->info(json_encode($contentMap));
                    //     //dump( date("Y-m-d H:i:s").' - '.json_encode($contentMap) );
                    //     $result = $client->request($method, $url, $params, $options);
                    //     dump('contentId: '.$contentId);
                    // }
                }
                dump(  date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.' );
                //$container['logger']->info(date("Y-m-d H:i:s").'] '.$createdCount . ' records created...' . $createdCount/$totalCount*100 . '% complete.');
                $createdCount = $perPage * $page;
                $page++;
            }   
         
        }
       
        //dump( date("Y-m-d H:i:s").' - '.print_r($result,true) );
        dump( date("Y-m-d H:i:s").' - '.' end.' );
    }
}
