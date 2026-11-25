@extends('layouts.app')

@section('title', $category->exists ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-500 hover:text-take-olive"><span aria-hidden="true">←</span> Kembali ke kategori</a>
        <div class="mt-5 rounded-none border border-take-line bg-white p-6 shadow-sm sm:p-9">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Pengaturan inventaris</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $category->exists ? 'Edit kategori' : 'Tambah kategori' }}</h1>
            <p class="mt-2 text-sm text-stone-500">Informasi ini membantu warga menemukan aset dengan cepat.</p>

            <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="mt-8 space-y-5">
                @csrf
                @if ($category->exists)
                    @method('PUT')
                @endif
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-stone-700">Nama kategori</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required maxlength="255" class="w-full rounded-none border border-stone-200 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: Peralatan acara">
                    @error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="slug" class="mb-2 block text-sm font-semibold text-stone-700">Slug <span class="font-normal text-stone-400">(otomatis dari nama jika kosong)</span></label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $category->slug) }}" maxlength="255" class="w-full rounded-none border border-stone-200 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="peralatan-acara">
                    @error('slug')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="icon" class="mb-2 block text-sm font-semibold text-stone-700">Ikon</label>
                    <input id="icon" name="icon" type="text" value="{{ old('icon', $category->icon) }}" maxlength="50" class="w-full rounded-none border border-stone-200 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="box, tent, speaker">
                    @error('icon')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm font-semibold text-stone-700">Deskripsi <span class="font-normal text-stone-400">(opsional)</span></label>
                    <textarea id="description" name="description" rows="4" maxlength="5000" class="w-full rounded-none border border-stone-200 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Jelaskan jenis aset dalam kategori ini">{{ old('description', $category->description) }}</textarea>
                    @error('description')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.categories.index') }}" class="rounded-none border border-stone-200 px-5 py-3 text-center text-sm font-bold text-stone-600 hover:bg-stone-50">Batal</a>
                    <button type="submit" class="rounded-none bg-take-olive px-6 py-3 text-sm font-bold text-white hover:bg-[#294231]">{{ $category->exists ? 'Simpan perubahan' : 'Simpan kategori' }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
