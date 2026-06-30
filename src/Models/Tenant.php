<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;
use NathaI96\PropertyTenantAuth\Database\Factories\TenantFactory;

class Tenant extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable;
    use Authorizable;
    use HasApiTokens;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'tenant_no',
        'role',
        'is_active',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => 'string',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The property this tenant belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo($this->propertyModelClass());
    }

    /**
     * Get the property number via the relationship.
     */
    public function getPropertyNoAttribute(): ?string
    {
        return $this->property?->property_no;
    }

    /**
     * Scope a query to only active tenants.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine whether this tenant has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine whether this tenant has the tenant role.
     */
    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    /**
     * Determine whether the tenant access has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Resolve the configured property model class for relationships.
     */
    protected function propertyModelClass(): string
    {
        return config('property-tenant-auth.models.property', Property::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }
}
