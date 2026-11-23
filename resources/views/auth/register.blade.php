@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <main class="grid min-h-screen bg-white lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-take-olive px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="absolute -right-24 -top-20 size-96 rounded-full border border-white/10"></div>
            <div class="absolute -right-8 -top-4 size-64 rounded-full border border-white/10"></div>
            <div class="absolute -bottom-36 -left-20 size-96 rounded-full bg-white/5"></div>
            <a href="{{ route('register') }}" class="relative inline-flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-2xl bg-white text-sm font-bold text-take-olive">tk</span>
                <span class="text-xl font-bold tracking-tight">Take</span>
            </a>
            <div class="relative max-w-xl">
                <p class="mb-5 text-xs font-bold uppercase tracking-[0.24em] text-lime-100/80">Mulai dari lingkunganmu</p>
                <h1 class="text-5xl font-semibold leading-[1.08] tracking-tight xl:text-6xl">Satu akun, banyak kebaikan bersama.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-white/75">Bergabung untuk melihat dan menggunakan inventaris yang dirawat bersama warga.</p>
                <div class="mt-10 flex items-center gap-3 text-sm text-white/80">
                    <span class="grid size-9 place-items-center rounded-full bg-white/10">✳</span>
                    <span>Data Anda membantu pengelolaan yang tertib</span>
                </div>
            </div>
            <p class="relative text-xs text-white/55">Dikelola bersama warga, untuk warga.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-10 lg:px-14">
            <div class="w-full max-w-lg">
                <div class="mb-8 lg:hidden">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-2xl bg-take-olive text-sm font-bold text-white">tk</span>
                        <span class="text-xl font-bold tracking-tight">Take</span>
                    </a>
                </div>
                <p class="text-sm font-semibold text-take-clay">Selamat datang di lingkungan Take</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight">Buat akun warga</h2>
                <p class="mt-3 text-sm leading-6 text-stone-500">Lengkapi data agar pengurus dapat mengenali Anda.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-7 grid gap-x-4 gap-y-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-2 block text-sm font-semibold text-stone-700">Nama lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Nama sesuai panggilan warga">
                        @error('name')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-stone-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="nama@email.com">
                        @error('email')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="mb-2 block text-sm font-semibold text-stone-700">No. WhatsApp</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="08xxxxxxxxxx">
                        @error('phone')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-semibold text-stone-700">Alamat blok / RT / RW</label>
                        <input id="address" name="address" type="text" value="{{ old('address') }}" required autocomplete="street-address" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Contoh: Blok A No. 12, RT 02 / RW 05">
                        @error('address')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-stone-700">Kata sandi</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Minimal 8 karakter">
                        @error('password')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-stone-700">Ulangi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-stone-200 bg-take-cream/50 px-4 py-3 text-sm outline-none transition focus:border-take-olive focus:ring-4 focus:ring-take-olive/10" placeholder="Ulangi kata sandi">
                    </div>
                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 sm:col-span-2" role="alert">
                            Periksa kembali isian yang ditandai.
                        </div>
                    @endif
                    <button type="submit" class="mt-1 flex w-full items-center justify-center gap-2 rounded-xl bg-take-olive px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-take-olive/15 transition hover:bg-[#294231] focus:outline-none focus:ring-4 focus:ring-take-olive/20 sm:col-span-2">
                        Buat akun warga
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="mt-7 text-center text-sm text-stone-500">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}" class="font-bold text-take-olive hover:underline">Masuk</a>
                </p>
            </div>
        </section>
    </main>
@endsection
