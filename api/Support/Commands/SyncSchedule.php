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
class SyncSchedule extends Command
{

    protected function configure()
    {
        $this->setName('sync')
            ->setDescription('sync schedule.')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'type')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'limit');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        require_once __DIR__.'/../../../lib/config.php';
                
        dump( date("Y-m-d H:i:s").' - start');

        $settings = [
            'logging' => true,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings()
            ],
        ];
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $type = Str::upper($input->getOption('type'));
        $limit = $input->getOption('limit');
        if(empty($limit )){
            $limit = 10;
        }
        dump('type: '.$type);
           
        $apiJobService = new \Api\Services\ApiJobService($app->getContainer());


        switch($type)
        {
            //코드 동기화
            case 'CODE':
                //dump('DataDicCodeSetService'.' start');
                $apiJobService->excute('Api\Services\DataDicCodeSetService');
                //dump('DataDicCodeSetService'.' end');

                //dump('DataDicCodeItemService'.' start');
                $apiJobService->excute('Api\Services\DataDicCodeItemService');
                //dump('DataDicCodeItemService'.' end');
            break;

            //콘텐츠 동기화
            case 'CONTENT':
                $apiJobService->excute('Api\Services\ContentService',[],$limit);
            break;
            default:
                dump('not found type');
            break;
        }
 
        dump( date("Y-m-d H:i:s").' - '.' end.' );
    }
}
