<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category)],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $slug = $this->input('slug');
        $icon = $this->input('icon');
        $attributes = [];

        if (is_string($name) && (! is_string($slug) || trim($slug) === '')) {
            $attributes['slug'] = Str::slug($name);
        }

        if (! is_string($icon) || trim($icon) === '') {
            $attributes['icon'] = 'box';
        }

        $this->merge($attributes);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'slug.required' => 'Slug kategori wajib diisi.',
            'slug.unique' => 'Slug ini sudah digunakan. Ubah nama atau slug kategori.',
            'slug.string' => 'Slug kategori harus berupa teks.',
            'slug.max' => 'Slug kategori maksimal 255 karakter.',
            'icon.string' => 'Nama ikon harus berupa teks.',
            'icon.max' => 'Nama ikon maksimal 50 karakter.',
            'description.string' => 'Deskripsi kategori harus berupa teks.',
            'description.max' => 'Deskripsi maksimal 5.000 karakter.',
        ];
    }
}
