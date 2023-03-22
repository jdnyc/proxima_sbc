<?php

namespace Api\Support\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle as OutputFormatterStyle;

class JobSchedule extends Command
{
    protected function configure()
    {
        $this->setName('job')
            ->setDescription('Job scheduling daemon.');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('fire', $style);
        $output->writeln('<fire>Job Scheduler started.</fire>');
        $path = dirname(__DIR__) . '/Jobs/job_schedule.php';
        $output->writeln('<fire>File path is ' . $path . '</fire>');
        passthru(PHP_BINARY . ' ' . $path);
    }
}
