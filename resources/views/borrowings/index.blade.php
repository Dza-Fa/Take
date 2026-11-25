@extends('layouts.app')

@section('title', 'Pinjaman Saya')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Ruang warga</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Pinjaman saya</h1>
                <p class="mt-2 text-sm text-stone-500">Pantau pengajuan, jadwal kembali, dan bukti pinjam digital.</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 self-start rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white transition hover:bg-[#294231] sm:self-auto">Cari aset lain <span aria-hidden="true">→</span></a>
        </div>

        @if ($borrowings->isNotEmpty())
            <div class="mt-7 space-y-4">
                @foreach ($borrowings as $borrowing)
                    <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                @if ($borrowing->asset->imageUrl())
                                    <img src="{{ $borrowing->asset->imageUrl() }}" alt="Foto {{ $borrowing->asset->name }}" class="size-16 shrink-0 rounded-2xl border border-stone-100 object-cover">
                                @else
                                    <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-take-cream text-2xl text-take-olive" aria-hidden="true">▧</span>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold">{{ $borrowing->asset->name }}</h2>
                                        <span @class([
                                            'rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-amber-50 text-amber-800' => $borrowing->status === 'pending',
                                            'bg-sky-50 text-sky-800' => $borrowing->status === 'approved',
                                            'bg-indigo-50 text-indigo-800' => $borrowing->status === 'borrowed',
                                            'bg-emerald-50 text-emerald-800' => $borrowing->status === 'returned',
                                            'bg-rose-50 text-rose-800' => $borrowing->status === 'rejected',
                                        ])>{{ [
                                            'pending' => 'Menunggu persetujuan',
                                            'approved' => 'Disetujui',
                                            'borrowed' => 'Sedang dipinjam',
                                            'returned' => 'Dikembalikan',
                                            'rejected' => 'Ditolak',
                                        ][$borrowing->status] }}</span>
                                        @if ($borrowing->isOverdue())
                                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Terlambat dikembalikan</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs font-semibold tracking-wide text-stone-400">{{ $borrowing->booking_code }} · {{ $borrowing->asset->category->name }}</p>
                                    <p class="mt-3 text-sm text-stone-600">{{ $borrowing->purpose }}</p>
                                </div>
                            </div>
                            <a href="{{ route('my-borrowings.receipt', $borrowing) }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-take-line px-4 py-2.5 text-sm font-bold text-take-olive transition hover:border-take-olive hover:bg-take-cream/50">
                                Kartu bukti pinjam <span aria-hidden="true">↗</span>
                            </a>
                        </div>

                        <div class="mt-5 grid gap-3 border-t border-stone-100 pt-4 text-sm sm:grid-cols-3">
                            <div><span class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Jumlah</span><span class="mt-1 block font-semibold text-stone-700">{{ $borrowing->quantity }} unit</span></div>
                            <div><span class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Tanggal pinjam</span><span class="mt-1 block font-semibold text-stone-700">{{ $borrowing->borrow_date->format('d/m/Y') }}</span></div>
                            <div><span class="block text-xs font-semibold uppercase tracking-wide text-stone-400">Tanggal kembali</span><span class="mt-1 block font-semibold text-stone-700">{{ $borrowing->return_date->format('d/m/Y') }}</span></div>
                        </div>
                        @if ($borrowing->admin_note)
                            <p class="mt-4 rounded-xl bg-take-cream/70 px-4 py-3 text-sm text-stone-600"><span class="font-bold">Catatan admin:</span> {{ $borrowing->admin_note }}</p>
                        @endif
                        @if ($borrowing->status === 'pending')
                            <div class="mt-4 max-w-sm">
                                <x-whatsapp-action :borrowing="$borrowing" type="new_request" label="Kirim ringkasan ke pengurus via WhatsApp" />
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($borrowings->hasPages())
                <div class="mt-6">{{ $borrowings->links() }}</div>
            @endif
        @else
            <div class="mt-7 rounded-3xl border border-dashed border-take-line bg-white px-6 py-16 text-center">
                <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-take-cream text-3xl text-take-olive" aria-hidden="true">↗</div>
                <h2 class="mt-5 text-lg font-semibold">Belum ada pinjaman</h2>
                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Saat kamu mengajukan aset untuk kegiatan warga, status dan jadwalnya akan muncul di halaman ini.</p>
                <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white hover:bg-[#294231]">Jelajahi katalog</a>
            </div>
        @endif
    </section>
@endsection
