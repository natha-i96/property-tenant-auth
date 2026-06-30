<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Feature\Auth;

use Illuminate\Support\Facades\Artisan;
use NathaI96\PropertyTenantAuth\Tests\TestCase;

class CreatePropertyCommandTest extends TestCase
{
    public function test_command_creates_property_and_prints_token(): void
    {
        // Act
        $exitCode = Artisan::call('property:create', [
            'property_no' => 'PCLI',
            '--name' => 'CLI Property',
        ]);
        $output = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Property created successfully.', $output);
        $this->assertStringContainsString('property_no : PCLI', $output);
        $this->assertStringContainsString('Store this token immediately', $output);

        $propertyClass = $this->propertyModelClass();
        $property = $propertyClass::where('property_no', 'PCLI')->first();
        $this->assertNotNull($property);
        $this->assertTrue($property->is_active);
        $this->assertEquals('CLI Property', $property->name);
        $this->assertNotNull($property->uuid);
    }

    public function test_command_fails_on_duplicate_property_no(): void
    {
        // Arrange
        $propertyClass = $this->propertyModelClass();
        $propertyClass::factory()->create(['property_no' => 'PDUP']);

        // Act
        $exitCode = Artisan::call('property:create', [
            'property_no' => 'PDUP',
        ]);
        $output = Artisan::output();

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Property [PDUP] already exists.', $output);
    }

    public function test_command_fails_on_invalid_expires_option(): void
    {
        // Act
        $exitCode = Artisan::call('property:create', [
            'property_no' => 'PINV',
            '--expires' => 'not-a-datetime',
        ]);
        $output = Artisan::output();

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Invalid --expires value.', $output);

        $propertyClass = $this->propertyModelClass();
        $this->assertNull($propertyClass::where('property_no', 'PINV')->first());
    }
}
