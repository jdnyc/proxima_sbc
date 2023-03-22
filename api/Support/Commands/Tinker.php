<?php
namespace Api\Support\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Tinker extends Command
{
    protected function configure()
    {
        $this->setName('tinker')
            ->setDescription('Interact with your application');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = [
            'logging' => true,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
            ],
        ];

        $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);

        $configFile = dirname(dirname(dirname(__DIR__))).'/bin/psysh.config.php';
        $sh = new \Psy\Shell(new \Psy\Configuration(['configFile' => $configFile]));
        $sh->setScopeVariables(get_defined_vars());
        $sh->run();
    }
}
