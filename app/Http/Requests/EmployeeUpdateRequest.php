<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()?->can('edit employees') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('employee');
        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['required', 'string', 'max:30', Rule::unique('employees', 'nip')->ignore($id)],
            'position' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'work_unit_id' => ['nullable', 'exists:work_units,id'],
            'status' => ['required', 'in:active,inactive'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($id)],
        ];
    }
}
