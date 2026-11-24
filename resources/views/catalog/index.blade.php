@extends('layouts.app')

@section('title', 'Katalog Aset')

@section('content')
    <section
        class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12"
        x-data="{
            assets: [],
            loaded: false,
            loading: false,
            error: '',
            requestController: null,
            page: 1,
            meta: { current_page: 1, last_page: 1, total: 0 },
            async loadAssets(page = 1) {
                this.requestController?.abort();
                const controller = new AbortController();
                this.requestController = controller;
                this.loading = true;
                this.error = '';
                const formData = new FormData(this.$refs.filterForm);
                const params = new URLSearchParams();
                if (formData.get('search')) params.set('q', formData.get('search'));
                if (formData.get('category_id')) params.set('category', formData.get('category_id'));
                if (formData.get('available')) params.set('available_only', '1');
                params.set('page', page);

                try {
                    const response = await fetch('{{ route('api.assets.index') }}?' + params.toString(), {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    });
                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'Pencarian aset tidak dapat dimuat.');
                    }

                    this.assets = payload.data;
                    this.meta = payload.meta;
                    this.page = payload.meta.current_page;
                    this.loaded = true;
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    this.error = error.message || 'Koneksi API tidak tersedia. Coba lagi atau muat ulang halaman.';
                } finally {
                    if (this.requestController === controller) {
                        this.requestController = null;
                        this.loading = false;
                    }
                }
            },
            conditionLabel(condition) {
                return { baik: 'Baik', perbaikan: 'Perlu perbaikan', rusak: 'Rusak' }[condition] || condition;
            },
            conditionClass(condition) {
                return {
                    baik: 'bg-emerald-50 text-emerald-800',
                    perbaikan: 'bg-amber-50 text-amber-800',
                    rusak: 'bg-rose-50 text-rose-800',
                }[condition] || 'bg-stone-100 text-stone-700';
            },
        }"
    >
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

        <section class="mt-5 rounded-3xl border border-take-line bg-white p-5 shadow-sm sm:p-6" x-data="{ bookingCode: '', borrowing: null, loading: false, error: '', async checkStatus() { this.loading = true; this.error = ''; this.borrowing = null; try { const response = await fetch('{{ url('/api/borrowings') }}/' + encodeURIComponent(this.bookingCode.trim()), { headers: { Accept: 'application/json' } }); const payload = await response.json(); if (!response.ok || !payload.success) throw new Error(payload.message || 'Status peminjaman tidak dapat dimuat.'); this.borrowing = payload.data; } catch (error) { this.error = error.message || 'Koneksi API tidak tersedia. Coba lagi.'; } finally { this.loading = false; } } }">
            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-take-clay">Pelacakan mandiri</p>
                    <h2 class="mt-1 text-xl font-semibold">Cek status peminjaman</h2>
                    <p class="mt-1 text-sm text-stone-500">Masukkan kode TAKE pada bukti pengajuan untuk melihat status dan tenggat pengembalian.</p>
                </div>
                <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="checkStatus()">
                    <label class="sr-only" for="booking-code-lookup">Kode peminjaman</label>
                    <input id="booking-code-lookup" x-model="bookingCode" required maxlength="40" type="text" class="min-w-0 rounded-xl border border-stone-200 px-4 py-3 text-sm uppercase outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10 sm:w-72" placeholder="TAKE-20261123-001">
                    <button type="submit" :disabled="loading" class="rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white transition hover:bg-[#294231] disabled:cursor-wait disabled:opacity-60" x-text="loading ? 'Memeriksa...' : 'Cek status'"></button>
                </form>
            </div>
            <p x-cloak x-show="error" x-text="error" class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert"></p>
            <article x-cloak x-show="borrowing" class="mt-5 rounded-2xl border border-take-line bg-take-cream/60 p-4 sm:p-5" aria-live="polite">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-500" x-text="borrowing?.booking_code"></p>
                        <h3 class="mt-1 text-lg font-semibold text-take-ink" x-text="borrowing?.asset_name"></h3>
                        <p class="mt-1 text-sm text-stone-600" x-text="(borrowing?.quantity || 0) + ' unit'"></p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-take-olive" x-text="({ pending: 'Menunggu persetujuan', approved: 'Disetujui', borrowed: 'Sedang dipinjam', returned: 'Dikembalikan', rejected: 'Ditolak' })[borrowing?.status] || borrowing?.status"></span>
                </div>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <p class="text-stone-500">Tanggal pinjam <span class="font-semibold text-stone-700" x-text="borrowing?.borrow_date"></span></p>
                    <p class="text-stone-500">Tanggal kembali <span class="font-semibold text-stone-700" x-text="borrowing?.return_date"></span></p>
                </div>
                <p x-cloak x-show="borrowing?.is_overdue" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">Peminjaman ini telah melewati tanggal kembali. Silakan hubungi pengurus untuk proses pengembalian.</p>
            </article>
        </section>

        <form x-ref="filterForm" method="GET" action="{{ route('catalog.index') }}" class="mt-8 grid gap-3 rounded-2xl border border-take-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_1fr_auto_auto] lg:items-end" @submit.prevent="loadAssets(1)">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-bold text-stone-600">Cari nama atau kode aset</label>
                <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: kursi atau TAKE-AST-001" @input.debounce.350ms="loadAssets(1)">
            </div>
            <div>
                <label for="category_id" class="mb-1.5 block text-xs font-bold text-stone-600">Kategori</label>
                <select id="category_id" name="category_id" class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" @change="loadAssets(1)">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-semibold text-stone-700">
                <input type="checkbox" name="available" value="1" @checked(request()->boolean('available')) class="size-4 rounded border-stone-300 text-take-olive focus:ring-take-olive" @change="loadAssets(1)">
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
                <p x-show="!loaded" class="mt-1 text-sm text-stone-500">{{ $assets->total() }} aset terdaftar di inventaris.</p>
                <p x-cloak x-show="loaded" class="mt-1 text-sm text-stone-500"><span x-text="meta.total"></span> aset ditemukan.</p>
            </div>
        </div>

        <p x-cloak x-show="loading" class="mt-5 rounded-2xl bg-white px-5 py-4 text-sm font-medium text-stone-500" role="status">Memuat aset terbaru...</p>
        <p x-cloak x-show="error" x-text="error" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800" role="alert"></p>

        <div x-cloak x-show="loaded" class="mt-5" aria-live="polite">
            <template x-if="assets.length > 0">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="asset in assets" :key="asset.id">
                        <article class="overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                            <template x-if="asset.image">
                                <img :src="asset.image" :alt="'Foto ' + asset.name" class="h-52 w-full bg-stone-100 object-cover">
                            </template>
                            <template x-if="!asset.image">
                                <div class="grid h-52 place-items-center bg-gradient-to-br from-take-cream to-[#eee7d8]" aria-label="Foto belum tersedia">
                                    <div class="grid size-20 place-items-center rounded-3xl border border-white/80 bg-white/70 text-4xl text-take-olive shadow-sm" aria-hidden="true">▧</div>
                                </div>
                            </template>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-take-clay" x-text="asset.category.name"></p>
                                        <h3 class="mt-1 text-lg font-semibold" x-text="asset.name"></h3>
                                        <p class="mt-1 text-xs font-medium tracking-wide text-stone-400" x-text="asset.code"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold" :class="conditionClass(asset.condition)" x-text="conditionLabel(asset.condition)"></span>
                                </div>
                                <p class="mt-4 text-sm font-semibold text-take-olive"><span x-text="asset.available_stock"></span> dari <span x-text="asset.total_stock"></span> unit tersedia</p>
                                <p class="mt-2 min-h-10 text-sm leading-5 text-stone-500" x-text="asset.description || 'Belum ada deskripsi untuk aset ini.'"></p>
                                <a :href="'{{ url('/catalog') }}/' + asset.id" class="mt-5 inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-bold transition" :class="asset.available_stock > 0 && asset.condition !== 'rusak' ? 'bg-take-olive text-white hover:bg-[#294231]' : 'border border-stone-200 text-stone-600 hover:bg-stone-50'" x-text="asset.available_stock > 0 && asset.condition !== 'rusak' ? 'Lihat detail & ajukan' : 'Lihat detail aset'"></a>
                            </div>
                        </article>
                    </template>
                </div>
            </template>
            <template x-if="assets.length === 0">
                <div class="rounded-3xl border border-dashed border-take-line bg-white px-6 py-16 text-center">
                    <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-take-cream text-3xl text-take-olive" aria-hidden="true">⌕</div>
                    <h2 class="mt-5 text-lg font-semibold">Belum ada aset yang cocok</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Coba ubah kata pencarian atau kategori.</p>
                    <button type="button" class="mt-6 rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white transition hover:bg-[#294231]" @click="$refs.filterForm.reset(); loadAssets(1)">Lihat semua aset</button>
                </div>
            </template>
            <div x-show="meta.last_page > 1" class="mt-6 flex items-center justify-between gap-3">
                <button type="button" :disabled="page <= 1 || loading" class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-bold text-stone-600 transition hover:bg-white disabled:opacity-40" @click="loadAssets(page - 1)">Sebelumnya</button>
                <span class="text-sm text-stone-500">Halaman <span x-text="page"></span> dari <span x-text="meta.last_page"></span></span>
                <button type="button" :disabled="page >= meta.last_page || loading" class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-bold text-stone-600 transition hover:bg-white disabled:opacity-40" @click="loadAssets(page + 1)">Berikutnya</button>
            </div>
        </div>

        <div x-show="!loaded">
        @if ($assets->isNotEmpty())
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($assets as $asset)
                    <article class="overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        @if ($asset->imageUrl())
                            <img src="{{ $asset->imageUrl() }}" alt="Foto {{ $asset->name }}" class="h-52 w-full bg-stone-100 object-cover">
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
        </div>
    </section>
@endsection
