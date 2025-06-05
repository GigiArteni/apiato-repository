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

    public function testServiceProviderIsRegistered()
    {
        $this->assertTrue($this->app->providerIsLoaded(RepositoryServiceProvider::class));
    }

    public function testConfigurationIsLoaded()
    {
        $this->assertNotNull(config('repository'));
        $this->assertIsArray(config('repository.apiato'));
    }

    // Add more integration tests here
}
