@extends('layouts.app')

@section('title', $asset->name)

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-500 transition hover:text-take-olive"><span aria-hidden="true">←</span> Kembali ke katalog</a>

        <div class="mt-5 grid gap-6 lg:grid-cols-[1fr_0.85fr]">
            <article class="overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm">
                @if ($asset->image)
                    <img src="{{ Storage::disk('public')->url($asset->image) }}" alt="Foto {{ $asset->name }}" class="max-h-[32rem] min-h-72 w-full bg-stone-100 object-cover">
                @else
                    <div class="grid min-h-72 place-items-center bg-gradient-to-br from-take-cream to-[#eee7d8]">
                        <div class="grid size-24 place-items-center rounded-3xl border border-white/80 bg-white/70 text-5xl text-take-olive shadow-sm" aria-hidden="true">▧</div>
                    </div>
                @endif
                <div class="p-6 sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-take-clay">{{ $asset->category->name }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $asset->name }}</h1>
                    <p class="mt-2 text-sm font-medium tracking-wide text-stone-400">{{ $asset->code }}</p>
                    <p class="mt-5 text-sm leading-7 text-stone-600">{{ $asset->description ?: 'Belum ada deskripsi untuk aset ini.' }}</p>

                    <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-stone-100 pt-5">
                        <span class="rounded-full bg-take-cream px-3 py-1.5 text-sm font-bold text-take-olive">Tersedia {{ $asset->available_stock }} dari {{ $asset->total_stock }} unit</span>
                        <span @class([
                            'rounded-full px-3 py-1.5 text-sm font-bold',
                            'bg-emerald-50 text-emerald-800' => $asset->condition === 'baik',
                            'bg-amber-50 text-amber-800' => $asset->condition === 'perbaikan',
                            'bg-rose-50 text-rose-800' => $asset->condition === 'rusak',
                        ])>{{ ['baik' => 'Kondisi baik', 'perbaikan' => 'Perlu perbaikan', 'rusak' => 'Rusak'][ $asset->condition ] }}</span>
                    </div>
                </div>
            </article>

            <section class="h-fit rounded-3xl border border-take-line bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-take-clay">Pengajuan pinjaman</p>
                <h2 class="mt-2 text-2xl font-semibold">Atur jadwal peminjaman</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">Pengajuan menunggu persetujuan admin. Stok baru dikurangi setelah pengajuan disetujui dan barang diserahkan.</p>

                @if ($asset->condition === 'rusak')
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-800">Aset ini sedang dalam kondisi rusak sehingga belum dapat dipinjam.</div>
                @elseif ($asset->available_stock < 1)
                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Stok aset sedang habis. Silakan cek kembali nanti.</div>
                @else
                    <form method="POST" action="{{ route('borrowings.store', $asset) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="quantity" class="mb-2 block text-sm font-semibold text-stone-700">Jumlah unit</label>
                            <input id="quantity" name="quantity" type="number" min="1" max="{{ $asset->available_stock }}" step="1" value="{{ old('quantity', $suggestedQuantity) }}" required class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                            <p class="mt-1.5 text-xs text-stone-400">Maksimal {{ $asset->available_stock }} unit tersedia.</p>
                            @error('quantity')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrow_date" class="mb-2 block text-sm font-semibold text-stone-700">Tanggal pinjam</label>
                                <input id="borrow_date" name="borrow_date" type="date" min="{{ today()->toDateString() }}" value="{{ old('borrow_date', today()->toDateString()) }}" required class="w-full rounded-xl border border-stone-200 px-3 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                                @error('borrow_date')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="return_date" class="mb-2 block text-sm font-semibold text-stone-700">Tanggal kembali</label>
                                <input id="return_date" name="return_date" type="date" min="{{ old('borrow_date', today()->toDateString()) }}" value="{{ old('return_date', old('borrow_date', today()->toDateString())) }}" required class="w-full rounded-xl border border-stone-200 px-3 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                                @error('return_date')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="purpose" class="mb-2 block text-sm font-semibold text-stone-700">Keperluan acara</label>
                            <textarea id="purpose" name="purpose" rows="3" maxlength="255" required class="w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: Rapat RT 04 atau kerja bakti lingkungan">{{ old('purpose') }}</textarea>
                            @error('purpose')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-take-olive px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-take-olive/10 transition hover:bg-[#294231]">Kirim pengajuan</button>
                    </form>
                @endif
            </section>
        </div>
    </section>
@endsection
