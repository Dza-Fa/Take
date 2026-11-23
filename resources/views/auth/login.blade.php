@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <main class="grid min-h-screen bg-white lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-take-olive px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-24 -top-20 size-96 rounded-full border border-white/10"></div>
            <div class="absolute -right-8 -top-4 size-64 rounded-full border border-white/10"></div>
            <div class="absolute -bottom-36 -left-20 size-96 rounded-full bg-white/5"></div>
            <a href="{{ route('login') }}" class="relative inline-flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-2xl bg-white text-sm font-bold text-take-olive">tk</span>
                <span class="text-xl font-bold tracking-tight">Take</span>
            </a>
            <div class="relative max-w-xl">
                <p class="mb-5 text-xs font-bold uppercase tracking-[0.24em] text-lime-100/80">Aset bersama, manfaat untuk semua</p>
                <h1 class="text-5xl font-semibold leading-[1.08] tracking-tight xl:text-6xl">Berbagi lebih mudah, lingkungan lebih kuat.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-white/75">Satu tempat untuk merawat, menemukan, dan menggunakan aset bersama warga.</p>
                <div class="mt-10 flex items-center gap-3 text-sm text-white/80">
                    <span class="grid size-9 place-items-center rounded-full bg-white/10">01</span>
                    <span>Transparan untuk semua warga</span>
                </div>
            </div>
            <p class="relative text-xs text-white/55">Dikelola bersama warga, untuk warga.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-12 sm:px-10 lg:px-14">
            <div class="w-full max-w-md">
                <div class="mb-10 lg:hidden">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-2xl bg-take-olive text-sm font-bold text-white">tk</span>
                        <span class="text-xl font-bold tracking-tight">Take</span>
                    </a>
                </div>
                <p class="text-sm font-semibold text-take-clay">Selamat datang kembali</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">Masuk ke Take</h2>
                <p class="mt-3 text-sm leading-6 text-stone-500">Gunakan akun warga atau admin untuk melanjutkan.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-stone-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="nama@email.com">
                        @error('email')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-stone-700">Kata sandi</label>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Masukkan kata sandi">
                        @error('password')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <label class="flex items-center gap-3 text-sm text-stone-600">
                        <input type="checkbox" name="remember" value="1" class="size-4 rounded border-stone-300 accent-take-olive">
                        Ingat saya
                    </label>
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-take-olive px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-take-olive/15 transition hover:bg-[#294231] focus:outline-none focus:ring-4 focus:ring-take-olive/20">
                        Masuk
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-stone-500">
                    Belum memiliki akun?
                    <a href="{{ route('register') }}" class="font-bold text-take-olive hover:underline">Daftar sebagai warga</a>
                </p>
                <p class="mt-10 text-center text-xs text-stone-400">Dengan masuk, Anda ikut menjaga aset bersama.</p>
            </div>
        </section>
    </main>
@endsection
