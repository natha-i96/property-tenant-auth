<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_no' => $this->property_no,
            'tenant_no' => $this->tenant_no,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
