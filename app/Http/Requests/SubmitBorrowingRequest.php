<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'borrow_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'return_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:borrow_date'],
            'purpose' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('quantity')) {
                    return;
                }

                $asset = $this->route('asset');

                if (! $asset instanceof Asset) {
                    return;
                }

                if ($asset->condition === 'rusak') {
                    $validator->errors()->add('quantity', 'Aset dalam kondisi rusak dan tidak dapat dipinjam.');

                    return;
                }

                if ($this->integer('quantity') > $asset->available_stock) {
                    $validator->errors()->add('quantity', 'Jumlah pinjaman melebihi stok yang tersedia.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Jumlah pinjaman wajib diisi.',
            'quantity.integer' => 'Jumlah pinjaman harus berupa angka bulat.',
            'quantity.min' => 'Jumlah pinjaman minimal 1 unit.',
            'borrow_date.required' => 'Tanggal pinjam wajib diisi.',
            'borrow_date.date_format' => 'Format tanggal pinjam tidak valid.',
            'borrow_date.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            'return_date.required' => 'Tanggal kembali wajib diisi.',
            'return_date.date_format' => 'Format tanggal kembali tidak valid.',
            'return_date.after_or_equal' => 'Tanggal kembali harus sama atau setelah tanggal pinjam.',
            'purpose.required' => 'Keperluan acara wajib diisi.',
            'purpose.string' => 'Keperluan acara harus berupa teks.',
            'purpose.max' => 'Keperluan acara maksimal 255 karakter.',
        ];
    }
}
