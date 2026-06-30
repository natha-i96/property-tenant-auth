<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Feature\Auth;

use Carbon\Carbon;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Tests\TestCase;

class TenantTokenTest extends TestCase
{
    private function createPropertyToken(Property $property, array $abilities): string
    {
        return $property->createToken('property', $abilities)->plainTextToken;
    }

    public function test_property_token_can_issue_tenant_token(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P100']);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', [
                'tenant_no' => 'T001',
                'role' => 'admin',
            ]);

        // Assert
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tenant_no', 'T001')
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('property_no', 'P100');
        $this->assertNotNull($response->json('token'));
    }

    public function test_tenant_token_can_access_me_endpoint(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P101']);
        $propertyToken = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        $issueResponse = $this->withToken($propertyToken)
            ->postJson('/api/v1/auth/tenant-token', [
                'tenant_no' => 'T002',
                'role' => 'tenant',
            ]);
        $tenantToken = $issueResponse->json('token');

        // Act
        $this->resetAuth();
        $response = $this->withToken($tenantToken)->getJson('/api/v1/me');

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'tenant',
                'property_no' => 'P101',
                'tenant_no' => 'T002',
                'role' => 'tenant',
            ])
            ->assertJsonPath('abilities', ['tenant', 'role:tenant', 'property:P101', 'tenant:T002']);
    }

    public function test_tenant_token_without_tenant_ability_returns_403(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P102']);
        $tenant = $tenantClass::factory()->create([
            'property_id' => $property->id,
            'tenant_no' => 'T003',
            'role' => 'admin',
        ]);
        $token = $tenant->createToken('tenant', ['other'])->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/me');

        // Assert
        $response->assertForbidden();
    }

    public function test_revoked_tenant_token_returns_401(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P103']);
        $propertyToken = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        $issueResponse = $this->withToken($propertyToken)
            ->postJson('/api/v1/auth/tenant-token', ['tenant_no' => 'T004']);
        $tenantToken = $issueResponse->json('token');

        // Revoke via /v1/auth/revoke
        $this->resetAuth();
        $this->withToken($tenantToken)->postJson('/api/v1/auth/revoke')->assertOk();

        // Act
        $this->resetAuth();
        $response = $this->withToken($tenantToken)->getJson('/api/v1/me');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_expired_tenant_token_returns_401(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P104']);
        $tenant = $tenantClass::factory()->create([
            'property_id' => $property->id,
            'tenant_no' => 'T005',
        ]);
        $token = $tenant->createToken(
            'tenant',
            ['tenant'],
            Carbon::now()->subMinute()
        )->plainTextToken;

        // Act
        $response = $this->withToken($token)->getJson('/api/v1/me');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_expired_property_cannot_issue_tenant_token(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create([
            'property_no' => 'P105',
            'expires_at' => Carbon::now()->subDay(),
        ]);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', ['tenant_no' => 'T006']);

        // Assert
        $response->assertForbidden();
    }

    public function test_inactive_tenant_cannot_get_token(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P106']);
        $tenantClass::factory()->create([
            'property_id' => $property->id,
            'tenant_no' => 'T007',
            'is_active' => false,
        ]);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', ['tenant_no' => 'T007']);

        // Assert
        $response->assertForbidden();
    }

    public function test_existing_tenant_role_is_not_changed_on_reissue(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P107']);
        $tenantClass::factory()->create([
            'property_id' => $property->id,
            'tenant_no' => 'T008',
            'role' => 'tenant',
        ]);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act: request admin role for existing tenant
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', [
                'tenant_no' => 'T008',
                'role' => 'admin',
            ]);

        // Assert: token still issued, but stored role stays tenant
        $response->assertOk()
            ->assertJsonPath('role', 'tenant');
        $this->assertDatabaseHas('tenants', [
            'property_id' => $property->id,
            'tenant_no' => 'T008',
            'role' => 'tenant',
        ]);
    }

    public function test_tenant_token_blocked_when_parent_property_inactive(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $tenantClass = $this->tenantModelClass();
        $property = $propertyClass::factory()->create([
            'property_no' => 'P108',
            'is_active' => true,
        ]);
        $tenant = $tenantClass::factory()->create([
            'property_id' => $property->id,
            'tenant_no' => 'T009',
        ]);
        $tenantToken = $tenant->createToken('tenant', ['tenant'])->plainTextToken;

        // Deactivate the parent property
        $property->update(['is_active' => false]);

        // Act
        $response = $this->withToken($tenantToken)->getJson('/api/v1/me');

        // Assert
        $response->assertForbidden();
    }

    public function test_tenant_token_issue_requires_tenant_no(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P109']);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('tenant_no');
    }

    public function test_tenant_token_issue_rejects_invalid_role(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P110']);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', [
                'tenant_no' => 'T010',
                'role' => 'superadmin',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('role');
    }

    public function test_tenant_token_issue_rejects_expires_in_minutes_out_of_range(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P111']);
        $token = $this->createPropertyToken($property, ['property', 'issue:tenant-token']);

        // Act
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/tenant-token', [
                'tenant_no' => 'T011',
                'expires_in_minutes' => 99999,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('expires_in_minutes');
    }
}
