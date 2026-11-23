@extends('layouts.app')

@section('title', 'Katalog Aset')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr] lg:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Inventaris warga</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Cari perlengkapan untuk kegiatanmu.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-500">Aset bersama dapat dipinjam warga untuk kegiatan lingkungan. Pilih barang, tentukan jadwal, lalu kirim pengajuan.</p>
            </div>
            <a href="{{ route('my-borrowings.index') }}" class="inline-flex items-center justify-center gap-2 justify-self-start rounded-xl border border-take-line bg-white px-4 py-3 text-sm font-bold text-take-olive transition hover:border-take-olive lg:justify-self-end">
                Lihat pinjaman saya <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="mt-8 rounded-3xl border border-take-line bg-white p-5 shadow-sm sm:p-7" x-data="{ activePreset: null }">
            <div class="flex flex-col gap-1">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-take-clay">Mulai lebih cepat</p>
                <h2 class="text-xl font-semibold">Paket rekomendasi acara</h2>
                <p class="text-sm text-stone-500">Pilih preset untuk melihat barang dan jumlah yang biasanya dibutuhkan.</p>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ($bundles as $key => $bundle)
                    <button
                        type="button"
                        @click="activePreset = '{{ $key }}'"
                        :class="activePreset === '{{ $key }}' ? 'bg-take-olive text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-700 hover:border-take-olive hover:text-take-olive'"
                        class="rounded-full px-4 py-2.5 text-sm font-bold transition"
                        :aria-pressed="activePreset === '{{ $key }}'"
                    >{{ $bundle['label'] }}</button>
                @endforeach
            </div>

            @foreach ($bundles as $key => $bundle)
                <div x-cloak x-show="activePreset === '{{ $key }}'" x-transition class="mt-5 rounded-2xl bg-take-cream/70 p-4 sm:p-5">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="font-semibold">{{ $bundle['label'] }}</h3>
                            <p class="mt-1 text-sm text-stone-500">{{ $bundle['description'] }}</p>
                        </div>
                        <span class="text-xs font-medium text-stone-500">Ajukan barang satu per satu</span>
                    </div>

                    @if ($bundle['recommendations']->isNotEmpty())
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($bundle['recommendations'] as $recommendation)
                                @php
                                    $recommendedAsset = $recommendation['asset'];
                                    $recommendedQuantity = min($recommendation['quantity'], max(1, $recommendedAsset->available_stock));
                                    $canRecommend = $recommendedAsset->available_stock > 0 && $recommendedAsset->condition !== 'rusak';
                                @endphp
                                <article class="rounded-2xl border border-white bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-stone-800">{{ $recommendedAsset->name }}</p>
                                            <p class="mt-1 text-xs text-stone-400">{{ $recommendedAsset->code }} · {{ $recommendedAsset->category->name }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-take-cream px-2.5 py-1 text-xs font-bold text-take-olive">{{ $recommendation['quantity'] }} unit</span>
                                    </div>
                                    <p class="mt-3 text-xs text-stone-500">Tersedia {{ $recommendedAsset->available_stock }} dari {{ $recommendedAsset->total_stock }} unit</p>
                                    @if ($canRecommend)
                                        <a href="{{ route('catalog.show', ['asset' => $recommendedAsset, 'quantity' => $recommendedQuantity]) }}" class="mt-3 inline-flex text-sm font-bold text-take-olive hover:underline">Atur jadwal & ajukan →</a>
                                    @else
                                        <p class="mt-3 text-xs font-semibold text-rose-700">Belum dapat diajukan saat ini.</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-4 rounded-xl bg-white p-4 text-sm text-stone-500">Belum ada aset rekomendasi untuk paket ini. Silakan pilih barang dari katalog.</p>
                    @endif
                </div>
            @endforeach
        </div>

        <form method="GET" action="{{ route('catalog.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-take-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_1fr_auto_auto] lg:items-end">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-bold text-stone-600">Cari nama atau kode aset</label>
                <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: kursi atau TAKE-AST-001">
            </div>
            <div>
                <label for="category_id" class="mb-1.5 block text-xs font-bold text-stone-600">Kategori</label>
                <select id="category_id" name="category_id" class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-semibold text-stone-700">
                <input type="checkbox" name="available" value="1" @checked(request()->boolean('available')) class="size-4 rounded border-stone-300 text-take-olive focus:ring-take-olive">
                Hanya yang tersedia
            </label>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-take-olive px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Cari aset</button>
                <a href="{{ route('catalog.index') }}" class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-bold text-stone-600 transition hover:bg-stone-50">Reset</a>
            </div>
        </form>

        <div class="mt-8 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Semua aset</h2>
                <p class="mt-1 text-sm text-stone-500">{{ $assets->total() }} aset terdaftar di inventaris.</p>
            </div>
        </div>

        @if ($assets->isNotEmpty())
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($assets as $asset)
                    <article class="overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        @if ($asset->image)
                            <img src="{{ Storage::disk('public')->url($asset->image) }}" alt="Foto {{ $asset->name }}" class="h-52 w-full bg-stone-100 object-cover">
                        @else
                            <div class="grid h-52 place-items-center bg-gradient-to-br from-take-cream to-[#eee7d8]" aria-label="Foto belum tersedia">
                                <div class="grid size-20 place-items-center rounded-3xl border border-white/80 bg-white/70 text-4xl text-take-olive shadow-sm" aria-hidden="true">▧</div>
                            </div>
                        @endif
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-take-clay">{{ $asset->category->name }}</p>
                                    <h3 class="mt-1 text-lg font-semibold">{{ $asset->name }}</h3>
                                    <p class="mt-1 text-xs font-medium tracking-wide text-stone-400">{{ $asset->code }}</p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-800' => $asset->condition === 'baik',
                                    'bg-amber-50 text-amber-800' => $asset->condition === 'perbaikan',
                                    'bg-rose-50 text-rose-800' => $asset->condition === 'rusak',
                                ])>{{ ['baik' => 'Baik', 'perbaikan' => 'Perlu perbaikan', 'rusak' => 'Rusak'][$asset->condition] }}</span>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-take-olive">Tersedia {{ $asset->available_stock }} dari {{ $asset->total_stock }} unit</p>
                            <p class="mt-2 min-h-10 text-sm leading-5 text-stone-500">{{ $asset->description ? \Illuminate\Support\Str::limit($asset->description, 90) : 'Belum ada deskripsi untuk aset ini.' }}</p>
                            <a href="{{ route('catalog.show', $asset) }}" class="mt-5 inline-flex w-full items-center justify-center rounded-xl {{ $asset->available_stock > 0 && $asset->condition !== 'rusak' ? 'bg-take-olive text-white hover:bg-[#294231]' : 'border border-stone-200 text-stone-600 hover:bg-stone-50' }} px-4 py-3 text-sm font-bold transition">
                                {{ $asset->available_stock > 0 && $asset->condition !== 'rusak' ? 'Lihat detail & ajukan' : 'Lihat detail aset' }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($assets->hasPages())
                <div class="mt-6">{{ $assets->links() }}</div>
            @endif
        @else
            <div class="mt-5 rounded-3xl border border-dashed border-take-line bg-white px-6 py-16 text-center">
                <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-take-cream text-3xl text-take-olive" aria-hidden="true">⌕</div>
                <h2 class="mt-5 text-lg font-semibold">Belum ada aset yang cocok</h2>
                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Coba ubah kata pencarian atau kategori. Aset baru akan muncul setelah pengurus menambahkannya.</p>
                <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white hover:bg-[#294231]">Lihat semua aset</a>
            </div>
        @endif
    </section>
@endsection
