<?php

return [
    'storage_adapter' => 's3',
    'database_adapter' => 'shell',
    'filesystem_adapter' => 'simple',

    'commands' => [
        'Meanbee\Magedbm2\Command\ConfigureCommand',
        'Meanbee\Magedbm2\Command\GetCommand',
        'Meanbee\Magedbm2\Command\LsCommand',
        'Meanbee\Magedbm2\Command\PutCommand',
        'Meanbee\Magedbm2\Command\RmCommand',
        'Meanbee\Magedbm2\Command\ExportCommand',
        'Meanbee\Magedbm2\Command\ImportCommand',
        'Meanbee\Magedbm2\Command\ViewConfigurationCommand',
    ],

    'command_instances' => function (\DI\Container $c) {
        $commands = [];

        foreach ($c->get('commands') as $command) {
            $commands[] = $c->make($command);
        }

        foreach ($commands as $command) {
            if ($command instanceof \Psr\Log\LoggerAwareInterface) {
                $command->setLogger($c->get('logger'));
            }
        }

        return $commands;
    },

    'logger' => DI\create(\Symfony\Component\Console\Logger\ConsoleLogger::class),

    \Meanbee\Magedbm2\Service\FilesystemInterface::class => \DI\factory([
        \Meanbee\Magedbm2\Service\FilesystemFactory::class,
        'create'
    ]),

    \Meanbee\Magedbm2\Service\StorageInterface::class => \DI\factory([
        \Meanbee\Magedbm2\Service\StorageFactory::class,
        'create'
    ]),

    \Meanbee\Magedbm2\Service\DatabaseInterface::class => \DI\factory([
        \Meanbee\Magedbm2\Service\DatabaseFactory::class,
        'create'
    ]),

    \Meanbee\Magedbm2\Application\ConfigInterface::class => \DI\get('config'),

    'config_file.dist' => function (\Meanbee\Magedbm2\Application\ConfigFileResolver $resolver) {
        return $resolver->getDistFilePath();
    },

    'config' => function (\DI\Container $c) {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $c->get('logger');

        $files = [
            $c->get('config_file.dist')
        ];

        $config = new \Meanbee\Magedbm2\Application\Config();

        $config->setLogger($logger);

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $logger->info(sprintf('Did not load config from file %s - did not exist', $file));
                continue;
            }

            if (!is_readable($file)) {
                $logger->info(sprintf('Did not load config from file %s - was unreadable', $file));
                continue;
            }

            $logger->info(sprintf('Loading config from file at %s', $file));
            $config->merge(
                (new \Meanbee\Magedbm2\Application\ConfigLoader\FileLoader($file))->asConfig()
            );
        }

        return $config;
    },
];
