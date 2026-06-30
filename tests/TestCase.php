<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\SanctumServiceProvider;
use NathaI96\PropertyTenantAuth\PropertyTenantAuthServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Reset all authentication guards between requests.
     */
    protected function resetAuth(): void
    {
        auth()->guard()->forgetUser();
        auth()->forgetGuards();
    }

    /**
     * Set up the test environment and run all required migrations.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->artisan('migrate', ['--database' => 'testing'])->assertSuccessful();
    }

    /**
     * Get the package providers required for the test environment.
     */
    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            PropertyTenantAuthServiceProvider::class,
        ];
    }

    /**
     * Define the test environment configuration.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Resolve the configured Property model class.
     */
    protected function propertyModelClass(): string
    {
        return config('property-tenant-auth.models.property');
    }

    /**
     * Resolve the configured Tenant model class.
     */
    protected function tenantModelClass(): string
    {
        return config('property-tenant-auth.models.tenant');
    }
}
