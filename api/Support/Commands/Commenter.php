<?php
namespace Api\Support\Commands;

use Illuminate\Support\Str;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Commenter extends Command
{
    protected function configure()
    {
        $this->setName('commenter')
            ->setDescription('Get database column comment.')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'Table name');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = [
            'logging' => false,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        $table = Str::upper($input->getOption('table'));
        $query = "SELECT table_name, column_name, comments 
                FROM USER_COL_COMMENTS 
                WHERE comments IS NOT NULL
                    AND TABLE_NAME='{$table}'";
        $comments = DB::table('user_col_comments')
                        ->whereNotNull('comments')
                        ->where('table_name', $table)
                        ->select('table_name', 'column_name', 'comments')
                        ->get();
        
        if(empty($comments)) {
            return;
        }

        $columns = DB::table('all_tab_columns')
                    ->where('table_name', $table)
                    ->select('column_name', 'data_type')
                    ->get();
        
        $dataTypeMap = [];
        foreach($columns as $column) {
            $type = '';
            if($column->data_type == 'NUMBER') {
                $type = 'int';
            } else {
                $type = 'string';
            }
            $dataTypeMap[$column->column_name] = $type;
        }

        foreach($comments as $comment) {
            $colName = $comment->column_name;
            $type = $dataTypeMap[$colName];
            $msg = " * @property {$type} \${$colName} {$comment->comments}";
            $output->writeln($msg);            
        }
    }
}
