<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Tests\Unit\Models;

use Carbon\Carbon;
use NathaI96\PropertyTenantAuth\Models\Tenant;
use NathaI96\PropertyTenantAuth\Tests\TestCase;

class TenantTest extends TestCase
{
    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $tenant = Tenant::make(['role' => 'admin']);

        $this->assertTrue($tenant->isAdmin());
        $this->assertFalse($tenant->isTenant());
    }

    public function test_is_tenant_returns_true_for_tenant_role(): void
    {
        $tenant = Tenant::make(['role' => 'tenant']);

        $this->assertTrue($tenant->isTenant());
        $this->assertFalse($tenant->isAdmin());
    }

    public function test_is_expired_returns_false_when_no_expiration(): void
    {
        $tenant = Tenant::make(['expires_at' => null]);

        $this->assertFalse($tenant->isExpired());
    }

    public function test_is_expired_returns_true_when_expiration_is_past(): void
    {
        $tenant = Tenant::make(['expires_at' => Carbon::now()->subDay()]);

        $this->assertTrue($tenant->isExpired());
    }
}
