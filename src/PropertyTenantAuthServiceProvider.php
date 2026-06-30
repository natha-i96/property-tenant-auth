<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use NathaI96\PropertyTenantAuth\Console\Commands\CreatePropertyCommand;
use NathaI96\PropertyTenantAuth\Http\Middleware\EnsureTokenableActive;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

class PropertyTenantAuthServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/property-tenant-auth.php',
            'property-tenant-auth'
        );
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->configureMorphMap();
        $this->registerRoutes();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerCommands();
        $this->registerMiddlewareAliases();
        $this->registerPublishing();
    }

    /**
     * Use short aliases for the polymorphic tokenable types so tokens stay
     * resolvable when host applications extend the package models.
     */
    protected function configureMorphMap(): void
    {
        $propertyClass = $this->modelClass('property', Property::class);
        $tenantClass = $this->modelClass('tenant', Tenant::class);

        Relation::morphMap([
            'property' => $propertyClass,
            'tenant' => $tenantClass,
        ], true);
    }

    /**
     * Register the package routes if enabled.
     */
    protected function registerRoutes(): void
    {
        if (! config('property-tenant-auth.routes.enabled', true)) {
            return;
        }

        $router = $this->app['router'];

        $this->registerRouteBindings($router);

        $router->group($this->routeConfiguration(), function () {
            require __DIR__.'/../routes/api.php';
        });
    }

    /**
     * Bind route parameters to the configured model classes so implicit model
     * resolution returns the host application's extended models.
     */
    protected function registerRouteBindings(Router $router): void
    {
        $router->bind('tenant', function (string $value): Tenant {
            $class = config('property-tenant-auth.models.tenant', Tenant::class);
            $property = request()?->user();

            if (! $property instanceof Property) {
                abort(404);
            }

            return $property->tenants()->where((new $class)->getRouteKeyName(), $value)->firstOrFail();
        });
    }

    /**
     * Build the route group configuration from config.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('property-tenant-auth.routes.prefix', 'api'),
            'middleware' => config('property-tenant-auth.routes.middleware', ['api']),
        ];
    }

    /**
     * Register the artisan command.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreatePropertyCommand::class,
            ]);
        }
    }

    /**
     * Register middleware aliases used by the package routes.
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('abilities', CheckAbilities::class);
        $router->aliasMiddleware('ability', CheckForAnyAbility::class);
        $router->aliasMiddleware('tokenable.active', EnsureTokenableActive::class);
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/property-tenant-auth.php' => config_path('property-tenant-auth.php'),
        ], 'property-tenant-auth-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'property-tenant-auth-migrations');
    }

    /**
     * Resolve a model class from config with a fallback default.
     */
    protected function modelClass(string $key, string $default): string
    {
        return config("property-tenant-auth.models.{$key}") ?: $default;
    }
}
