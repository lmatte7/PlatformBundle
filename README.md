# PlatformBundle
A Symfony bundle to add commands that can be used with Platform.sh  
_Note: This bundle is still under development towards a version 1. It is highly reccomended to have a version control system in place prior to running any commands_

## Installation  
Add the following to your composer.json file.

```
"require": {
    ...
    "lmatte7/PlatformBundle": "0.4"
},
```

Add the following to your AppKernel.php

```
public function registerBundles()
    {
        $bundles = [
            ...
            new lmatte7\PlatformBundle\lmatte7PlatformBundle(),
        ];

        ...
    }
```

Then run `composer update`

## Available Commands

There are three commands available:

`rsync`: Sync files from or to your platform.sh environments. Will only sync files in /web

`sync_db`: Sync databases from or to your platform.sh environments

`init`: Set up the necessary files to create a platform.sh project


## Rsync
```
Usage:
  platform:rsync [options]

Options:
  -d, --direction=DIRECTION                      The direction to rsync. Options are "to" or "from" production.
  -f, --directory[=DIRECTORY]                    The directory to sync with no starting slash, leave blank to sync the entire web directory
  -s, --source-environment[=SOURCE-ENVIRONMENT]  The environment to sync files with. Defaults to current environment
  -h, --help                                     Display this help message
```

### Example:
To sync the local web directory with your current platform.sh environment  
`bin/console platform:rsync -d from`  

To sync the platform.sh master environment web directory with your current web directory  
`bin/console platform:rsync -d to -s master`  

To sync only the local /web/upload directory with the master platform.sh env /web/upload  
`bin/console platform:rsync -d to -f upload -s master`  

## Sync DB
```
Usage:
     platform:sync_db [options]
   
   Options:
     -d, --direction[=DIRECTION]                    The direction to sync data. Options are to and from. Option defaults to from
     -s, --source-environment[=SOURCE-ENVIRONMENT]  The environment to sync data with. Defaults to current environment
     -h, --help                                     Display this help message
```

### Example:
To sync the local database with your current platform.sh environment database
`bin/console platform:sync_db -d from`  

To sync the platform.sh master environment database with your local database
`bin/console platform:rsync -d from -s master`  

## Platform Init
```
Usage:
     platform:init
   
   Options:
     -h, --help            Display this help message
```  

_Creates default config files needed to create a platform.sh project. It is highly recommended these are reviewed before pushing to a live server_ 
