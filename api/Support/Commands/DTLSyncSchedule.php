<?php
namespace Api\Support\Commands;

use Api\Models\DivaTape;
use Illuminate\Support\Str;
use Api\Services\DivaApiService;
use \Api\Support\Helpers\DatabaseHelper;
use ProximaCustom\core\FolderAuthManager;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DTLSyncSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:dtl_sync')
            ->setDescription('DTL Sync.')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(3600);
        
        require_once __DIR__.'/../../../lib/config.php';

        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
    
        $type = Str::upper($input->getOption('type'));
       
        dump(date("Y-m-d H:i:s").' - start');

        $container = app()->getContainer();

        $divaApiService = new DivaApiService($container);
     
        $tapes = $divaApiService->getAllTapes();
   
        auth()->setUser('admin');
    
        foreach ($tapes as $row => $record) {
            //신규 및 업데이트
      
            $tape = DivaTape::findByTaId($record['id']);
            if(empty($tape)){
                $tape = new DivaTape();
               
            }
            $tape->logging = false;

            $tape->tape_se = 'diva';
            //소산여부
            if($record['status'] == 'OFFLINE'){
                $tape->disprs_at = 'Y';
            }else{
                $tape->disprs_at = 'N';
            }
            $tape->ta_id = $record['id'];
            $tape->ta_barcode= $record['barcode'];//->ta_barcode;
            $tape->ta_acs= $record['acs'];//->ta_acs;
            $tape->ta_lsm= $record['lsm'];//->ta_lsm;
            $tape->ta_media_type_tp_id= $record['mediaFormatId'];//->ta_media_type_tp_id;
            $tape->ta_set_id= $record['set'];//->ta_set_id;
            $tape->ta_is_online= $record['status'];//->ta_is_online;
            $tape->ta_protected= ($record['protected'] == true) ? 'Y': 'N';//->ta_protected;
            $tape->ta_enable_for_writing= ($record['writeEnabled']== true) ? 'Y': 'N';//->ta_enable_for_writing;
            $tape->ta_to_be_cleared= ($record['toBeCleared']== true) ? 'Y': 'N';//->ta_to_be_cleared;
            $tape->ta_enable_for_repack= ($record['repackEnabled']== true) ? 'Y': 'N';//->ta_enable_for_repack;
            $tape->ta_group= $record['group'];//->ta_group_tg_id;
            $tape->ta_remaining_size= $record['remainingSizeMB'];//->ta_remaining_size;
            $tape->ta_filling_ratio= $record['fillingRatio'];//->ta_filling_ratio;
            $tape->ta_fragmentation_ratio= $record['fragmentationRatio'];//->ta_fragmentation_ratio;
            $tape->ta_block_size= $record['blockSize'];//->ta_block_size;
            $tape->ta_last_written_block= $record['lastWrittenBlock'];//->ta_last_written_block;
            $tape->ta_format= $record['format'];//->ta_format;
            $tape->ta_eject_comment= $record['ejectComment'];//->ta_eject_comment;
            $tape->ta_last_archive_date= $divaApiService->dateArrayToDateString($record['lastArchiveDate']);//->ta_last_archive_date;
            $tape->ta_first_mount_date= $divaApiService->dateArrayToDateString($record['firstMountDate']);//->ta_first_mount_date;
            $tape->ta_last_retention_date= $divaApiService->dateArrayToDateString($record['lastRetentionDate']);//->ta_last_retention_date;
            $tape->ta_first_insertion_date= $divaApiService->dateArrayToDateString($record['firstInsertionDate']);//->ta_first_insertion_date;

            $tape->ta_media_type= $record['mediaType'];//->mediaType;
            $tape->ta_total_size= $record['totalSizeMB'];//->totalSizeMB;
            //$tape->ta_export_tape = $record['id'];//->ta_export_tape;
            $tape->save();
        }

        $numCnt = count($tapes);
 
         dd( date("Y-m-d H:i:s").'] '.' 100% complete.' );
        
    }
}
