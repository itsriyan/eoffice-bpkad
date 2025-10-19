<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkUnitStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('create work_units');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:work_units,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
