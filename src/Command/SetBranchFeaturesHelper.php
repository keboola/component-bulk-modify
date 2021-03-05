<?php

declare(strict_types=1);

namespace Keboola\ComponentBulkModify\Command;

class SetBranchFeaturesHelper
{
    public static function getPatchData(array $app): array
    {
        $features = $app['features'] ?? [];
        if ($app['type'] === 'writer' || $app['type'] === 'application') {
            $features[] = 'dev-branch-configuration-unsafe';
        }
        if (!empty($app['forwardToken'])) {
            $features[] = 'dev-branch-job-blocked';
        }
        if ($features === $app['features']) {
            return [];
        } else {
            return ['features' => array_values(array_unique($features))];
        }
    }
}
