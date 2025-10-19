<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('edit permissions');
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.id' => ['required', 'integer', 'exists:permissions,id'],
            // ensure name uniqueness ignoring current row's id
            'permissions.*.name' => ['required', 'string', 'max:100'],
        ];
    }
}
