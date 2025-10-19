<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('create permissions');
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.name' => ['required', 'string', 'max:100', 'distinct', 'unique:permissions,name'],
        ];
    }
}
