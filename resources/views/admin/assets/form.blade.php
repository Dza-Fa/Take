@extends('layouts.app')

@section('title', $asset->exists ? 'Edit Aset' : 'Tambah Aset')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <a href="{{ route('admin.assets.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-500 hover:text-take-olive"><span aria-hidden="true">←</span> Kembali ke aset</a>
        <div class="mt-5 rounded-3xl border border-take-line bg-white p-6 shadow-sm sm:p-9">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Pengelolaan inventaris</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $asset->exists ? 'Edit aset' : 'Tambah aset' }}</h1>
            <p class="mt-2 text-sm text-stone-500">Catat identitas, kondisi, dan ketersediaan aset dengan teliti.</p>

            <form method="POST" action="{{ $asset->exists ? route('admin.assets.update', $asset) : route('admin.assets.store') }}" enctype="multipart/form-data" class="mt-8 space-y-6">
                @csrf
                @if ($asset->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-stone-700">Nama aset</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $asset->name) }}" required maxlength="255" class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: Kursi lipat">
                        @error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="category_id" class="mb-2 block text-sm font-semibold text-stone-700">Kategori</label>
                        <select id="category_id" name="category_id" required class="w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $asset->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="code" class="mb-2 block text-sm font-semibold text-stone-700">Kode aset <span class="font-normal text-stone-400">(opsional)</span></label>
                        <input id="code" name="code" type="text" value="{{ old('code', $asset->code) }}" maxlength="255" class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Otomatis: TAKE-AST-001">
                        <p class="mt-1.5 text-xs text-stone-400">Kode otomatis dibuat jika kolom ini dikosongkan.</p>
                        @error('code')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="condition" class="mb-2 block text-sm font-semibold text-stone-700">Kondisi</label>
                        <select id="condition" name="condition" required class="w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                            <option value="baik" @selected(old('condition', $asset->condition ?: 'baik') === 'baik')>Baik</option>
                            <option value="perbaikan" @selected(old('condition', $asset->condition) === 'perbaikan')>Perlu perbaikan</option>
                            <option value="rusak" @selected(old('condition', $asset->condition) === 'rusak')>Rusak</option>
                        </select>
                        @error('condition')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="total_stock" class="mb-2 block text-sm font-semibold text-stone-700">Stok total</label>
                        <input id="total_stock" name="total_stock" type="number" min="0" step="1" value="{{ old('total_stock', $asset->total_stock ?? 0) }}" required class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                        @error('total_stock')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="available_stock" class="mb-2 block text-sm font-semibold text-stone-700">Stok tersedia</label>
                        <input id="available_stock" name="available_stock" type="number" min="0" step="1" value="{{ old('available_stock', $asset->available_stock ?? 0) }}" required class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                        <p class="mt-1.5 text-xs text-stone-400">Tidak boleh melebihi stok total atau mengabaikan aset yang sedang dipinjam.</p>
                        @error('available_stock')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="mb-2 block text-sm font-semibold text-stone-700">Deskripsi <span class="font-normal text-stone-400">(opsional)</span></label>
                        <textarea id="description" name="description" rows="4" maxlength="5000" class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Catatan singkat tentang aset">{{ old('description', $asset->description) }}</textarea>
                        @error('description')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="image" class="mb-2 block text-sm font-semibold text-stone-700">Foto aset <span class="font-normal text-stone-400">(JPG, PNG, WEBP; maks. 5 MB)</span></label>
                        @if ($asset->image)
                            <div class="mb-3 flex items-center gap-4 rounded-xl bg-take-cream/70 p-3">
                                <img src="{{ Storage::disk('public')->url($asset->image) }}" alt="Foto {{ $asset->name }}" class="size-16 rounded-lg object-cover">
                                <span class="text-xs text-stone-500">Foto saat ini akan diganti jika Anda memilih foto baru.</span>
                            </div>
                        @endif
                        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-stone-200 bg-white text-sm text-stone-600 file:mr-4 file:border-0 file:bg-take-cream file:px-4 file:py-3 file:text-sm file:font-semibold file:text-take-olive hover:file:bg-take-olive/10">
                        @error('image')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-stone-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.assets.index') }}" class="rounded-xl border border-stone-200 px-5 py-3 text-center text-sm font-bold text-stone-600 hover:bg-stone-50">Batal</a>
                    <button type="submit" class="rounded-xl bg-take-olive px-6 py-3 text-sm font-bold text-white hover:bg-[#294231]">{{ $asset->exists ? 'Simpan perubahan' : 'Simpan aset' }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
