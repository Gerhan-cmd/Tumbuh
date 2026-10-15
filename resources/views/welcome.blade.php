<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tumbuh · Rawat tanaman dengan minum air</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-leaf-50 text-gray-800">

    {{-- Menu atas --}}
    <header class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <x-application-logo class="h-9 w-9" />
            <span class="text-lg font-bold text-leaf-800">Tumbuh</span>
        </a>
        <nav class="flex items-center gap-2 text-sm">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg bg-leaf-500 px-4 py-2 font-semibold text-white hover:bg-leaf-600">Buka Kebunku</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 font-medium text-leaf-800 hover:bg-leaf-100">Masuk</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="rounded-lg bg-leaf-500 px-4 py-2 font-semibold text-white hover:bg-leaf-600">Daftar</a>
                @endif
            @endauth
        </nav>
    </header>

    <main>
        {{-- Pembuka --}}
        <section class="mx-auto grid max-w-5xl items-center gap-8 px-4 py-10 sm:grid-cols-2 sm:py-16">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-water-500">Untuk kamu yang lama di depan laptop</p>
                <h1 class="mt-2 text-4xl font-extrabold leading-tight text-leaf-800 sm:text-5xl">
                    Setiap gelas air menyiram tanamanmu.
                </h1>
                <p class="mt-4 text-lg text-gray-600">
                    Tumbuh membantu membangun kebiasaan minum air dan bergerak. Catat dengan satu ketukan,
                    kumpulkan XP, dan lihat tanamanmu tumbuh dari bibit sampai pohon.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="rounded-xl bg-leaf-500 px-6 py-3 text-base font-bold text-white hover:bg-leaf-600">Buka Kebunku</a>
                    @else
                        <a href="{{ route('register') }}"
                           class="rounded-xl bg-leaf-500 px-6 py-3 text-base font-bold text-white hover:bg-leaf-600">Mulai Menanam</a>
                        <a href="{{ route('login') }}"
                           class="rounded-xl border border-leaf-500 px-6 py-3 text-base font-semibold text-leaf-700 hover:bg-white">Sudah punya akun</a>
                    @endauth
                </div>
            </div>

            <div class="flex justify-center">
                <div class="relative rounded-3xl bg-white p-6 shadow-sm">
                    <img src="{{ asset('img/plants/level-5.svg') }}" alt="Tanaman level 5" class="h-56 w-56">
                    <span class="absolute -right-3 top-6 rounded-full bg-xp-500 px-3 py-1 text-sm font-bold text-white shadow">+10 XP</span>
                    <span class="absolute -left-3 bottom-8 rounded-full bg-water-500 px-3 py-1 text-sm font-bold text-white shadow">6/8 gelas</span>
                </div>
            </div>
        </section>

        {{-- Fitur --}}
        <section class="bg-white py-12">
            <div class="mx-auto max-w-5xl px-4">
                <h2 class="text-center text-2xl font-bold text-leaf-800">Kebiasaan sehat yang terasa seperti bermain</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Catat sekali ketuk', 'Minum, ketuk, dan langsung dapat XP. Air di progress bar naik seketika.', 'bg-water-100 text-water-700'],
                        ['8 tahap tumbuh', 'Dari bibit sampai pohon legenda. Tanamanmu cerminan konsistensimu.', 'bg-leaf-100 text-leaf-800'],
                        ['Misi & Peti Harian', 'Empat misi baru setiap hari. Selesaikan tiga untuk membuka peti hadiah.', 'bg-xp-100 text-xp-700'],
                        ['Squad bersama teman', 'Kejar target gelas bersama dan lihat peringkat mingguan squad-mu.', 'bg-leaf-100 text-leaf-800'],
                    ] as [$title, $text, $color])
                        <div class="rounded-2xl border border-gray-100 p-5">
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $color }}">{{ $loop->iteration }}</span>
                            <h3 class="mt-3 font-semibold text-gray-800">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Aturan yang ramah --}}
        <section class="mx-auto max-w-5xl px-4 py-12">
            <div class="rounded-3xl bg-leaf-800 p-8 text-white sm:p-10">
                <h2 class="text-2xl font-bold">Dirancang ramah, bukan menghukum</h2>
                <ul class="mt-4 space-y-2 text-leaf-100">
                    <li>Pengingat dihitung dari gelas terakhirmu, diam saat malam, dan maksimal 6 kali sehari.</li>
                    <li>Pelindung streak menjaga streak-mu saat ada hari yang terlewat.</li>
                    <li>Peringkat hanya terlihat oleh teman satu squad, tidak ada peringkat global.</li>
                </ul>
                @guest
                    <a href="{{ route('register') }}"
                       class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-bold text-leaf-800 hover:bg-leaf-100">Tanam bibit pertamamu</a>
                @endguest
            </div>
        </section>
    </main>

    <footer class="pb-10 text-center text-xs text-gray-500">
        Tumbuh &middot; Tugas Gamification Design &middot; Teknik Informatika Universitas Halu Oleo
    </footer>
</body>
</html>
