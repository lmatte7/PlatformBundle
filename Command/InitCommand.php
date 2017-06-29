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
            ->setDescription('Create the necessary files to make symfony work with platform.sh');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $platform_params= 'app/config/parameters_platform.php';
      $symfony_config = 'app/config/config.yml';

      $platform_app_yaml = '.platform.app.yaml';
      $platform_routes = '.platform/routes.yaml';
      $platform_services = '.platform/services.yaml';

      if(!file_exists($platform_params)) {
        $f = fopen($platform_params, 'w') or die ('Cannot open file: '. $platform_params);
        $contents = <<<'EOF'
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

      if(!file_exists($platform_app_yaml)) {
        $f = fopen($platform_app_yaml, 'w') or die ('Cannnot open file: '.$platform_app_yaml);
        $contents = <<<'EOF'
        
# This file describes an application. You can have multiple applications
# in the same project.

# The name of this app. Must be unique within a project.
name: app

# The type of the application to build.
type: php:7.0
build:
    flavor: composer

variables:
    env:
        # Tell Symfony to always install in production-mode.
        SYMFONY_ENV: 'prod'

# The hooks that will be performed when the package is deployed.
hooks:
    build: |
        rm web/app_dev.php
        bin/console --env=prod assets:install --no-debug
    deploy: |
        bin/console --env=prod cache:clear

# The relationships of the application with services or other applications.
# The left-hand side is the name of the relationship as it will be exposed
# to the application in the PLATFORM_RELATIONSHIPS variable. The right-hand
# side is in the form `<service name>:<endpoint name>`.
relationships:
    database: "mysqldb:mysql"

# The size of the persistent disk of the application (in MB).
disk: 2048

# The mounts that will be performed when the package is deployed.
mounts:
    "/var/cache": "shared:files/cache"
    "/var/logs": "shared:files/logs"
    "/var/sessions": "shared:files/sessions"

# The configuration of app when it is exposed to the web.
web:
    locations:
        "/":
            # The public directory of the app, relative to its root.
            root: "web"
            # The front-controller script to send non-static requests to.
            passthru: "/app.php"
EOF;

        fwrite($f, $contents);
      }

      if(!exec('mkdir .platform')) {
        if(!file_exists($platform_routes)) {
          $f = fopen($platform_routes, 'w') or die ('Cannnot open file: ' . $platform_app_yaml);
          $contents =<<<'EOF'
# The routes of the project.
#
# Each route describes how an incoming URL is going
# to be processed by Platform.sh.

"https://{default}/":
    type: upstream
    upstream: "app:http"

"https://www.{default}/":
    type: redirect
    to: "https://{default}/"
EOF;

          fwrite($f, $contents);
        }
        if(!file_exists($platform_services)) {
          $f = fopen($platform_services, 'w') or die ('Cannnot open file: ' . $platform_app_yaml);
          $contents =<<<'EOF'
mysqldb:
    type: mysql:10.0
    disk: 2048
EOF;

          fwrite($f, $contents);
        }
      }

      if(!exec("grep parameters_platform.php $symfony_config")) {
        $contents = explode("\n", file_get_contents($symfony_config));
        foreach ($contents as $key => $line) {
          $new_contents[] =  "$line\n";
          if(strpos($line, 'import') !== false) {
            $new_contents[] =  "    - { resource: parameters_platform.php }\n";
          }
        }
        file_put_contents($symfony_config, $new_contents);
      }


    }
}
