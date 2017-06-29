<?php

namespace lmatte7\PlatformBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDatabaseCommand extends ContainerAwareCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('platform:sync_db')
      ->setDescription('Sync databases to or from platform.sh')
      ->addOption('direction',
        'd',
        InputOption::VALUE_OPTIONAL,
        'The direction to sync data. Options are to and from. Option defaults to from')
      ->addOption('source-environment',
        's',
        InputOption::VALUE_OPTIONAL,
        'The environment to sync data with. Defaults to current environment');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $db_host = $this->getContainer()->getParameter('database_host');
    $db_port = $this->getContainer()->getParameter('database_port');
    $db_name = $this->getContainer()->getParameter('database_name');
    $db_user = $this->getContainer()->getParameter('database_user');
    $db_pass = $this->getContainer()->getParameter('database_password');

    $env = $input->getOption('source-environment');
    $direction = $input->getOption('direction');

    if ($direction == 'to') {
      $output->writeln("-----------------------------Exporting Local Data------------------------------");

      if ($db_port == null) {
        $command = ('mysqldump -u' . $db_user . ' -p' . $db_pass . ' -h' . $db_host . ' ' . $db_name . ' > symfony_dump.sql');
      } else {
        $command = ('mysqldump -u ' . $db_user . ' -p ' . $db_pass . ' -h ' . $db_host . ' --port ' . $db_port . ' ' . $db_name . ' > symfony_dump.sql');
      }

      fwrite(STDOUT, passthru($command));
      $output->writeln("-----------------------------Sending Data-----------------------------------");
      fwrite(STDOUT, passthru('platform db:sql < symfony_dump.sql'));

      if (file_exists('symfony_dump.sql')) {
        unlink('symfony_dump.sql');
      }

    } else {
      $output->writeln("-----------------------------Retrieving data----------------------------------");

      if ($env) {
        fwrite(STDOUT, passthru("platform db:dump -f symfony_dump.sql -y --environment $env"));
      } else {
        fwrite(STDOUT, passthru("platform db:dump -f symfony_dump.sql -y"));
      }

      if ($db_port == null) {
        $command = ('mysql -u' . $db_user . ' -p' . $db_pass . ' -D' . $db_name . ' -h' . $db_host . ' < symfony_dump.sql');
      } else {
        $command = ('mysql -u ' . $db_user . ' -p ' . $db_pass . ' -D ' . $db_name . ' -h ' . $db_host . ' --port ' . $db_port . ' < symfony_dump.sql');
      }

      $output->writeln("-----------------------------Importing data-----------------------------------");
      fwrite(STDOUT, passthru($command));

      if (file_exists('symfony_dump.sql')) {
        unlink('symfony_dump.sql');
      }

    }
  }
}
