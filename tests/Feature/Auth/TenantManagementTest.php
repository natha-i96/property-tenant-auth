<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Feature\Auth;

use NathaI96\PropertyTenantAuth\Tests\TestCase;

class TenantManagementTest extends TestCase
{
    public function test_property_can_list_its_tenants(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'PLST']);
        $tenantClass::factory()->count(2)->create(['property_id' => $property->id]);
        $token = $property->createToken('property', ['property'])->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/tenants');

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.property_no', 'PLST');
    }

    public function test_property_can_revoke_all_tenant_tokens(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'PREV']);
        $tenant = $tenantClass::factory()->create(['property_id' => $property->id]);
        $tenantToken = $tenant->createToken('tenant', ['tenant'])->plainTextToken;
        $propertyToken = $property->createToken('property', ['property'])->plainTextToken;

        // Act
        $response = $this->withToken($propertyToken)
            ->deleteJson('/api/v1/tenants/'.$tenant->id.'/tokens');

        // Assert
        $response->assertOk()
            ->assertJsonPath('success', true);
        $this->assertCount(0, $tenant->fresh()->tokens);

        // Tenant token should no longer authenticate
        $this->resetAuth();
        $this->withToken($tenantToken)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_property_cannot_revoke_tenant_tokens_from_other_property(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $propertyA = $propertyClass::factory()->create(['property_no' => 'PA']);
        $propertyB = $propertyClass::factory()->create(['property_no' => 'PB']);
        $tenantB = $tenantClass::factory()->create(['property_id' => $propertyB->id]);
        $tokenA = $propertyA->createToken('property', ['property'])->plainTextToken;

        // Act
        $response = $this->withToken($tokenA)
            ->deleteJson('/api/v1/tenants/'.$tenantB->id.'/tokens');

        // Assert: scoped binding returns 404 for tenants outside the property.
        $response->assertNotFound();
    }
}
