<?php

namespace App\Http\Requests\Admin;

class IndexRequest extends AdminRequest
{
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
