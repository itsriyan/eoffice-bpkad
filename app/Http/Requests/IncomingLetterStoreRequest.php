<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomingLetterStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user()->can('incoming_letter.create');
    }

    public function rules(): array
    {
        return [
            'letter_number' => ['required', 'string', 'max:100', 'unique:incoming_letters,letter_number'],
            'letter_date' => ['required', 'date'],
            'received_date' => ['required', 'date', 'after_or_equal:letter_date'],
            'sender' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'primary_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'classification_code' => ['nullable', 'string', 'max:50'],
            'security_level' => ['nullable', 'string', 'max:50'],
            'speed_level' => ['nullable', 'string', 'max:50'],
            'origin_agency' => ['nullable', 'string', 'max:255'],
            'physical_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
