@extends('layouts.app')

@section('title', 'Beranda Warga')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="overflow-hidden rounded-none bg-take-olive p-7 text-white shadow-xl shadow-take-olive/10 sm:p-10">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-100/80">Ruang warga</p>
            <h1 class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">Halo, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-base leading-7 text-white/75">Selamat datang di Take, ruang bersama untuk menjaga aset lingkungan tetap bermanfaat bagi semua.</p>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            <article class="rounded-none border border-take-line bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold text-stone-500">Pinjaman aktif</p>
                <p class="mt-2 text-4xl font-semibold tracking-tight text-take-ink">{{ $activeBorrowings->count() }}</p>
                <a href="{{ route('my-borrowings.index') }}" class="mt-3 inline-flex text-xs font-bold text-take-olive hover:underline">Lihat pinjaman saya <span class="ml-1" aria-hidden="true">→</span></a>
            </article>
            <article class="rounded-none border border-take-line bg-white p-6 shadow-sm sm:col-span-2">
                <p class="text-sm font-semibold text-stone-500">Tenggat pengembalian terdekat</p>
                @if ($nearestReturn)
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xl font-semibold text-take-ink">{{ $nearestReturn->asset->name }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $nearestReturn->booking_code }} · {{ $nearestReturn->quantity }} unit</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-bold text-stone-700">{{ $nearestReturn->return_date->format('d/m/Y') }}</p>
                            @if ($nearestReturn->isOverdue())
                                <span class="rounded-none bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Terlambat</span>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-stone-500">Belum ada aset yang perlu dikembalikan.</p>
                @endif
            </article>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            <a href="{{ route('catalog.index') }}" class="group rounded-none border border-take-line bg-white p-7 transition hover:-translate-y-0.5 hover:shadow-lg sm:p-9">
                <span class="grid size-12 place-items-center rounded-none bg-take-cream text-2xl text-take-olive" aria-hidden="true">▧</span>
                <h2 class="mt-5 text-xl font-semibold">Jelajahi aset bersama</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">Cari perlengkapan lingkungan dan ajukan pinjaman untuk kegiatan warga.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-take-olive">Buka katalog <span aria-hidden="true">→</span></span>
            </a>
            <a href="{{ route('my-borrowings.index') }}" class="group rounded-none border border-take-line bg-white p-7 transition hover:-translate-y-0.5 hover:shadow-lg sm:p-9">
                <span class="grid size-12 place-items-center rounded-none bg-take-cream text-2xl text-take-olive" aria-hidden="true">↗</span>
                <h2 class="mt-5 text-xl font-semibold">Pantau pinjaman</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">Lihat status pengajuan, tanggal kembali, dan kartu bukti pinjam digital.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-take-olive">Pinjaman saya <span aria-hidden="true">→</span></span>
            </a>
        </div>

        <section class="mt-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-take-clay">Pantau jadwal</p>
                    <h2 class="mt-1 text-xl font-semibold">Pinjaman yang sedang berjalan</h2>
                </div>
                <a href="{{ route('my-borrowings.index') }}" class="text-sm font-bold text-take-olive hover:underline">Riwayat lengkap</a>
            </div>
            @if ($activeBorrowings->isNotEmpty())
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach ($activeBorrowings->take(4) as $borrowing)
                        <article class="rounded-none border border-take-line bg-white p-5 transition hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold">{{ $borrowing->asset->name }}</h3>
                                    <p class="mt-1 text-xs text-stone-500">{{ $borrowing->booking_code }} · {{ $borrowing->quantity }} unit</p>
                                </div>
                                @if ($borrowing->isOverdue())
                                    <span class="shrink-0 rounded-none bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Terlambat</span>
                                @else
                                    <span class="shrink-0 rounded-none bg-sky-50 px-3 py-1 text-xs font-bold text-sky-800">{{ $borrowing->status === 'approved' ? 'Disetujui' : 'Sedang dipinjam' }}</span>
                                @endif
                            </div>
                            <p class="mt-4 text-sm text-stone-500">Tenggat kembali <span class="font-semibold text-stone-700">{{ $borrowing->return_date->format('d/m/Y') }}</span></p>
                            <a href="{{ route('my-borrowings.receipt', $borrowing) }}" class="mt-4 inline-flex text-xs font-bold text-take-olive hover:underline">Buka kartu bukti <span class="ml-1" aria-hidden="true">→</span></a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-none border border-dashed border-stone-300 bg-white/70 px-6 py-10 text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-none bg-take-cream text-xl text-take-olive" aria-hidden="true">↗</span>
                    <h3 class="mt-3 font-semibold text-stone-800">Belum ada pinjaman aktif</h3>
                    <p class="mt-1 text-sm text-stone-500">Ajukan pinjaman dari katalog untuk kegiatan warga berikutnya.</p>
                    <a href="{{ route('catalog.index') }}" class="mt-4 inline-flex rounded-none bg-take-olive px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Jelajahi katalog</a>
                </div>
            @endif
        </section>
    </section>
@endsection
