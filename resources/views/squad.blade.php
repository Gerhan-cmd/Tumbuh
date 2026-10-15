<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-leaf-800 leading-tight">Squad</h2>
            @include('partials.tumbuh-nav')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 space-y-4">

            @if (session('status'))
                <div class="rounded-lg bg-leaf-100 px-4 py-3 text-sm text-leaf-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (! $squad)
                {{-- ================= Belum punya squad ================= --}}
                <section class="rounded-2xl bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-800">Kenapa bergabung dengan squad?</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Kebiasaan lebih mudah dijaga bersama teman. Di squad kamu bisa melihat peringkat XP mingguan,
                        dan mengejar target total gelas bersama. Kalau tercapai, semua anggota mendapat bonus XP.
                        Maksimal {{ $maxMembers }} orang per squad.
                    </p>
                </section>

                <div class="grid gap-4 sm:grid-cols-2">
                    <form method="POST" action="{{ route('squad.store') }}" class="rounded-2xl bg-white p-5 shadow-sm">
                        @csrf
                        <h3 class="font-semibold text-gray-800">Buat squad baru</h3>
                        <label for="name" class="mt-3 block text-sm text-gray-600">Nama squad</label>
                        <input id="name" name="name" type="text" maxlength="40" value="{{ old('name') }}"
                               placeholder="Contoh: Anak TI 23"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-leaf-500 focus:ring-leaf-500">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        <button type="submit"
                                class="mt-4 w-full rounded-lg bg-leaf-500 px-4 py-2.5 font-semibold text-white hover:bg-leaf-600">
                            Buat Squad
                        </button>
                    </form>

                    <form method="POST" action="{{ route('squad.join') }}" class="rounded-2xl bg-white p-5 shadow-sm">
                        @csrf
                        <h3 class="font-semibold text-gray-800">Gabung dengan kode</h3>
                        <label for="code" class="mt-3 block text-sm text-gray-600">Kode undangan (6 karakter)</label>
                        <input id="code" name="code" type="text" maxlength="8" value="{{ old('code') }}"
                               placeholder="Contoh: GU4ASE" autocomplete="off"
                               class="mt-1 w-full rounded-lg border-gray-300 font-mono uppercase tracking-widest focus:border-leaf-500 focus:ring-leaf-500">
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        <button type="submit"
                                class="mt-4 w-full rounded-lg bg-water-500 px-4 py-2.5 font-semibold text-white hover:bg-water-700">
                            Gabung
                        </button>
                    </form>
                </div>
            @else
                {{-- ================= Sudah punya squad ================= --}}
                @php $c = $squad['challenge']; @endphp

                {{-- Identitas squad + kode undangan --}}
                <section class="rounded-2xl bg-white p-5 shadow-sm"
                         x-data="{ copied: false, code: @js($squad['invite_code']) }">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-leaf-800">{{ $squad['name'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $squad['member_count'] }}/{{ $squad['max_members'] }} anggota</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Kode undangan</p>
                            <p class="font-mono text-xl font-bold tracking-widest text-gray-800">{{ $squad['invite_code'] }}</p>
                        </div>
                    </div>
                    @if ($squad['member_count'] < $squad['max_members'])
                        <button type="button"
                                @click="navigator.clipboard?.writeText(`Yuk gabung squad Tumbuh-ku! Buka menu Squad lalu masukkan kode ${code}`).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="mt-3 w-full rounded-lg border border-leaf-500 px-4 py-2 text-sm font-semibold text-leaf-700 hover:bg-leaf-50">
                            <span x-show="!copied">Salin ajakan bergabung</span>
                            <span x-show="copied" style="display: none">Tersalin! Tempel di grup WhatsApp</span>
                        </button>
                    @endif
                </section>

                {{-- Tantangan mingguan --}}
                <section class="rounded-2xl p-5 shadow-sm {{ $c['completed'] ? 'bg-leaf-100' : 'bg-white' }}">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-semibold text-gray-800">Tantangan minggu ini</h3>
                        <span class="text-xs text-gray-500">
                            {{ $c['completed'] ? 'Selesai' : 'Sisa '.$c['days_left'].' hari' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Kumpulkan {{ $c['target'] }} gelas bersama sebelum Minggu malam.
                        Semua anggota mendapat +{{ $c['xp'] }} XP.
                    </p>
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-water-100">
                        <div class="h-full rounded-full {{ $c['completed'] ? 'bg-leaf-500' : 'bg-water-500' }}"
                             style="width: {{ $c['target'] ? min(100, round($c['progress'] / $c['target'] * 100)) : 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-sm font-semibold {{ $c['completed'] ? 'text-leaf-800' : 'text-gray-700' }}">
                        {{ $c['progress'] }}/{{ $c['target'] }} gelas
                        @if ($c['completed'])
                            &middot; Tercapai! +{{ $c['xp'] }} XP sudah masuk ke semua anggota.
                        @endif
                    </p>
                </section>

                {{-- Leaderboard --}}
                <section class="rounded-2xl bg-white p-5 shadow-sm">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-semibold text-gray-800">Peringkat minggu ini</h3>
                        <span class="text-xs text-gray-500">Mulai dari nol setiap Senin</span>
                    </div>
                    <ol class="mt-3 divide-y divide-gray-100">
                        @foreach ($squad['leaderboard'] as $row)
                            <li class="flex items-center gap-3 py-2.5 {{ $row['is_me'] ? '-mx-2 rounded-lg bg-leaf-50 px-2' : '' }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                    {{ $row['rank'] === 1 ? 'bg-xp-500 text-white' : ($row['rank'] <= 3 ? 'bg-xp-100 text-xp-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $row['rank'] }}
                                </span>
                                <span class="flex-1 text-sm text-gray-800">
                                    {{ $row['name'] }}
                                    @if ($row['is_me']) <span class="text-xs text-leaf-700">(kamu)</span> @endif
                                    @if ($row['is_owner']) <span class="text-xs text-gray-400">&middot; pembuat</span> @endif
                                </span>
                                <span class="text-xs text-gray-500">{{ $row['glasses'] }} gelas</span>
                                <span class="w-16 text-right text-sm font-bold text-xp-500">{{ $row['xp'] }} XP</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-xs text-gray-500">
                        Peringkat hanya terlihat oleh anggota squad ini, bukan oleh semua pengguna.
                    </p>
                </section>

                {{-- Keluar --}}
                <form method="POST" action="{{ route('squad.leave') }}" class="text-center"
                      onsubmit="return confirm('Yakin keluar dari squad ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-gray-500 underline hover:text-red-600">Keluar dari squad</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
