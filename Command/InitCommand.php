<?php

namespace lmatte7\PlatformBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('platform:init')
            ->setDescription('Create the necessary files to make symfony work with platform');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $platform_params= 'app/config/parameters_platform.php';
      $symfony_config = 'app/config/config.yml';

      if(!file_exists($platform_params)) {
        $f = fopen($platform_params, 'w') or die ('Cannot open file: '. $file);
        $contents = <<<'EOF'
// app/config/parameters_platform.php
<?php
$relationships = getenv("PLATFORM_RELATIONSHIPS");
if (!$relationships) {
    return;
}

$relationships = json_decode(base64_decode($relationships), true);

foreach ($relationships['database'] as $endpoint) {
    if (empty($endpoint['query']['is_master'])) {
      continue;
    }

    $container->setParameter('database_driver', 'pdo_' . $endpoint['scheme']);
    $container->setParameter('database_host', $endpoint['host']);
    $container->setParameter('database_port', $endpoint['port']);
    $container->setParameter('database_name', $endpoint['path']);
    $container->setParameter('database_user', $endpoint['username']);
    $container->setParameter('database_password', $endpoint['password']);
    $container->setParameter('database_path', '');
}

# Store session into /tmp.
ini_set('session.save_path', '/tmp/sessions');
EOF;

        fwrite($f, $contents);
      }

      if(!exec("grep parameters_platform.php $symfony_config")) {
        $contents = explode("\n", file_get_contents($symfony_config));
        foreach ($contents as $key => $line) {
          $new_contents[] =  "$line\n";
          if(strpos($line, 'import') !== false) {
            $new_contents[] =  "\t- { resource: parameters_platform.php }\n";
          }
        }
        file_put_contents($symfony_config, $new_contents);
      }


    }
}
