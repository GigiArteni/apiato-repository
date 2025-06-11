<?php

namespace Apiato\Repository\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Apiato\Repository\Providers\RepositoryServiceProvider;

class RepositoryIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Manually load the repository config for integration tests
        $app['config']->set('repository', require __DIR__ . '/../../config/repository.php');
    }

    public function testServiceProviderIsRegistered()
    {
        $this->assertTrue($this->app->providerIsLoaded(RepositoryServiceProvider::class));
    }

    public function testConfigurationIsLoaded()
    {
        $this->assertNotNull(config('repository'));
        $this->assertIsArray(config('repository'));
    }

    // Add more integration tests here
}
