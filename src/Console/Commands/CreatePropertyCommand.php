<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NathaI96\PropertyTenantAuth\Models\Property;

class CreatePropertyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property:create
                            {property_no : Unique property identifier (max 50 chars)}
                            {--name= : Human-readable property name}
                            {--expires= : Optional expiration datetime}
                            {--inactive : Create as inactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap a property and emit its API token once.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $propertyNo = (string) $this->argument('property_no');

        if (! $this->isValidPropertyNo($propertyNo)) {
            $this->error('Property number must be 1-50 characters and contain only letters, numbers, hyphens, underscores, or dots.');

            return self::FAILURE;
        }

        $propertyClass = $this->propertyModelClass();

        if ($propertyClass::where('property_no', $propertyNo)->exists()) {
            $this->error("Property [{$propertyNo}] already exists.");

            return self::FAILURE;
        }

        $expiresAt = $this->resolveExpiresAt();
        if ($expiresAt === false) {
            return self::FAILURE;
        }

        try {
            [$property, $token] = DB::transaction(function () use ($propertyClass, $propertyNo, $expiresAt): array {
                $property = $propertyClass::create([
                    'uuid' => (string) Str::orderedUuid(),
                    'property_no' => $propertyNo,
                    'name' => $this->option('name'),
                    'is_active' => ! $this->option('inactive'),
                    'expires_at' => $expiresAt,
                ]);

                $token = $this->createPropertyToken($property, $expiresAt);

                return [$property, $token];
            });
        } catch (\Throwable $e) {
            $this->error('Failed to create property: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->outputSummary($property, $token);

        return self::SUCCESS;
    }

    /**
     * Resolve the optional --expires value.
     */
    protected function resolveExpiresAt(): Carbon|false|null
    {
        if (! $this->option('expires')) {
            return null;
        }

        try {
            return Carbon::parse($this->option('expires'));
        } catch (\Exception $e) {
            $this->error('Invalid --expires value. Use a valid datetime string.');

            return false;
        }
    }

    /**
     * Create the property API token.
     */
    protected function createPropertyToken(Property $property, ?Carbon $expiresAt): string
    {
        return $property->createToken(
            'property',
            config('property-tenant-auth.tokens.property', ['property', 'issue:tenant-token']),
            $expiresAt
        )->plainTextToken;
    }

    /**
     * Print the property details and token to the console.
     */
    protected function outputSummary(Property $property, string $token): void
    {
        $this->info('Property created successfully.');
        $this->newLine();
        $this->line("  property_no : {$property->property_no}");
        $this->line('  name      : '.($property->name ?? 'n/a'));
        $this->line('  active    : '.($property->is_active ? 'yes' : 'no'));
        $this->line('  expires   : '.($property->expires_at?->toDateTimeString() ?? 'never'));
        $this->newLine();
        $this->warn('  Store this token immediately — it will not be shown again.');
        $this->line("  token     : {$token}");
        $this->newLine();
        $this->line('  Example:');
        $this->line("    curl -H 'Authorization: Bearer {$token}' {$this->exampleUrl()}");
        $this->newLine();
    }

    /**
     * Build the example curl URL using the configured route prefix.
     */
    protected function exampleUrl(): string
    {
        $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $prefix = config('property-tenant-auth.routes.prefix', 'api');

        return "{$appUrl}/{$prefix}/v1/me";
    }

    /**
     * Resolve the property model class from config.
     */
    protected function propertyModelClass(): string
    {
        return config('property-tenant-auth.models.property', Property::class);
    }

    /**
     * Validate the property number format.
     */
    protected function isValidPropertyNo(string $propertyNo): bool
    {
        return preg_match('/^[A-Za-z0-9._-]{1,50}$/', $propertyNo) === 1;
    }
}
