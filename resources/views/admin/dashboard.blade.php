@extends('layouts.app')

@section('title', 'Dashboard Pengurus')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Ringkasan pengurus</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Dashboard Take</h1>
                <p class="mt-2 text-sm text-stone-500">Gambaran inventaris dan hal-hal yang perlu ditindaklanjuti hari ini.</p>
            </div>
            <a href="{{ route('admin.borrowings.index') }}" class="inline-flex items-center justify-center rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white transition hover:bg-[#294231]">Kelola peminjaman <span class="ml-2" aria-hidden="true">→</span></a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:col-span-2">
                <p class="text-sm font-semibold text-stone-500">Total jenis aset</p>
                <p data-stat="asset-types" class="mt-3 text-4xl font-semibold tracking-tight text-take-ink">{{ $stats['assetTypes'] }}</p>
                <a href="{{ route('admin.assets.index') }}" class="mt-3 inline-flex text-xs font-bold text-take-olive hover:underline">Lihat inventaris <span class="ml-1" aria-hidden="true">→</span></a>
            </article>
            <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:col-span-1">
                <p class="text-sm font-semibold text-stone-500">Unit tersedia</p>
                <p data-stat="available-units" class="mt-3 text-4xl font-semibold tracking-tight text-take-ink">{{ $stats['availableUnits'] }}</p>
                <p class="mt-3 text-xs text-stone-400">Di seluruh inventaris</p>
            </article>
            <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:col-span-1">
                <p class="text-sm font-semibold text-stone-500">Pinjaman aktif</p>
                <p data-stat="active-borrowings" class="mt-3 text-4xl font-semibold tracking-tight text-take-ink">{{ $stats['activeBorrowings'] }}</p>
                <p class="mt-3 text-xs text-stone-400">Sudah diserahkan</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:col-span-1">
                <p class="text-sm font-semibold text-amber-800">Menunggu</p>
                <p data-stat="pending-borrowings" class="mt-3 text-4xl font-semibold tracking-tight text-amber-950">{{ $stats['pendingBorrowings'] }}</p>
                <a href="{{ route('admin.borrowings.index', ['status' => 'pending']) }}" class="mt-3 inline-flex text-xs font-bold text-amber-800 hover:underline">Tinjau pengajuan <span class="ml-1" aria-hidden="true">→</span></a>
            </article>
            <article class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md lg:col-span-1">
                <p class="text-sm font-semibold text-rose-800">Terlambat</p>
                <p data-stat="overdue-borrowings" class="mt-3 text-4xl font-semibold tracking-tight text-rose-950">{{ $stats['overdueBorrowings'] }}</p>
                <a href="{{ route('admin.borrowings.index', ['status' => 'all', 'overdue' => 1]) }}" class="mt-3 inline-flex text-xs font-bold text-rose-800 hover:underline">Tindak lanjuti <span class="ml-1" aria-hidden="true">→</span></a>
            </article>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-5">
            <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm sm:p-6 lg:col-span-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-take-clay">Pemakaian aset</p>
                    <h2 class="mt-1 text-lg font-semibold">Barang paling sering dipinjam</h2>
                    <p class="mt-1 text-sm text-stone-500">Berdasarkan jumlah unit yang sudah disetujui atau dipinjam.</p>
                </div>
                @if ($chartData['topAssets']['labels'] !== [])
                    <div class="mt-5 h-64">
                        <canvas data-chart="top-assets" role="img" aria-label="Grafik barang paling sering dipinjam"></canvas>
                    </div>
                @else
                    <div class="mt-5 grid min-h-64 place-items-center rounded-2xl bg-take-cream/60 px-6 text-center">
                        <div>
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-white text-xl text-take-olive" aria-hidden="true">▥</span>
                            <p class="mt-3 font-semibold text-stone-700">Belum ada data peminjaman</p>
                            <p class="mt-1 text-sm text-stone-500">Grafik terisi setelah pengajuan disetujui.</p>
                        </div>
                    </div>
                @endif
            </article>

            <article class="rounded-3xl border border-take-line bg-white p-5 shadow-sm sm:p-6 lg:col-span-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-take-clay">Status transaksi</p>
                    <h2 class="mt-1 text-lg font-semibold">Distribusi peminjaman</h2>
                    <p class="mt-1 text-sm text-stone-500">Ringkasan semua status transaksi saat ini.</p>
                </div>
                @if (array_sum($chartData['statuses']['values']) > 0)
                    <div class="mx-auto mt-5 h-64 max-w-sm">
                        <canvas data-chart="borrowing-statuses" role="img" aria-label="Grafik distribusi status peminjaman"></canvas>
                    </div>
                @else
                    <div class="mt-5 grid min-h-64 place-items-center rounded-2xl bg-take-cream/60 px-6 text-center">
                        <div>
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-white text-xl text-take-olive" aria-hidden="true">◷</span>
                            <p class="mt-3 font-semibold text-stone-700">Belum ada transaksi</p>
                            <p class="mt-1 text-sm text-stone-500">Pengajuan warga akan muncul sebagai ringkasan di sini.</p>
                        </div>
                    </div>
                @endif
            </article>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Perlu persetujuan</p>
                        <h2 class="mt-1 text-lg font-semibold">Pengajuan terbaru</h2>
                    </div>
                    <a href="{{ route('admin.borrowings.index', ['status' => 'pending']) }}" class="text-xs font-bold text-take-olive hover:underline">Semua pengajuan</a>
                </div>
                @if ($pendingBorrowings->isNotEmpty())
                    <div class="divide-y divide-stone-100">
                        @foreach ($pendingBorrowings as $borrowing)
                            <article id="borrowing-{{ $borrowing->id }}" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-stone-800">{{ $borrowing->asset->name }} <span class="font-normal text-stone-400">· {{ $borrowing->quantity }} unit</span></p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $borrowing->booking_code }} · {{ $borrowing->user->name }}</p>
                                </div>
                                <a href="{{ route('admin.borrowings.index', ['status' => 'pending']) }}#borrowing-{{ $borrowing->id }}" class="shrink-0 rounded-lg bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 transition hover:bg-amber-100">Tinjau</a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-10 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-xl bg-take-cream text-lg text-take-olive" aria-hidden="true">✓</span>
                        <p class="mt-3 font-semibold text-stone-700">Tidak ada pengajuan menunggu</p>
                        <p class="mt-1 text-sm text-stone-500">Semua pengajuan terbaru sudah ditangani.</p>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-3xl border border-rose-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-rose-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-700">Butuh tindak lanjut</p>
                        <h2 class="mt-1 text-lg font-semibold">Peminjaman terlambat</h2>
                    </div>
                    <a href="{{ route('admin.borrowings.index', ['status' => 'all', 'overdue' => 1]) }}" class="text-xs font-bold text-rose-700 hover:underline">Semua yang terlambat</a>
                </div>
                @if ($overdueBorrowings->isNotEmpty())
                    <div class="divide-y divide-stone-100">
                        @foreach ($overdueBorrowings as $borrowing)
                            <article id="borrowing-{{ $borrowing->id }}" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-stone-800">{{ $borrowing->asset->name }} <span class="font-normal text-stone-400">· {{ $borrowing->quantity }} unit</span></p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $borrowing->booking_code }} · {{ $borrowing->user->name }} · Tenggat {{ $borrowing->return_date->format('d/m/Y') }}</p>
                                </div>
                                <a href="{{ route('admin.borrowings.index', ['status' => 'all', 'overdue' => 1]) }}#borrowing-{{ $borrowing->id }}" class="shrink-0 rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-800 transition hover:bg-rose-100">Tindak lanjuti</a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-10 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-xl bg-emerald-50 text-lg text-emerald-700" aria-hidden="true">✓</span>
                        <p class="mt-3 font-semibold text-stone-700">Tidak ada pinjaman terlambat</p>
                        <p class="mt-1 text-sm text-stone-500">Semua pengembalian masih dalam tenggat.</p>
                    </div>
                @endif
            </section>
        </div>
    </section>

    <script>
        window.takeDashboardCharts = {{ Illuminate\Support\Js::from($chartData) }};
    </script>
@endsection
