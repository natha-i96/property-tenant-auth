<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use NathaI96\PropertyTenantAuth\Models\Property;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::orderedUuid(),
            'property_no' => 'P'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->company(),
            'is_active' => true,
            'expires_at' => null,
        ];
    }
}
