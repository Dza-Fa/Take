@extends('layouts.app')

@section('title', 'Aset Inventaris')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Inventaris Take</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Aset bersama</h1>
                <p class="mt-2 text-sm text-stone-500">Kelola kondisi dan stok perlengkapan lingkungan.</p>
            </div>
            <a href="{{ route('admin.assets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-none bg-take-olive px-5 py-3 text-sm font-bold text-white shadow-lg shadow-take-olive/10 transition hover:bg-[#294231]">
                <span class="text-lg leading-none">+</span> Tambah aset
            </a>
        </div>

        <form method="GET" action="{{ route('admin.assets.index') }}" class="mt-8 grid gap-3 rounded-none border border-take-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_1fr_1fr_auto_auto] lg:items-end">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-bold text-stone-600">Cari nama atau kode</label>
                <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-none border border-stone-200 px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: TAKE-AST-001">
            </div>
            <div>
                <label for="category_id" class="mb-1.5 block text-xs font-bold text-stone-600">Kategori</label>
                <select id="category_id" name="category_id" class="w-full rounded-none border border-stone-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="condition" class="mb-1.5 block text-xs font-bold text-stone-600">Kondisi</label>
                <select id="condition" name="condition" class="w-full rounded-none border border-stone-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                    <option value="">Semua kondisi</option>
                    <option value="baik" @selected(($filters['condition'] ?? '') === 'baik')>Baik</option>
                    <option value="perbaikan" @selected(($filters['condition'] ?? '') === 'perbaikan')>Perlu perbaikan</option>
                    <option value="rusak" @selected(($filters['condition'] ?? '') === 'rusak')>Rusak</option>
                </select>
            </div>
            <button type="submit" class="rounded-none bg-take-olive px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Terapkan</button>
            <a href="{{ route('admin.assets.index') }}" class="rounded-none border border-stone-200 px-5 py-2.5 text-center text-sm font-bold text-stone-600 transition hover:bg-stone-50">Reset</a>
        </form>

        <div class="mt-5 overflow-hidden rounded-none border border-take-line bg-white shadow-sm">
            @if ($assets->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left">
                        <thead class="bg-stone-50 text-xs font-bold uppercase tracking-wider text-stone-500">
                            <tr>
                                <th class="px-5 py-4">Aset</th>
                                <th class="px-5 py-4">Kategori</th>
                                <th class="px-5 py-4">Stok tersedia</th>
                                <th class="px-5 py-4">Kondisi</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($assets as $asset)
                                <tr class="transition hover:bg-take-cream/40">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($asset->imageUrl())
                                                <img src="{{ $asset->imageUrl() }}" alt="Foto {{ $asset->name }}" class="size-12 rounded-none border border-stone-100 object-cover">
                                            @else
                                                <span class="grid size-12 place-items-center rounded-none bg-take-cream text-xl text-take-olive" aria-hidden="true">▧</span>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-stone-800">{{ $asset->name }}</p>
                                                <p class="mt-1 text-xs font-medium tracking-wide text-stone-400">{{ $asset->code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-stone-600">{{ $asset->category->name }}</td>
                                    <td class="px-5 py-4">
                                        <span class="font-semibold text-stone-800">{{ $asset->available_stock }}</span>
                                        <span class="text-sm text-stone-400">/ {{ $asset->total_stock }}</span>
                                        <span class="block text-xs text-stone-400">unit tersedia</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'inline-flex rounded-none px-3 py-1 text-xs font-bold',
                                            'bg-emerald-50 text-emerald-800' => $asset->condition === 'baik',
                                            'bg-amber-50 text-amber-800' => $asset->condition === 'perbaikan',
                                            'bg-rose-50 text-rose-800' => $asset->condition === 'rusak',
                                        ])>{{ ['baik' => 'Baik', 'perbaikan' => 'Perlu perbaikan', 'rusak' => 'Rusak'][$asset->condition] }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.assets.edit', $asset) }}" class="rounded-none px-3 py-2 text-xs font-bold text-take-olive hover:bg-take-cream">Edit</a>
                                            <form method="POST" action="{{ route('admin.assets.destroy', $asset) }}" @submit.prevent="$dispatch('confirm-action', { form: $el, title: 'Hapus aset?', message: 'Aset dan foto yang tersimpan akan dihapus. Tindakan ini tidak dapat dibatalkan.' })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-none px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($assets->hasPages())
                    <div class="border-t border-stone-100 px-5 py-4">{{ $assets->links() }}</div>
                @endif
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid size-16 place-items-center rounded-none bg-take-cream text-3xl text-take-olive" aria-hidden="true">▧</div>
                    <h2 class="mt-5 text-lg font-semibold">Belum ada aset yang cocok</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Ubah filter pencarian atau tambahkan aset inventaris pertama untuk warga.</p>
                    <a href="{{ route('admin.assets.create') }}" class="mt-6 inline-flex rounded-none bg-take-olive px-5 py-3 text-sm font-bold text-white hover:bg-[#294231]">Tambah aset pertama</a>
                </div>
            @endif
        </div>
    </section>
@endsection
