<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $userId = Auth::id();
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:30'],
            'position' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'work_unit_id' => ['nullable', 'exists:work_units,id'],
        ];
    }
}
