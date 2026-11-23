<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_condition' => ['required', Rule::in(['baik', 'perbaikan', 'rusak'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_condition.required' => 'Pilih kondisi aset saat dikembalikan.',
            'return_condition.in' => 'Kondisi pengembalian tidak valid.',
            'admin_note.string' => 'Catatan admin harus berupa teks.',
            'admin_note.max' => 'Catatan admin maksimal 2.000 karakter.',
        ];
    }
}
