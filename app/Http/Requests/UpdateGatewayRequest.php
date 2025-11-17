<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'exists:sites,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('gateways')->ignore($this->gateway)],
            'mac_address' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'connection_type' => ['nullable', 'string', 'max:50'],
            'ip_netmask' => ['nullable', 'string', 'max:50'],
            'ip_gateway' => ['nullable', 'ip'],
            'server_ip' => ['nullable', 'ip'],
            'description' => ['nullable', 'string'],
            'update_csv' => ['boolean'],
            'update_site_code' => ['boolean'],
            'ssh_enabled' => ['boolean'],
            'force_load_profile' => ['boolean'],
            'idf_number' => ['nullable', 'string', 'max:50'],
            'switch_name' => ['nullable', 'string', 'max:255'],
            'idf_port' => ['nullable', 'string', 'max:50'],
            'software_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
