<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SubjectRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects')->ignore($this->route('subject'))],
            'code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('subjects')->ignore($this->route('subject'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
