@extends('layouts.app')

@section('title', 'Beranda Warga')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="overflow-hidden rounded-3xl bg-take-olive p-7 text-white shadow-xl shadow-take-olive/10 sm:p-10">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-100/80">Ruang warga</p>
            <h1 class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">Halo, {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-base leading-7 text-white/75">Selamat datang di Take, ruang bersama untuk menjaga aset lingkungan tetap bermanfaat bagi semua.</p>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            <a href="{{ route('catalog.index') }}" class="group rounded-3xl border border-take-line bg-white p-7 transition hover:-translate-y-0.5 hover:shadow-lg sm:p-9">
                <span class="grid size-12 place-items-center rounded-2xl bg-take-cream text-2xl text-take-olive" aria-hidden="true">▧</span>
                <h2 class="mt-5 text-xl font-semibold">Jelajahi aset bersama</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">Cari perlengkapan lingkungan dan ajukan pinjaman untuk kegiatan warga.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-take-olive">Buka katalog <span aria-hidden="true">→</span></span>
            </a>
            <a href="{{ route('my-borrowings.index') }}" class="group rounded-3xl border border-take-line bg-white p-7 transition hover:-translate-y-0.5 hover:shadow-lg sm:p-9">
                <span class="grid size-12 place-items-center rounded-2xl bg-take-cream text-2xl text-take-olive" aria-hidden="true">↗</span>
                <h2 class="mt-5 text-xl font-semibold">Pantau pinjaman</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">Lihat status pengajuan, tanggal kembali, dan kartu bukti pinjam digital.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-take-olive">Pinjaman saya <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </section>
@endsection
