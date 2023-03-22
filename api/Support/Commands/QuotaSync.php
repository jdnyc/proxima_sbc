<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use \Api\Support\Helpers\DatabaseHelper;
use ProximaCustom\core\FolderAuthManager;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class QuotaSync extends Command
{
    protected function configure()
    {
        $this->setName('quota:sync')
            ->setDescription('Quota Sync.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type');
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
    
        $type = Str::upper($input->getOption('type'));
       
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();
     
        $folderMngService = new \Api\Services\FolderMngService($container);
        
        $storageFsname = config('auth_config')['fsname'];
        $linkage = config('auth_config')['linkage'] ; 
        $authOwner = config('auth_config')['auth_owner'];
        //$storageFsname = 'MAIN';
        $authMng = new FolderAuthManager([
            'storageFsname' => $storageFsname
        ]);

        //처리
        $quotaLists = $authMng->getQuotas();
        dump('quotaLists: '.count($quotaLists));
        $lists = $folderMngService->list([], $user);
        dump('lists: '.count($lists));
        foreach ($lists as $list) {

            $fullPath = $folderMngService->getFullPath($list->id);

            foreach ($quotaLists as $quotaList) {

                if ($quotaList['type'] == 'dir' && strstr($fullPath, $quotaList['name'] ) ) {
                    dump($fullPath);
                    $folder = $folderMngService->findOrFail($list->id);
                  
                    $folder->cursize          = $quotaList['curSize'];
                    $folder->status           = $quotaList['status'];
                    $folder->fs_type          = $quotaList['type'];
                    $folder->hardlimit_num    = $authMng::convNumber($quotaList['hardLimit']);
                    $folder->softlimit_num    = $authMng::convNumber($quotaList['softLimit']);
                    $folder->cursize_num      = $authMng::convNumber($quotaList['curSize']);

                    // if('/gemiso/Scratch/gemiso24' == $quotaList['name']){
                    //     dump( $list);
                    //     dd($quotaList);
                    // }

                    $folder->save();
                }
            }
        }
 
         dump( date("Y-m-d H:i:s").'] '.' 100% complete.' );
         die();
    }
}
