<?php

declare(strict_types=1);

namespace NathaI96\PropertyTenantAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_no' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
            'role' => ['sometimes', 'in:admin,tenant'],
            'expires_in_minutes' => ['sometimes', 'integer', 'min:1', 'max:10080'],
        ];
    }
}
