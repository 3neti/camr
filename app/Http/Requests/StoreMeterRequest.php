<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway_id' => ['required', 'exists:gateways,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'name' => ['required', 'string', 'max:255', 'unique:meters'],
            'type' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Active,Inactive'],
            'is_addressable' => ['boolean'],
            'has_load_profile' => ['boolean'],
            'default_name' => ['nullable', 'string', 'max:255'],
            'software_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
