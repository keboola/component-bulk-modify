<?php

declare(strict_types=1);

namespace Keboola\ComponentBulkModify\Tests;

use Keboola\ComponentBulkModify\AdminApiClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AdminApiClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $requiredEnvs = ['test_admin_token', 'test_api_url', 'test_app'];
        foreach ($requiredEnvs as $env) {
            if (empty(getenv($env))) {
                throw new RuntimeException(sprintf('Environment variable "%s" is empty.', $env));
            }
        }
    }

    public function testList(): void
    {
        $client = new AdminApiClient((string) getenv('test_api_url'), (string) getenv('test_admin_token'));
        $apps = $client->getAllAdminApps();
        self::assertArrayHasKey('name', $apps[0]);
        self::assertArrayHasKey('type', $apps[0]);
        self::assertArrayHasKey('id', $apps[0]);
        self::assertGreaterThan(100, count($apps));
    }

    public function testPatchApp(): void
    {
        $client = new AdminApiClient((string) getenv('test_api_url'), (string) getenv('test_admin_token'));
        $apps = $client->getAllAdminApps();
        $apps = array_filter($apps, function ($app) {
            return $app['id'] === (string) getenv('test_app');
        });
        $feature = uniqid('foo');
        self::assertCount(1, $apps);
        $app = end($apps);
        self::assertEquals((string) getenv('test_app'), $app['id']);
        $patchData = [
            'features' => [
                $feature,
            ],
        ];
        $data = $client->patchApp((string) getenv('test_app'), $patchData);
        $app['features'] = [$feature];
        unset($app['version']);
        unset($data['version']);
        unset($app['updatedOn']);
        unset($data['updatedOn']);
        self::assertEquals($app, $data);
    }
}
