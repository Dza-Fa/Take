@extends('layouts.app')

@section('title', 'Pengelolaan Peminjaman')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-take-clay">Panel pengurus</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Pengelolaan peminjaman</h1>
            <p class="mt-2 text-sm text-stone-500">Tinjau pengajuan, serahkan aset, catat kondisi pengembalian, dan pantau transaksi terlambat.</p>
        </div>

        <form method="GET" action="{{ route('admin.borrowings.index') }}" class="mt-7 grid gap-3 rounded-2xl border border-take-line bg-white p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <label for="status" class="mb-1.5 block text-xs font-bold text-stone-600">Status transaksi</label>
                <select id="status" name="status" class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                    <option value="all" @selected($status === 'all')>Semua status</option>
                    <option value="pending" @selected($status === 'pending')>Menunggu persetujuan</option>
                    <option value="approved" @selected($status === 'approved')>Disetujui</option>
                    <option value="borrowed" @selected($status === 'borrowed')>Sedang dipinjam</option>
                    <option value="returned" @selected($status === 'returned')>Dikembalikan</option>
                    <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
                </select>
            </div>
            <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-semibold text-stone-700">
                <input type="checkbox" name="overdue" value="1" @checked($isOverdueFilter) class="size-4 rounded border-stone-300 text-take-olive focus:ring-take-olive">
                Hanya yang terlambat
            </label>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-take-olive px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Terapkan</button>
                <a href="{{ route('admin.borrowings.index') }}" class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-bold text-stone-600 transition hover:bg-stone-50">Reset</a>
            </div>
        </form>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                <p class="font-bold">Perubahan belum dapat disimpan.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 overflow-hidden rounded-3xl border border-take-line bg-white shadow-sm">
            @if ($borrowings->isNotEmpty())
                <div class="divide-y divide-stone-100">
                    @foreach ($borrowings as $borrowing)
                        <article id="borrowing-{{ $borrowing->id }}" @class([
                            'p-5 sm:p-6',
                            'bg-rose-50/60' => $borrowing->isOverdue(),
                        ])>
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold">{{ $borrowing->asset->name }}</h2>
                                        <span @class([
                                            'rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-amber-50 text-amber-800' => $borrowing->status === 'pending',
                                            'bg-sky-50 text-sky-800' => $borrowing->status === 'approved',
                                            'bg-indigo-50 text-indigo-800' => $borrowing->status === 'borrowed',
                                            'bg-emerald-50 text-emerald-800' => $borrowing->status === 'returned',
                                            'bg-rose-100 text-rose-800' => $borrowing->status === 'rejected',
                                        ])>{{ [
                                            'pending' => 'Menunggu persetujuan',
                                            'approved' => 'Disetujui',
                                            'borrowed' => 'Sedang dipinjam',
                                            'returned' => 'Dikembalikan',
                                            'rejected' => 'Ditolak',
                                        ][$borrowing->status] }}</span>
                                        @if ($borrowing->isOverdue())
                                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">Terlambat</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs font-semibold tracking-wide text-stone-400">{{ $borrowing->booking_code }} · {{ $borrowing->asset->code }} · {{ $borrowing->asset->category->name }}</p>

                                    <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Peminjam</p>
                                            <p class="mt-1 font-semibold text-stone-700">{{ $borrowing->user->name }}</p>
                                            <p class="mt-0.5 text-stone-500">{{ $borrowing->user->phone }}</p>
                                            <p class="mt-0.5 text-stone-500">{{ $borrowing->user->address }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Jumlah & jadwal</p>
                                            <p class="mt-1 font-semibold text-stone-700">{{ $borrowing->quantity }} unit</p>
                                            <p class="mt-0.5 text-stone-500">Pinjam {{ $borrowing->borrow_date->format('d/m/Y') }}</p>
                                            <p class="mt-0.5 text-stone-500">Kembali {{ $borrowing->return_date->format('d/m/Y') }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Keperluan acara</p>
                                            <p class="mt-1 text-stone-600">{{ $borrowing->purpose }}</p>
                                            @if ($borrowing->return_condition)
                                                <p class="mt-2 text-xs font-semibold text-stone-500">Kondisi saat kembali: {{ ucfirst($borrowing->return_condition) }}@if ($borrowing->returned_at) · {{ $borrowing->returned_at->format('d/m/Y H:i') }}@endif</p>
                                            @endif
                                            @if ($borrowing->admin_note)
                                                <p class="mt-2 text-xs text-stone-500"><span class="font-bold">Catatan admin:</span> {{ $borrowing->admin_note }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col gap-3 xl:w-72">
                                    <a href="{{ route('admin.borrowings.receipt', $borrowing) }}" class="inline-flex items-center justify-center rounded-xl border border-take-line px-4 py-2.5 text-sm font-bold text-take-olive transition hover:border-take-olive hover:bg-take-cream/50">Kartu bukti pinjam</a>

                                    @if (in_array($borrowing->status, ['approved', 'borrowed', 'rejected'], true))
                                        <x-whatsapp-action :borrowing="$borrowing" type="status" label="Kirim konfirmasi status via WhatsApp" />
                                    @endif

                                    @if (in_array($borrowing->status, ['approved', 'borrowed'], true))
                                        <x-whatsapp-action :borrowing="$borrowing" type="reminder" :label="$borrowing->isOverdue() ? 'Kirim peringatan terlambat' : 'Kirim pengingat pengembalian'" />
                                    @endif

                                    @if ($borrowing->status === 'pending')
                                        <form method="POST" action="{{ route('admin.borrowings.approve', $borrowing) }}" @submit.prevent="$dispatch('confirm-action', { form: $el, title: 'Setujui dan serahkan barang?', message: 'Persetujuan akan langsung mengurangi stok tersedia dan mengubah status transaksi menjadi sedang dipinjam.' })">
                                            @csrf
                                            <button type="submit" class="w-full rounded-xl bg-take-olive px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Setujui & serahkan barang</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.borrowings.reject', $borrowing) }}" class="space-y-2" @submit.prevent="$dispatch('confirm-action', { form: $el, title: 'Tolak pengajuan?', message: 'Pastikan alasan penolakan sudah diisi. Pengajuan akan ditandai sebagai ditolak.' })">
                                            @csrf
                                            <label for="admin_note_{{ $borrowing->id }}" class="block text-xs font-bold text-stone-600">Alasan penolakan <span class="text-rose-700">*</span></label>
                                            <textarea id="admin_note_{{ $borrowing->id }}" name="admin_note" rows="2" required maxlength="2000" class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10" placeholder="Jelaskan alasan pengajuan ditolak">{{ old('admin_note') }}</textarea>
                                            <button type="submit" class="w-full rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50">Tolak pengajuan</button>
                                        </form>
                                    @elseif (in_array($borrowing->status, ['approved', 'borrowed'], true))
                                        <form method="POST" action="{{ route('admin.borrowings.return', $borrowing) }}" class="space-y-2 rounded-2xl bg-take-cream/60 p-3" @submit.prevent="$dispatch('confirm-action', { form: $el, title: 'Terima pengembalian?', message: 'Pastikan kondisi aset sudah dipilih. Stok tersedia akan ditambah sesuai jumlah barang yang kembali.' })">
                                            @csrf
                                            <label for="return_condition_{{ $borrowing->id }}" class="block text-xs font-bold text-stone-600">Kondisi aset saat kembali</label>
                                            <select id="return_condition_{{ $borrowing->id }}" name="return_condition" required class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10">
                                                <option value="">Pilih kondisi</option>
                                                <option value="baik">Baik</option>
                                                <option value="perbaikan">Perlu perbaikan</option>
                                                <option value="rusak">Rusak</option>
                                            </select>
                                            <label for="return_note_{{ $borrowing->id }}" class="block text-xs font-bold text-stone-600">Catatan admin <span class="font-normal text-stone-400">(opsional)</span></label>
                                            <textarea id="return_note_{{ $borrowing->id }}" name="admin_note" rows="2" maxlength="2000" class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm outline-none focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Catatan kondisi atau tindak lanjut">{{ old('admin_note') }}</textarea>
                                            <button type="submit" class="w-full rounded-xl bg-take-olive px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#294231]">Terima pengembalian</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if ($borrowings->hasPages())
                    <div class="border-t border-stone-100 px-5 py-4">{{ $borrowings->links() }}</div>
                @endif
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-take-cream text-3xl text-take-olive" aria-hidden="true">↗</div>
                    <h2 class="mt-5 text-lg font-semibold">Belum ada transaksi pada filter ini</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-stone-500">Transaksi baru dari warga akan muncul di sini untuk ditinjau oleh pengurus.</p>
                    <a href="{{ route('admin.borrowings.index') }}" class="mt-6 inline-flex rounded-xl bg-take-olive px-5 py-3 text-sm font-bold text-white hover:bg-[#294231]">Tampilkan semua</a>
                </div>
            @endif
        </div>
    </section>
@endsection
