@extends('layouts.app')

@section('title', 'Kartu Bukti Pinjam')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="no-print mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ request()->routeIs('admin.borrowings.receipt') ? route('admin.borrowings.index') : route('my-borrowings.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-stone-500 transition hover:text-take-olive"><span aria-hidden="true">←</span> Kembali ke daftar peminjaman</a>
            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white transition hover:bg-[#294231]">Cetak kartu <span aria-hidden="true">⎙</span></button>
        </div>

        <article class="print-card overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm">
            <div class="bg-take-olive px-6 py-7 text-white sm:px-9">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-100/80">Take · Aset bersama</p>
                        <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">Kartu Bukti Pinjam</h1>
                    </div>
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/10 text-sm font-bold tracking-tight">tk</span>
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold">{{ [
                        'pending' => 'Menunggu persetujuan',
                        'approved' => 'Disetujui',
                        'borrowed' => 'Sedang dipinjam',
                        'returned' => 'Dikembalikan',
                        'rejected' => 'Ditolak',
                    ][$borrowing->status] }}</span>
                    @if ($borrowing->isOverdue())
                        <span class="rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-800">Terlambat dikembalikan</span>
                    @endif
                </div>
            </div>

            <div class="space-y-7 p-6 sm:p-9">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Kode booking</p>
                    <p class="mt-1 text-2xl font-bold tracking-wide text-take-olive">{{ $borrowing->booking_code }}</p>
                </div>

                <div class="grid gap-6 border-y border-dashed border-stone-200 py-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Peminjam</p>
                        <p class="mt-2 font-semibold text-stone-800">{{ $borrowing->user->name }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $borrowing->user->phone }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $borrowing->user->address }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Barang dipinjam</p>
                        <p class="mt-2 font-semibold text-stone-800">{{ $borrowing->asset->name }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $borrowing->asset->code }} · {{ $borrowing->asset->category->name }}</p>
                        <p class="mt-1 text-sm text-stone-500">Jumlah: {{ $borrowing->quantity }} unit</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="rounded-2xl bg-take-cream/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Tanggal pinjam</p>
                        <p class="mt-2 font-semibold text-stone-800">{{ $borrowing->borrow_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="rounded-2xl bg-take-cream/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Batas pengembalian</p>
                        <p class="mt-2 font-semibold text-stone-800">{{ $borrowing->return_date->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Keperluan acara</p>
                    <p class="mt-2 text-sm leading-6 text-stone-700">{{ $borrowing->purpose }}</p>
                </div>

                @if ($borrowing->return_condition)
                    <div class="rounded-2xl bg-take-cream/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Kondisi saat dikembalikan</p>
                        <p class="mt-2 text-sm font-semibold text-stone-700">{{ ucfirst($borrowing->return_condition) }}@if ($borrowing->returned_at) · {{ $borrowing->returned_at->format('d/m/Y H:i') }}@endif</p>
                        @if ($borrowing->admin_note)
                            <p class="mt-2 text-sm leading-6 text-stone-600">{{ $borrowing->admin_note }}</p>
                        @endif
                    </div>
                @elseif ($borrowing->admin_note)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Catatan admin</p>
                        <p class="mt-2 text-sm leading-6 text-stone-700">{{ $borrowing->admin_note }}</p>
                    </div>
                @endif

                <p class="border-t border-stone-100 pt-5 text-xs leading-5 text-stone-400">Kartu ini merupakan bukti digital pengajuan peminjaman aset lingkungan. Status persetujuan mengikuti informasi terbaru pada halaman Pinjaman Saya.</p>
            </div>
        </article>
    </section>
@endsection
