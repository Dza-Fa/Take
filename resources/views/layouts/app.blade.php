<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Take') · Take</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-take-cream font-sans text-take-ink antialiased">
    @auth
        <header class="sticky top-0 z-30 border-b border-take-line bg-take-cream/95 backdrop-blur" x-data="{ menuOpen: false }">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-4 py-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3" aria-label="Take, beranda">
                    <span class="grid size-10 place-items-center rounded-2xl bg-take-olive text-sm font-bold tracking-tight text-white">tk</span>
                    <span>
                        <span class="block text-lg font-bold leading-none tracking-tight">Take</span>
                        <span class="mt-1 block text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-500">Aset bersama</span>
                    </span>
                </a>

                <div class="hidden items-center gap-2 md:flex">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.assets.index') }}" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-white text-take-olive shadow-sm' => request()->routeIs('admin.assets.*'),
                            'text-stone-600 hover:bg-white/70 hover:text-take-ink' => ! request()->routeIs('admin.assets.*'),
                        ])>Aset</a>
                        <a href="{{ route('admin.categories.index') }}" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-white text-take-olive shadow-sm' => request()->routeIs('admin.categories.*'),
                            'text-stone-600 hover:bg-white/70 hover:text-take-ink' => ! request()->routeIs('admin.categories.*'),
                        ])>Kategori</a>
                    @else
                        <a href="{{ route('dashboard') }}" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-white text-take-olive shadow-sm' => request()->routeIs('dashboard'),
                            'text-stone-600 hover:bg-white/70 hover:text-take-ink' => ! request()->routeIs('dashboard'),
                        ])>Beranda</a>
                        <a href="{{ route('catalog.index') }}" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-white text-take-olive shadow-sm' => request()->routeIs('catalog.*'),
                            'text-stone-600 hover:bg-white/70 hover:text-take-ink' => ! request()->routeIs('catalog.*'),
                        ])>Katalog aset</a>
                        <a href="{{ route('my-borrowings.index') }}" @class([
                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                            'bg-white text-take-olive shadow-sm' => request()->routeIs('my-borrowings.*'),
                            'text-stone-600 hover:bg-white/70 hover:text-take-ink' => ! request()->routeIs('my-borrowings.*'),
                        ])>Pinjaman saya</a>
                    @endif
                </div>

                <div class="hidden items-center gap-4 md:flex">
                    <span class="text-sm font-medium text-stone-600">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-take-line px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-take-olive hover:text-take-olive">
                            Keluar
                        </button>
                    </form>
                </div>

                <button type="button" class="grid size-10 place-items-center rounded-xl border border-take-line text-take-ink md:hidden" @click="menuOpen = ! menuOpen" :aria-expanded="menuOpen.toString()" aria-label="Buka navigasi">
                    <svg x-show="! menuOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg x-cloak x-show="menuOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </nav>

            <div x-cloak x-show="menuOpen" x-transition class="border-t border-take-line px-4 pb-4 md:hidden">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 pt-3">
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.assets.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-white">Kelola aset</a>
                        <a href="{{ route('admin.categories.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-white">Kelola kategori</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-white">Beranda warga</a>
                        <a href="{{ route('catalog.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-white">Katalog aset</a>
                        <a href="{{ route('my-borrowings.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-white">Pinjaman saya</a>
                    @endif
                    <div class="mt-2 flex items-center justify-between border-t border-take-line pt-4">
                        <span class="text-sm text-stone-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-take-olive">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
    @endauth

    @if (session('success') || session('error'))
        <div
            x-data="{ visible: true }"
            x-init="setTimeout(() => visible = false, 5000)"
            x-show="visible"
            x-transition.opacity.duration.200ms
            class="fixed right-4 top-4 z-50 w-[calc(100%-2rem)] max-w-md"
            role="status"
            aria-live="polite"
        >
            @if (session('success'))
                <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-white p-4 text-emerald-900 shadow-xl shadow-emerald-950/10">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">✓</span>
                    <p class="flex-1 pt-1 text-sm font-medium">{{ session('success') }}</p>
                    <button type="button" class="text-emerald-700" @click="visible = false" aria-label="Tutup notifikasi">×</button>
                </div>
            @else
                <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white p-4 text-rose-900 shadow-xl shadow-rose-950/10">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose-100 text-rose-700" aria-hidden="true">!</span>
                    <p class="flex-1 pt-1 text-sm font-medium">{{ session('error') }}</p>
                    <button type="button" class="text-rose-700" @click="visible = false" aria-label="Tutup notifikasi">×</button>
                </div>
            @endif
        </div>
    @endif

    <main class="min-h-screen @auth flex flex-col @endauth">
        <div class="@auth flex-1 @endauth">
            @yield('content')
        </div>

        @auth
            <footer class="border-t border-take-line bg-white/40">
                <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <span class="font-semibold text-stone-700">Take <span class="font-normal text-stone-500">· Aset bersama, manfaat untuk semua.</span></span>
                    <span>Lingkungan tumbuh saat kita saling berbagi.</span>
                </div>
            </footer>
        @endauth
    </main>
</body>
</html>
