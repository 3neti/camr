<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $siteId = $this->route('site')->id;
        
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'division_id' => ['required', 'exists:divisions,id'],
            'code' => ['required', 'string', 'max:100', 'unique:sites,code,' . $siteId],
            'primary_building_id' => ['nullable', 'exists:buildings,id'],
        ];
    }
}
