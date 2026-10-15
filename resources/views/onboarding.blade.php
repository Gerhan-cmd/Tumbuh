<x-guest-layout>
    <div class="text-center mb-6">
        <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-leaf-100">
            {{-- Bibit sederhana --}}
            <svg viewBox="0 0 48 48" class="h-10 w-10" aria-hidden="true">
                <path d="M14 34h20l-3 10H17z" fill="#c9774a"/>
                <path d="M24 34V22" stroke="#2f8a53" stroke-width="3" stroke-linecap="round"/>
                <path d="M24 26c-7 0-11-5-11-10 6 0 11 4 11 10z" fill="#5fb882"/>
                <path d="M24 23c0-7 5-11 11-11 0 6-4 11-11 11z" fill="#3fa968"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-leaf-800">Halo, {{ $user->name }}!</h1>
        <p class="mt-1 text-sm text-gray-600">
            Atur target harianmu dulu. Setiap gelas air yang kamu minum akan menyiram tanamanmu.
        </p>
    </div>

    <form method="POST" action="{{ route('onboarding.update') }}"
          x-data="{ target: {{ old('water_target', $user->water_target) }} }">
        @csrf
        @method('PATCH')

        {{-- Target minum --}}
        <div>
            <x-input-label for="water_target" value="Target minum per hari" />

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

            <input type="hidden" id="water_target" name="water_target" :value="target">
            <p class="mt-2 text-xs text-gray-500">Anjuran umum: 8 gelas (250 ml) sehari.</p>
            <x-input-error :messages="$errors->get('water_target')" class="mt-2" />
        </div>

        {{-- Pengingat --}}
        <div class="mt-6 space-y-3">
            <x-input-label value="Pengingat" />

            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-leaf-50">
                <input type="hidden" name="reminder_enabled" value="0">
                <input type="checkbox" name="reminder_enabled" value="1"
                       class="mt-1 rounded border-gray-300 text-leaf-500 focus:ring-leaf-500"
                       @checked(old('reminder_enabled', $user->reminder_enabled))>
                <span>
                    <span class="block text-sm font-medium text-gray-800">Ingatkan aku minum</span>
                    <span class="block text-xs text-gray-500">Kira-kira setiap 90 menit saat aplikasi terbuka.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-leaf-50">
                <input type="hidden" name="walk_reminder_enabled" value="0">
                <input type="checkbox" name="walk_reminder_enabled" value="1"
                       class="mt-1 rounded border-gray-300 text-leaf-500 focus:ring-leaf-500"
                       @checked(old('walk_reminder_enabled', $user->walk_reminder_enabled))>
                <span>
                    <span class="block text-sm font-medium text-gray-800">Ajak aku jalan sebentar</span>
                    <span class="block text-xs text-gray-500">Ajakan jalan 5 menit setiap 2 jam.</span>
                </span>
            </label>
        </div>

        <button type="submit"
                class="mt-6 w-full rounded-lg bg-leaf-500 px-4 py-3 font-semibold text-white hover:bg-leaf-600 focus:outline-none focus:ring-2 focus:ring-leaf-500 focus:ring-offset-2">
            Mulai Menanam
        </button>

        <p class="mt-3 text-center text-xs text-gray-500">Semua pengaturan bisa diubah lagi nanti.</p>
    </form>
</x-guest-layout>
