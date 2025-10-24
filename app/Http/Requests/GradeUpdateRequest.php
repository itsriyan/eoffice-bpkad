<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('grade.edit');
    }

    public function rules(): array
    {
        $id = $this->route('grade')->id ?? null;
        return [
            'code' => ['required', 'string', 'max:20', 'unique:grades,code,' . $id],
            'category' => ['required', 'string', 'max:50'],
            'rank' => ['required', 'string', 'max:100'],
        ];
    }
}
