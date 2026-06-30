<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Feature\Auth;

use NathaI96\PropertyTenantAuth\Tests\TestCase;

class ThrottleTest extends TestCase
{
    public function test_tenant_token_issue_is_throttled_after_five_attempts(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P200']);
        $token = $property->createToken('property', ['property', 'issue:tenant-token'])->plainTextToken;

        // Act: send 6 requests within one minute
        for ($i = 0; $i < 6; $i++) {
            $response = $this->withToken($token)
                ->postJson('/api/v1/auth/tenant-token', [
                    'tenant_no' => 'T'.$i,
                ]);
        }

        // Assert: the 6th request is throttled
        $response->assertStatus(429);
    }

    public function test_token_revoke_is_throttled_after_five_attempts(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::factory()->create(['property_no' => 'P201']);
        $tokens = [];
        for ($i = 0; $i < 6; $i++) {
            $tokens[] = $property->createToken('property', ['property'])->plainTextToken;
        }

        // Act: send 6 revoke requests
        foreach ($tokens as $token) {
            $response = $this->withToken($token)->postJson('/api/v1/auth/revoke');
        }

        // Assert: the 6th request is throttled
        $response->assertStatus(429);
    }
}
