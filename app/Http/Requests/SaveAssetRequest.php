<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('assets', 'code')->ignore($asset)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'available_stock' => ['required', 'integer', 'min:0', 'lte:total_stock'],
            'condition' => ['required', Rule::in(['baik', 'perbaikan', 'rusak'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $asset = $this->route('asset');

                if (! $asset instanceof Asset
                    || $validator->errors()->has('total_stock')
                    || $validator->errors()->has('available_stock')) {
                    return;
                }

                $borrowedStock = $asset->borrowings()
                    ->where(function ($query): void {
                        $query->whereIn('status', ['approved', 'borrowed'])
                            ->orWhere(function ($pendingQuery): void {
                                $pendingQuery->where('status', 'pending')
                                    ->where('stock_reserved', true);
                            });
                    })
                    ->sum('quantity');

                if ($this->integer('available_stock') + $borrowedStock > $this->integer('total_stock')) {
                    $validator->errors()->add(
                        'total_stock',
                        'Stok total harus mencakup stok tersedia dan aset yang sedang dipinjam.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori aset.',
            'category_id.integer' => 'Kategori aset yang dipilih tidak valid.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'code.string' => 'Kode aset harus berupa teks.',
            'code.max' => 'Kode aset maksimal 255 karakter.',
            'code.unique' => 'Kode aset sudah digunakan.',
            'name.required' => 'Nama aset wajib diisi.',
            'name.string' => 'Nama aset harus berupa teks.',
            'name.max' => 'Nama aset maksimal 255 karakter.',
            'description.string' => 'Deskripsi aset harus berupa teks.',
            'description.max' => 'Deskripsi maksimal 5.000 karakter.',
            'image.image' => 'File foto harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
            'total_stock.required' => 'Jumlah stok total wajib diisi.',
            'total_stock.integer' => 'Jumlah stok total harus berupa angka bulat.',
            'total_stock.min' => 'Jumlah stok total tidak boleh kurang dari 0.',
            'available_stock.required' => 'Stok tersedia wajib diisi.',
            'available_stock.integer' => 'Stok tersedia harus berupa angka bulat.',
            'available_stock.min' => 'Stok tersedia tidak boleh kurang dari 0.',
            'available_stock.lte' => 'Stok tersedia tidak boleh melebihi stok total.',
            'condition.required' => 'Pilih kondisi aset.',
            'condition.in' => 'Kondisi aset tidak valid.',
        ];
    }
}
