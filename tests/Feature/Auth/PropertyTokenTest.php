<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Feature\Auth;

use NathaI96\PropertyTenantAuth\Tests\TestCase;

class PropertyTokenTest extends TestCase
{
    public function test_property_token_can_access_me_endpoint(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create([
            'property_no' => 'P001',
            'is_active' => true,
        ]);
        $token = $property->createToken('property', ['property'])->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/me');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'property',
                'property_no' => 'P001',
            ])
            ->assertJsonPath('abilities', ['property']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        // Act
        $response = $this->getJson('/api/v1/me');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_invalid_token_returns_401(): void
    {
        // Act
        $response = $this->withToken('invalid-token')->getJson('/api/v1/me');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_inactive_property_token_returns_403(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create([
            'property_no' => 'P002',
            'is_active' => false,
        ]);
        $token = $property->createToken('property', ['property'])->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/me');

        // Assert
        $response->assertForbidden();
    }

    public function test_property_token_requires_ability_property(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P003']);
        $token = $property->createToken('property', ['other'])->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/me');

        // Assert
        $response->assertForbidden();
    }
}
