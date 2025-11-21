<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSqlDumpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            // max is in kilobytes: 51200 KB = 50 MB
            'file' => ['required', 'file', 'max:51200'],
            'file.extensions' => 'The file must have a .sql extension',
        ];
    }

    /**
     * Validate the file extension manually
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            if ($file && ! in_array(strtolower($file->getClientOriginalExtension()), ['sql'])) {
                $validator->errors()->add('file', 'The file must be a SQL file (.sql).');
            }
        });

        return $validator;
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a SQL dump file to upload.',
            'file.file' => 'The uploaded item must be a file.',
            'file.mimes' => 'The file must be a SQL file (.sql).',
            'file.max' => 'The file may not be greater than 50MB.',
        ];
    }
}
