<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Unit\Models;

use Carbon\Carbon;
use NathaI96\PropertyTenantAuth\Models\Property;
use NathaI96\PropertyTenantAuth\Tests\TestCase;

class PropertyTest extends TestCase
{
    public function test_is_expired_returns_false_when_no_expiration(): void
    {
        $property = Property::make(['expires_at' => null]);

        $this->assertFalse($property->isExpired());
    }

    public function test_is_expired_returns_true_when_expiration_is_past(): void
    {
        $property = Property::make(['expires_at' => Carbon::now()->subDay()]);

        $this->assertTrue($property->isExpired());
    }

    public function test_is_expired_returns_false_when_expiration_is_future(): void
    {
        $property = Property::make(['expires_at' => Carbon::now()->addDay()]);

        $this->assertFalse($property->isExpired());
    }
}
