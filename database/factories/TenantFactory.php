<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Models\Tenant;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'tenant_no' => 'T'.fake()->unique()->numberBetween(100, 999),
            'role' => 'tenant',
            'is_active' => true,
            'expires_at' => null,
        ];
    }
}
