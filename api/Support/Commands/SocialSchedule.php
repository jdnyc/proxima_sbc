<?php
namespace Api\Support\Commands;

use Api\Application;
use Api\Models\ApiJob;
use Api\Types\JobStatus;
use Api\Types\ApiJobType;
use Illuminate\Support\Str;
use Api\Modules\SocialClient;
use Api\Services\ApiJobService;
use Api\Services\SnsPostService;
use Api\Support\Helpers\UrlHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;


/**
 * Social API 관련 작업 처리 클래스
 */
class SocialSchedule extends Command
{

    protected function configure()
    {
        $this->setName('job:social')
            ->setDescription('sync schedule.')
            ->addOption('job', 'j', InputOption::VALUE_REQUIRED, '작업 유형(publish, sync)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        require __DIR__.'/../../../lib/bootstrap.php';

        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings()
            ],
        ];
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        $jobType = Str::lower($input->getOption('job'));

        $container = Application::container();
        $logger = $container->get('logger');
        $logger->info('Started Sns publish.');
        
        $style = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('fire', $style);
        $errorStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('error', $errorStyle);

        $output->writeln('<fire>[' . date("Y-m-d H:i:s") . '] Start social job: ' . $jobType . '.</fire>');

        if($jobType === 'publish') {
            $apiJobService = new ApiJobService(Application::container());
            $apiJobs = $apiJobService->list(ApiJobType::SNS_PUBLISH, [JobStatus::QUEUED]);

            foreach ($apiJobs as $apiJob) {
                // 조회 된 작업에서 대기중인 작업 Assigning으로 변경
                $apiJob = $apiJobService->assigning($apiJob);
                $payload = $apiJob->payload;
                $snsPublish = $payload['sns_publish'];
                $result = null;
                $parsedUrl = UrlHelper::parse($apiJob->url);
                $socialClient = new SocialClient($parsedUrl['base_url']);
                try {
                    $result = $socialClient->publish($snsPublish['platform'], 
                        $snsPublish['account'], $snsPublish['data'], 
                        '', '', 'ko', $snsPublish['callback'],
                        $parsedUrl['path']
                    );
                    if((string)$result === '"complete"') {
                        $apiJobService->working($apiJob);
                        $output->writeln('<fire>[' . date("Y-m-d H:i:s") . '] Publish request complete.</fire>');  
                    } else {
                        $apiJobService->failed($apiJob, (string)$result ?? '');      
                        $output->writeln('<error>[' . date("Y-m-d H:i:s") . '] Publish request error.</error>');       
                    }
                } catch(\Exception $e) {
                    $error = $e->getMessage();
                    $output->writeln('<error>[' . date("Y-m-d H:i:s") . '] Publish error: ' . $error . '</error>');                    
                    $apiJobService->failed($apiJob, $error);                    
                }
            }
        } else if($jobType === 'sync') {
            $snsPostService = new SnsPostService(Application::container());
            //$snsPostService->list()
        }

        $logger->info('Finished');
 
        $output->writeln('<fire>[' . date("Y-m-d H:i:s") . '] Finished social job: ' . $jobType . '.</fire>');
    }
}
