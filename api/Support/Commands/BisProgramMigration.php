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

use Api\Models\BisProgram;
use Api\Models\BisScpgmmst;
class BisProgramMigration extends Command
{
    protected function configure(){
        $this->setName('bis:program');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        require_once __DIR__.'/../../../lib/config.php';
        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis')
            ],
        ];       
        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        $bisPrograms = BisProgram::get();

        dump('------------------------------------------------------');
        dump('bis DB scpgmmst 테이블 카운터['.BisProgram::count().']');
        dump('cms DB bis_scpgmmst 테이블 카운터['.BisScpgmmst::count().']');
        dump('------------------------------------------------------');

        
        dump('['.date("Y-m-d H:i:s").'] '.'update batch start');
        $i=0;
        foreach($bisPrograms as $bisProgram){
            $pgmId = $bisProgram->pgm_id;
            $isBisProgram = BisScpgmmst::find($pgmId);

            if(is_null($isBisProgram)){
                ++$i;
                $record = BisProgram::find($pgmId);
                
                $scpgmmst = new BisScpgmmst();  
                
                
                
                foreach($record->toArray() as $key => $value){
                    $scpgmmst[$key] = $value;
                }
                
                $scpgmmst->save();
                dump($i.' => pgm_id['.$pgmId.']');

            }
            
        }
        dump('['.date("Y-m-d H:i:s").'] '.'update batch end');
        dump('update count['.$i.']');
        if($i == 0){
            dump('업데이트된 데이터가 없습니다.');
        }
    }
}