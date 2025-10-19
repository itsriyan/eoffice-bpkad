<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('create grades');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:grades,code'],
            'category' => ['required', 'string', 'max:50'],
            'rank' => ['required', 'string', 'max:100'],
        ];
    }
}
