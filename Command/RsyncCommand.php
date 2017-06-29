<?php

namespace lmatte7\PlatformBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RsyncCommand extends ContainerAwareCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('platform:rsync')
      ->setDescription('This command will sync data to or from the platform.sh server')
      ->addOption('direction',
        'd',
        InputOption::VALUE_REQUIRED,
        'The direction to rsync. Options are "to" or "from" production.')
      ->addOption('directory',
        'f',
        InputOption::VALUE_OPTIONAL,
        'The directory to sync with no starting slash, leave blank to sync the entire web directory')
      ->addOption('source-environment',
        's',
        InputOption::VALUE_OPTIONAL,
        'The environment to sync files with. Defaults to current environment');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $directory = $input->getOption('directory');
    $env = $input->getOption('source-environment');

    if($env) {
      $project_id = exec("platform environment:info -e $env  | grep project | cut -d\| -f3");
      $machine_name = exec("platform environment:info -e $env  | grep machine_name | cut -d\| -f3");
      $machine_name = str_replace(' ', '', $machine_name);
      $ssh_addr = $project_id . '-' . $machine_name . '@ssh.us.platform.sh';
    } else {
      $project_id = exec('platform environment:info  | grep project | cut -d\| -f3');
      $machine_name = exec('platform environment:info  | grep machine_name | cut -d\| -f3');
      $machine_name = str_replace(' ', '', $machine_name);
      $ssh_addr = $project_id . '-' . $machine_name . '@ssh.us.platform.sh';
    }



    if ($input->getOption('direction') == 'to') {
      $output->writeln('-----------------------------Syncing data TO----------------------------------------');
      fwrite(STDOUT, passthru("rsync -r web/$directory " . $ssh_addr . ":web/ --progress"));
    } elseif ($input->getOption('direction') == 'from') {
      $output->writeln('-----------------------------Syncing data FROM--------------------------------------');
      fwrite(STDOUT, passthru("rsync -r " . $ssh_addr . ":web/$directory web/ --progress"));
    } else {
      $output->writeln('Error! Direction argument not provided. Use "to" or "from".');
    }


  }
}
