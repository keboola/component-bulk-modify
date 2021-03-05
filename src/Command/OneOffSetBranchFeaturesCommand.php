<?php

declare(strict_types=1);

namespace Keboola\ComponentBulkModify\Command;

use Keboola\ComponentBulkModify\AdminApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OneOffSetBranchFeaturesCommand extends Command
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('app:set-branch-features');
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription('Update all apss to set flags related to branches')
            ->setHelp('See https://keboola.atlassian.net/browse/PS-1698')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Make actual changes, otherwise the changes are only printed out.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Using Url: ' . getenv('api_url'));
        $this->logger->info(sprintf('Using Token %s...', substr((string) getenv('api_token'), 0, 10)));
        $client = new AdminApiClient((string) getenv('api_url'), (string) getenv('api_token'), []);
        $force = (bool) $input->getOption('force');
        if ($force) {
            $this->logger->info('Changes will be made.');
        } else {
            $this->logger->info('Dry running, no changes will be made.');
        }
        $apps = $client->getAllAdminApps();
        $this->logger->info(sprintf('Processing %s apps.', count($apps)));
        foreach ($apps as $app) {
            $patch = SetBranchFeaturesHelper::getPatchData($app);
            if ($patch) {
                $this->logger->info(
                    sprintf('Patching app "%s" with data: %s', $app['id'], json_encode($patch))
                );
                if ($force) {
                    $this->logger->info(sprintf('Modifying app "%s" in developer portal.', $app['id']));
                    $client->patchApp($app['id'], $patch);
                }
            } else {
                $this->logger->info(sprintf('No changes to app "%s".', $app['id']));
            }
        }
        return 0;
    }
}
