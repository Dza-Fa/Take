@extends('layouts.app')

@section('title', 'Kategori Aset')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Inventaris Take</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Kategori aset</h1>
                <p class="mt-2 text-sm text-stone-500">Kelompokkan perlengkapan agar mudah ditemukan dan dirawat.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white shadow-lg shadow-take-olive/10 transition hover:bg-[#294231]">
                <span class="text-lg leading-none">+</span> Tambah kategori
            </a>
        </div>

        <div class="mt-8 overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm">
            @if ($categories->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead class="bg-stone-50 text-xs font-bold uppercase tracking-wider text-stone-500">
                            <tr>
                                <th class="px-6 py-4">Nama kategori</th>
                                <th class="px-6 py-4">Slug</th>
                                <th class="px-6 py-4">Jumlah aset</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($categories as $category)
                                <tr class="transition hover:bg-take-cream/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="font-semibold text-stone-800">{{ $category->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-stone-500">{{ $category->slug }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full bg-take-cream px-3 py-1 text-xs font-bold text-take-olive">{{ $category->assets_count }} aset</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg px-3 py-2 text-xs font-bold text-take-olive hover:bg-take-cream">Edit</a>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" @submit.prevent="$dispatch('confirm-action', { form: $el, title: 'Hapus kategori?', message: 'Kategori yang masih memiliki aset tetap tidak dapat dihapus.' })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($categories->hasPages())
                    <div class="border-t border-stone-100 px-6 py-4">{{ $categories->links() }}</div>
                @endif
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-take-cream text-3xl text-take-olive" aria-hidden="true">▦</div>
                    <h2 class="mt-5 text-lg font-semibold">Belum ada kategori</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Mulai dengan membuat kategori pertama, misalnya perlengkapan acara atau alat kerja bakti.</p>
                    <a href="{{ route('admin.categories.create') }}" class="mt-6 inline-flex rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white hover:bg-[#294231]">Buat kategori pertama</a>
                </div>
            @endif
        </div>
    </section>
@endsection
