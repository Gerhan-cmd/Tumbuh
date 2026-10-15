<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-leaf-800 leading-tight">Pengaturan</h2>
            @include('partials.tumbuh-nav')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto px-4 space-y-4">

            @if (session('status'))
                <div class="rounded-lg bg-leaf-100 px-4 py-3 text-sm text-leaf-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}"
                  x-data="{ target: {{ old('water_target', $user->water_target) }} }"
                  class="rounded-2xl bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                {{-- Target minum --}}
                <h3 class="font-semibold text-gray-800">Target minum per hari</h3>
                <div class="mt-3 flex items-center justify-between gap-4">
                    <button type="button" @click="target = Math.max({{ $min }}, target - 1)"
                            class="h-10 w-10 rounded-full border border-water-500 text-xl font-bold text-water-500 hover:bg-water-100"
                            aria-label="Kurangi satu gelas">&minus;</button>
                    <div class="text-center">
                        <span class="text-4xl font-bold text-water-500" x-text="target"></span>
                        <span class="text-gray-600"> gelas</span>
                        <p class="text-xs text-gray-500">
                            &plusmn; <span x-text="(target * 0.25).toFixed(2).replace('.', ',')"></span> liter
                        </p>
                    </div>
                    <button type="button" @click="target = Math.min({{ $max }}, target + 1)"
                            class="h-10 w-10 rounded-full border border-water-500 text-xl font-bold text-water-500 hover:bg-water-100"
                            aria-label="Tambah satu gelas">+</button>
                </div>
                <input type="hidden" name="water_target" :value="target">
                <p class="mt-2 text-xs text-gray-500">
                    Mengubah target langsung berlaku untuk hari ini. Streak dan misi ikut menyesuaikan.
                </p>
                <x-input-error :messages="$errors->get('water_target')" class="mt-2" />

                {{-- Pengingat --}}
                <h3 class="mt-6 font-semibold text-gray-800">Pengingat</h3>
                <div class="mt-3 space-y-3">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-leaf-50">
                        <input type="hidden" name="reminder_enabled" value="0">
                        <input type="checkbox" name="reminder_enabled" value="1"
                               class="mt-1 rounded border-gray-300 text-leaf-500 focus:ring-leaf-500"
                               @checked(old('reminder_enabled', $user->reminder_enabled))>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Ingatkan aku minum</span>
                            <span class="block text-xs text-gray-500">
                                {{ $reminders['water_every'] }} menit setelah gelas terakhir, selama aplikasi terbuka.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-leaf-50">
                        <input type="hidden" name="walk_reminder_enabled" value="0">
                        <input type="checkbox" name="walk_reminder_enabled" value="1"
                               class="mt-1 rounded border-gray-300 text-leaf-500 focus:ring-leaf-500"
                               @checked(old('walk_reminder_enabled', $user->walk_reminder_enabled))>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Ajak aku jalan sebentar</span>
                            <span class="block text-xs text-gray-500">
                                Setiap {{ intdiv($reminders['walk_every'], 60) }} jam duduk di depan aplikasi.
                            </span>
                        </span>
                    </label>
                </div>
                <p class="mt-3 text-xs text-gray-500">
                    Pengingat tidak muncul pukul {{ sprintf('%02d.00', $reminders['quiet_start']) }}&ndash;{{ sprintf('%02d.00', $reminders['quiet_end']) }}
                    dan maksimal {{ $reminders['max_per_day'] }} kali sehari.
                </p>

                <button type="submit"
                        class="mt-6 w-full rounded-lg bg-leaf-500 px-4 py-3 font-semibold text-white hover:bg-leaf-600 focus:outline-none focus:ring-2 focus:ring-leaf-500 focus:ring-offset-2">
                    Simpan Pengaturan
                </button>
            </form>

            {{-- Pasang sebagai aplikasi (PWA) --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm"
                     x-data="{
                        can: !!window.tumbuhInstallPrompt,
                        installed: window.matchMedia('(display-mode: standalone)').matches,
                        ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
                        async install() {
                            const prompt = window.tumbuhInstallPrompt;
                            if (!prompt) return;
                            prompt.prompt();
                            const choice = await prompt.userChoice;
                            if (choice.outcome === 'accepted') this.installed = true;
                            window.tumbuhInstallPrompt = null;
                            this.can = false;
                        },
                     }"
                     @tumbuh-installable.window="can = true">
                <h3 class="font-semibold text-gray-800">Pasang Tumbuh di HP</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Tumbuh bisa dipasang seperti aplikasi: punya ikon sendiri di layar utama dan terbuka tanpa bilah browser.
                </p>

                <template x-if="installed">
                    <p class="mt-3 rounded-lg bg-leaf-100 px-3 py-2 text-sm text-leaf-800">Tumbuh sudah terpasang sebagai aplikasi.</p>
                </template>
                <template x-if="!installed && can">
                    <button type="button" @click="install()"
                            class="mt-3 w-full rounded-lg bg-leaf-500 px-4 py-2.5 font-semibold text-white hover:bg-leaf-600">
                        Pasang aplikasi
                    </button>
                </template>
                <template x-if="!installed && !can && ios">
                    <p class="mt-3 text-sm text-gray-700">
                        Di iPhone: buka Tumbuh di Safari, ketuk tombol <strong>Bagikan</strong>, lalu pilih
                        <strong>Tambahkan ke Layar Utama</strong>.
                    </p>
                </template>
                <template x-if="!installed && !can && !ios">
                    <p class="mt-3 text-sm text-gray-700">
                        Buka menu browser (titik tiga di pojok kanan atas), lalu pilih <strong>Instal aplikasi</strong>
                        atau <strong>Tambahkan ke layar utama</strong>. Pilihan ini hanya muncul kalau situs dibuka lewat https.
                    </p>
                </template>
            </section>

            <p class="text-center text-xs text-gray-500">
                Nama, email, dan kata sandi diubah di
                <a href="{{ route('profile.edit') }}" class="font-medium text-leaf-700 underline">halaman Profil</a>.
            </p>
        </div>
    </div>
</x-app-layout>
