<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkUnitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('edit work_units');
    }

    public function rules(): array
    {
        $id = $this->route('work_unit')->id ?? null;
        return [
            'name' => ['required', 'string', 'max:150', 'unique:work_units,name,' . $id],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
