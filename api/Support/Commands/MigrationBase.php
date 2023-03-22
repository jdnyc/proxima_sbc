<?php

namespace Api\Support\Commands;

use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

interface MigrationContract
{
    function dropTables();

    function migrate();

    function seed();
}

class MigrationBase extends Command implements MigrationContract
{
    protected $commandName;
    protected $commandDescription;
    protected $options;

    protected function configure()
    {
        if (empty($this->commandName)) {
            echo 'Command name is required.';
            return;
        }
        $this->setName($this->commandName)
            ->setDescription($this->commandDescription);
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if($input->hasOption('table')) {
            $this->options = [
                'table' => Str::lower($input->getOption('table'))
            ];
        }

        $settings = [
            'logging' => false, 
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings()
            ]
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        $style = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('fire', $style);
        $output->writeln('<fire>Start migration.</fire>');
        $this->dropTables();
        $this->migrate();
        $this->seed();
        $output->writeln('<fire>End migration.</fire>');
    }

    public function dropTables()
    { }

    public function migrate()
    { }

    protected function loadSeedData($filePath)
    {
        $migDataPath = dirname(__DIR__) . '/Migration/';
        $fileName =  $migDataPath . $filePath;
        return json_decode(file_get_contents($fileName), true);
    }

    public function seed()
    { }
}
