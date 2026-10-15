<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-leaf-800 leading-tight">Kebun Sehatku</h2>
            @include('partials.tumbuh-nav')
        </div>
    </x-slot>

    <div class="py-8" x-data="waterTracker(@js($initial))">
        <div class="max-w-md mx-auto px-4 space-y-4">

            {{-- Pesan dari onboarding --}}
            @if (session('status'))
                <div class="rounded-lg bg-leaf-100 px-4 py-3 text-sm text-leaf-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Pemberitahuan streak setelah ada hari yang terlewat --}}
            <div x-show="streak_notice" style="display: none" role="status"
                 class="flex items-start justify-between gap-3 rounded-2xl border border-xp-500 bg-xp-100 p-4 text-sm text-xp-700">
                <span x-text="streak_notice"></span>
                <button type="button" @click="streak_notice = null" class="shrink-0 font-semibold underline">Tutup</button>
            </div>

            {{-- Pengingat minum / jalan kaki --}}
            <div x-show="reminder" x-transition style="display: none" role="status"
                 class="rounded-2xl border p-4"
                 :class="reminder?.type === 'walk' ? 'border-leaf-300 bg-leaf-100' : 'border-water-300 bg-water-100'">
                <p class="font-semibold" :class="reminder?.type === 'walk' ? 'text-leaf-800' : 'text-water-700'"
                   x-text="reminder?.title"></p>
                <p class="mt-1 text-sm text-gray-700" x-text="reminder?.text"></p>
                <div class="mt-3 flex gap-2">
                    <button type="button" x-show="reminder?.type === 'water'" @click="drinkFromReminder()"
                            class="rounded-lg bg-water-500 px-4 py-2 text-sm font-semibold text-white hover:bg-water-700">
                        Sudah minum, catat
                    </button>
                    <button type="button" @click="reminder = null"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            x-text="reminder?.type === 'walk' ? 'Oke, jalan dulu' : 'Nanti dulu'"></button>
                </div>
            </div>

            {{-- Minta izin notifikasi browser (hanya jika pengingat aktif dan belum pernah ditanya) --}}
            <div x-show="canAskNotification" style="display: none"
                 class="flex items-center justify-between gap-3 rounded-2xl bg-white p-4 text-sm shadow-sm">
                <span class="text-gray-600">Izinkan notifikasi supaya pengingat tetap muncul saat kamu membuka tab lain.</span>
                <button type="button" @click="askNotification()"
                        class="shrink-0 rounded-lg bg-leaf-500 px-3 py-2 font-semibold text-white hover:bg-leaf-600">
                    Izinkan
                </button>
            </div>

            {{-- Kartu tanaman & XP --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Halo, {{ $user->name }}</span>
                    <span class="rounded-full bg-leaf-100 px-3 py-1 font-semibold text-leaf-700">
                        Level <span x-text="progress.level"></span> · <span x-text="progress.plant"></span>
                    </span>
                </div>

                {{-- Streak dan pelindung streak --}}
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 font-semibold"
                          :class="streak.counted_today ? 'bg-xp-100 text-xp-700' : 'bg-gray-100 text-gray-600'">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M12.5 2c.6 3.2-1.2 5-2.8 6.8C8.2 10.5 7 12.2 7 14.5a5 5 0 0 0 10 0c0-2.3-1.1-3.9-2.2-5.5-.3 1.5-1.2 2.4-2.1 2.6.6-3.2.3-6.6-.2-9.6z"/>
                        </svg>
                        <span x-text="`Streak ${streak.current} hari`"></span>
                    </span>
                    <span x-show="streak.freezes > 0"
                          class="inline-flex items-center gap-1 rounded-full bg-water-100 px-3 py-1 font-semibold text-water-700">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M12 2l8 3v6c0 5-3.4 9-8 11-4.6-2-8-6-8-11V5z"/>
                        </svg>
                        <span x-text="`${streak.freezes} pelindung`"></span>
                    </span>
                    <span class="text-gray-500" x-text="streakHint"></span>
                </div>

                {{-- Tanaman dalam pot sesuai level + animasi siram --}}
                <div class="relative my-4 flex h-52 items-end justify-center overflow-hidden rounded-xl bg-leaf-50">
                    <img :src="plantUrl(progress.level)" :alt="`Tanaman level ${progress.level}: ${progress.plant}`"
                         class="h-52 w-52 select-none" draggable="false"
                         :class="{ 'tumbuh-bounce': justWatered, 'tumbuh-grow': justGrew }">

                    {{-- Tetes air saat menyiram --}}
                    <template x-if="justWatered">
                        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                            <span class="tumbuh-drop" style="left: 38%; animation-delay: 0ms"></span>
                            <span class="tumbuh-drop" style="left: 50%; animation-delay: 120ms"></span>
                            <span class="tumbuh-drop" style="left: 62%; animation-delay: 240ms"></span>
                        </div>
                    </template>

                    {{-- XP melayang --}}
                    <template x-if="floatXp">
                        <span class="tumbuh-float pointer-events-none absolute left-1/2 top-6 -translate-x-1/2 rounded-full bg-xp-500 px-3 py-1 text-sm font-bold text-white shadow"
                              x-text="floatXp" aria-hidden="true"></span>
                    </template>
                </div>

                <p class="mb-3 text-center text-sm text-gray-600" x-text="moodText"></p>

                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <template x-if="!progress.is_max">
                        <span>XP menuju Level <span x-text="progress.level + 1"></span></span>
                    </template>
                    <template x-if="progress.is_max">
                        <span>Level maksimal tercapai</span>
                    </template>
                    <span x-show="!progress.is_max" x-text="`${progress.xp_in_level}/${progress.xp_needed}`"></span>
                </div>
                <div class="h-2.5 rounded-full bg-xp-100 overflow-hidden">
                    <div class="h-full rounded-full bg-xp-500 transition-all duration-500"
                         :style="`width: ${progress.percent}%`"></div>
                </div>
                <p class="mt-1 text-right text-xs text-gray-500">Total <span x-text="progress.xp_total"></span> XP</p>
            </section>

            {{-- Kartu minum hari ini --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-800">Air minum hari ini</h3>
                    <span class="text-sm text-gray-500"><span x-text="glasses"></span>/<span x-text="target"></span> gelas</span>
                </div>

                <div class="mt-3 grid grid-cols-6 gap-2">
                    <template x-for="i in slots" :key="i">
                        <div class="h-11 rounded-b-lg rounded-t-sm border-2 border-water-500 bg-white relative overflow-hidden">
                            <div class="absolute inset-x-0 bottom-0 bg-water-300 transition-all duration-300"
                                 :style="i <= glasses ? 'height: 80%' : 'height: 0%'"></div>
                        </div>
                    </template>
                </div>

                <div class="mt-4 h-2.5 rounded-full bg-water-100 overflow-hidden">
                    <div class="h-full rounded-full bg-water-500 transition-all duration-500"
                         :style="`width: ${Math.min(100, Math.round(glasses / target * 100))}%`"></div>
                </div>

                <p class="mt-2 text-sm"
                   :class="goal_met ? 'text-leaf-700 font-semibold' : 'text-gray-500'"
                   x-text="goal_met
                        ? 'Target hari ini tercapai! Tanamanmu segar.'
                        : `${target - glasses} gelas lagi untuk mencapai target.`"></p>

                <button type="button" @click="addGlass()"
                        :disabled="busy || glasses >= limit"
                        class="mt-4 w-full rounded-xl bg-water-500 py-3 text-base font-bold text-white transition hover:bg-water-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="glasses < limit">+ Catat 1 Gelas</span>
                    <span x-show="glasses >= limit">Batas harian tercapai</span>
                </button>

                <div class="mt-2 text-center">
                    <button type="button" @click="undoGlass()" x-show="glasses > 0" :disabled="busy"
                            class="text-xs text-gray-500 underline hover:text-gray-700 disabled:opacity-50">
                        Batalkan gelas terakhir
                    </button>
                </div>

                <p class="mt-3 text-center text-xs text-gray-400">
                    Setiap gelas dihitung ke target. XP didapat jika berjarak minimal
                    {{ config('gamification.limits.water_xp_gap_min') }} menit dari gelas sebelumnya.
                </p>

            </section>

            {{-- Kartu jeda aktif --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-800">Jeda aktif</h3>
                    <span class="text-sm text-gray-500"><span x-text="breaks"></span> kali hari ini</span>
                </div>

                {{-- Belum mulai --}}
                <div x-show="!breakRunning">
                    <p class="mt-1 text-sm text-gray-600">
                        Berdiri dan regangkan badan selama <span x-text="breakDurationLabel"></span>.
                        Dapat +<span x-text="break_cfg.xp"></span> XP.
                    </p>
                    <button type="button" @click="startBreak()" :disabled="busy || !breakAvailable"
                            class="mt-4 w-full rounded-xl bg-leaf-500 py-3 text-base font-bold text-white transition hover:bg-leaf-600 disabled:cursor-not-allowed disabled:opacity-50"
                            x-text="breakButtonLabel"></button>
                </div>

                {{-- Timer berjalan --}}
                <div x-show="breakRunning" style="display: none" class="mt-3 text-center">
                    <p class="text-4xl font-bold tabular-nums text-leaf-700" x-text="breakClock" aria-live="off"></p>
                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-leaf-100">
                        <div class="h-full rounded-full bg-leaf-500" :style="`width: ${breakPercent}%`"></div>
                    </div>
                    <p class="mt-3 text-sm text-gray-700" x-text="breakTip"></p>
                    <button type="button" @click="cancelBreak()"
                            class="mt-3 text-xs text-gray-500 underline hover:text-gray-700">
                        Batalkan jeda
                    </button>
                </div>
            </section>

            {{-- Kartu misi harian + Peti Harian --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-800">Misi hari ini</h3>
                    <span class="text-sm text-gray-500">
                        <span x-text="missions.done"></span>/<span x-text="missions.list.length"></span> selesai
                    </span>
                </div>

                <ul class="mt-3 space-y-2">
                    <template x-for="(m, i) in missions.list" :key="i">
                        <li class="rounded-xl border p-3 transition"
                            :class="m.done ? 'border-leaf-200 bg-leaf-50' : 'border-gray-200'">
                            <div class="flex items-center gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2"
                                      :class="m.done ? 'border-leaf-500 bg-leaf-500 text-white' : 'border-gray-300'">
                                    <svg x-show="m.done" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor"
                                         stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 10.5l3 3 7-7"/>
                                    </svg>
                                </span>
                                <span class="flex-1 text-sm" :class="m.done ? 'text-gray-500 line-through' : 'text-gray-800'"
                                      x-text="m.title"></span>
                                <span class="text-xs font-bold text-xp-500" x-text="`+${m.xp} XP`"></span>
                            </div>
                            {{-- Progres hanya untuk misi yang butuh lebih dari 1 langkah --}}
                            <div x-show="!m.done && m.target > 1" class="mt-2 ml-9 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-leaf-500 transition-all duration-500"
                                         :style="`width: ${Math.round(m.progress / m.target * 100)}%`"></div>
                                </div>
                                <span class="text-xs text-gray-500" x-text="`${m.progress}/${m.target}`"></span>
                            </div>
                        </li>
                    </template>
                </ul>

                {{-- Peti Harian --}}
                <div class="mt-4 rounded-xl p-3 text-sm"
                     :class="missions.chest && !missions.chest.opened ? 'bg-xp-100' : 'bg-gray-50'">
                    <template x-if="!missions.chest">
                        <p class="text-gray-600">
                            Selesaikan <span x-text="missions.for_chest"></span> misi untuk membuka Peti Harian
                            (<span x-text="Math.min(missions.done, missions.for_chest)"></span>/<span x-text="missions.for_chest"></span>).
                        </p>
                    </template>
                    <template x-if="missions.chest && !missions.chest.opened">
                        <button type="button" @click="openChest()" :disabled="busy"
                                class="w-full rounded-xl bg-xp-500 py-3 font-bold text-white transition hover:bg-xp-700 disabled:opacity-50">
                            Buka Peti Harian
                        </button>
                    </template>
                    <template x-if="missions.chest && missions.chest.opened">
                        <p class="text-gray-700">
                            Peti hari ini sudah dibuka: <strong x-text="chestRewardText(missions.chest)"></strong>.
                            Misi baru menunggu besok.
                        </p>
                    </template>
                </div>
            </section>

            {{-- Kartu squad --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <template x-if="squad">
                    <div>
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-semibold text-gray-800">
                                Squad <span class="text-leaf-700" x-text="squad.name"></span>
                            </h3>
                            <span class="text-sm text-gray-500"
                                  x-text="`Peringkat #${squad.my_rank} dari ${squad.member_count}`"></span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600"
                           x-text="squad.challenge.completed
                                ? `Tantangan minggu ini tercapai! +${squad.challenge.xp} XP untuk semua.`
                                : `Tantangan bersama: ${squad.challenge.progress}/${squad.challenge.target} gelas, sisa ${squad.challenge.days_left} hari.`"></p>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-water-100">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="squad.challenge.completed ? 'bg-leaf-500' : 'bg-water-500'"
                                 :style="`width: ${squad.challenge.target ? Math.min(100, Math.round(squad.challenge.progress / squad.challenge.target * 100)) : 0}%`"></div>
                        </div>
                        <a :href="squad_url" class="mt-3 inline-block text-sm font-semibold text-leaf-700 underline">Lihat peringkat squad</a>
                    </div>
                </template>
                <template x-if="!squad">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-gray-600">Kebiasaan lebih awet kalau dijalani bersama. Ajak teman membentuk squad.</p>
                        <a :href="squad_url"
                           class="shrink-0 rounded-lg bg-leaf-500 px-3 py-2 text-sm font-semibold text-white hover:bg-leaf-600">
                            Buat / gabung
                        </a>
                    </div>
                </template>
            </section>

            {{-- Pesan kesalahan dari server (batas harian, jeda terlalu cepat, koneksi putus) --}}
            <p x-show="error" x-text="error" style="display: none" role="alert"
               class="rounded-xl bg-red-50 px-4 py-3 text-center text-sm text-red-700"></p>
        </div>

        {{-- Toast XP --}}
        <div x-show="toast" x-transition.opacity.duration.300ms
             class="fixed bottom-6 left-1/2 -translate-x-1/2 rounded-full bg-leaf-800 px-5 py-3 text-sm font-semibold text-white shadow-lg"
             x-text="toast" style="display: none"></div>

        {{-- Modal Peti Harian --}}
        <div x-show="chestOpen" x-transition style="display: none"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             @click.self="closeChest()">
            <div class="w-full max-w-xs rounded-2xl bg-white p-6 text-center shadow-xl">
                <svg viewBox="0 -14 120 114" class="mx-auto h-32 w-36" aria-hidden="true">
                    <ellipse cx="60" cy="93" rx="42" ry="5" fill="#fde6c4"/>
                    {{-- cahaya saat terbuka --}}
                    <g x-show="chestReward" class="tumbuh-grow">
                        <path d="M60 -8l3 9 9 3-9 3-3 9-3-9-9-3 9-3z" fill="#f4c542"/>
                        <path d="M25 22l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#f4c542"/>
                        <path d="M95 20l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#f4c542"/>
                    </g>
                    {{-- badan peti --}}
                    <rect x="20" y="48" width="80" height="42" rx="6" fill="#c9774a"/>
                    <rect x="20" y="48" width="80" height="8" fill="#b0603a"/>
                    <rect x="32" y="48" width="8" height="42" fill="#e79b2c"/>
                    <rect x="80" y="48" width="8" height="42" fill="#e79b2c"/>
                    {{-- cahaya dari dalam peti --}}
                    <ellipse cx="60" cy="50" rx="34" ry="7" fill="#fff1b8" x-show="chestReward"/>
                    <path d="M34 50L46 10H74L86 50z" fill="#fff6d6" opacity=".7" x-show="chestReward"/>
                    {{-- tutup: miring ke atas saat terbuka --}}
                    <g style="transition: transform .5s ease; transform-origin: 20px 48px"
                       :style="chestReward ? 'transform: rotate(-20deg)' : ''">
                        <path d="M20 48V36a14 14 0 0 1 14-14h52a14 14 0 0 1 14 14v12z" fill="#d9874f"/>
                        <rect x="32" y="22" width="8" height="26" fill="#f0a63a"/>
                        <rect x="80" y="22" width="8" height="26" fill="#f0a63a"/>
                    </g>
                    {{-- gembok --}}
                    <rect x="53" y="44" width="14" height="16" rx="3" fill="#f4c542" x-show="!chestReward"/>
                </svg>
                <p class="mt-2 text-sm font-semibold uppercase tracking-wide text-xp-500">Peti Harian</p>
                <p class="mt-1 text-2xl font-bold text-leaf-800"
                   x-text="chestReward ? chestRewardText(chestReward) : 'Membuka peti...'"></p>
                <p class="mt-1 text-sm text-gray-600" x-show="chestReward"
                   x-text="(chestReward?.type ?? chestReward?.reward_type) === 'freeze'
                        ? 'Pelindung ini menjaga streak-mu saat ada hari yang terlewat.'
                        : 'Bonus XP langsung ditambahkan ke tanamanmu.'"></p>
                <button type="button" @click="closeChest()" :disabled="!chestReward"
                        class="mt-5 w-full rounded-xl bg-leaf-500 py-2.5 font-semibold text-white hover:bg-leaf-600 disabled:opacity-50">
                    Lanjutkan
                </button>
            </div>
        </div>

        {{-- Modal naik level --}}
        <div x-show="levelUp" x-transition style="display: none"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
             @click.self="levelUp = false">
            <div class="w-full max-w-xs rounded-2xl bg-white p-6 text-center shadow-xl">
                <img :src="fullUrl(progress.level)" alt="" class="tumbuh-grow mx-auto h-40 w-40">
                <p class="text-sm font-semibold uppercase tracking-wide text-xp-500">Naik level!</p>
                <p class="mt-2 text-2xl font-bold text-leaf-800">
                    Level <span x-text="progress.level"></span>
                </p>
                <p class="mt-1 text-gray-600">Tanamanmu kini menjadi <strong x-text="progress.plant"></strong>.</p>
                <button type="button" @click="levelUp = false"
                        class="mt-5 w-full rounded-xl bg-leaf-500 py-2.5 font-semibold text-white hover:bg-leaf-600">
                    Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Animasi beranda Tumbuh */
        @keyframes tumbuh-bounce {
            0%, 100% { transform: scale(1); }
            30% { transform: scale(1.06, 0.95); }
            60% { transform: scale(0.97, 1.04); }
        }
        @keyframes tumbuh-grow {
            0% { transform: scale(0.6); opacity: 0.4; }
            70% { transform: scale(1.08); opacity: 1; }
            100% { transform: scale(1); }
        }
        @keyframes tumbuh-drop {
            0% { transform: translateY(-12px); opacity: 0; }
            20% { opacity: 1; }
            100% { transform: translateY(120px); opacity: 0; }
        }
        @keyframes tumbuh-float {
            0% { transform: translate(-50%, 16px); opacity: 0; }
            20% { opacity: 1; }
            100% { transform: translate(-50%, -18px); opacity: 0; }
        }
        .tumbuh-bounce { animation: tumbuh-bounce 0.7s ease; transform-origin: bottom center; }
        .tumbuh-grow { animation: tumbuh-grow 0.8s ease; transform-origin: bottom center; }
        .tumbuh-drop {
            position: absolute; top: 8px; width: 9px; height: 13px; margin-left: -4px;
            background: #2f8fd6; border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            opacity: 0; animation: tumbuh-drop 0.8s ease-in forwards;
        }
        .tumbuh-float { animation: tumbuh-float 1.1s ease-out forwards; }
        .tumbuh-water { transition: transform 0.8s ease; }

        /* Hormati pengaturan "kurangi gerakan" di perangkat pengguna */
        @media (prefers-reduced-motion: reduce) {
            .tumbuh-bounce, .tumbuh-grow, .tumbuh-drop, .tumbuh-float { animation: none; }
            .tumbuh-water { transition: none; }
            .tumbuh-drop { display: none; }
            .tumbuh-float { opacity: 1; }
        }
    </style>

    <script>
        /**
         * Memutuskan pengingat mana yang sudah waktunya muncul.
         * Fungsi murni: hanya menerima angka dan mengembalikan 'water', 'walk', atau null.
         * Semua waktu dalam milidetik (hasil Date.now()).
         */
        function tumbuhReminderDue(s) {
            const hour = new Date(s.now).getHours();
            const quiet = s.quietStart > s.quietEnd
                ? (hour >= s.quietStart || hour < s.quietEnd)     // contoh 22.00 - 06.00 (melewati tengah malam)
                : (hour >= s.quietStart && hour < s.quietEnd);
            if (quiet || s.countToday >= s.maxPerDay) return null;

            const minute = 60000;

            // Minum: dihitung dari gelas terakhir atau pengingat minum terakhir (mana yang lebih baru).
            // Jika hari ini belum ada keduanya, dihitung dari pertama kali aplikasi dibuka hari ini.
            if (s.waterEnabled && !s.goalMet) {
                const ref = Math.max(s.lastGlassAt ?? 0, s.lastWaterAt ?? 0) || s.firstSeenAt;
                if (s.now - ref >= s.waterEvery * minute) return 'water';
            }

            // Jalan kaki: dihitung dari awal sesi duduk di depan aplikasi atau ajakan jalan terakhir.
            if (s.walkEnabled) {
                const ref = Math.max(s.lastWalkAt ?? 0, s.sessionStart);
                if (s.now - ref >= s.walkEvery * minute) return 'walk';
            }

            return null;
        }

        function waterTracker(initial) {
            return {
                ...initial,
                busy: false,
                error: '',
                toast: '',
                levelUp: false,
                justWatered: false,
                justGrew: false,
                floatXp: '',
                toastTimer: null,
                plantBase: @js(asset('img/plants')),

                // Jam yang diperbarui berkala, supaya hitung mundur di layar ikut berubah
                clock: Date.now(),

                // Jeda aktif
                breakEndsAt: null,              // waktu timer selesai (milidetik), null = tidak berjalan
                breakTimer: null,
                breakTips: [
                    'Berdiri, lalu tarik kedua tangan lurus ke atas.',
                    'Putar bahu ke belakang 10 kali.',
                    'Tengok ke kiri dan kanan perlahan, tahan 5 detik.',
                    'Jalan di tempat, atau ambil segelas air.',
                ],

                // Peti Harian
                chestOpen: false,
                chestReward: null,              // { type, value } setelah peti dibuka
                pendingLevelUp: false,          // naik level ditunda sampai modal peti ditutup
                chestBase: @js(url('chests')),

                // Pengingat
                reminder: null,                 // { type, title, text } atau null
                notifPermission: 'unsupported', // 'default' | 'granted' | 'denied' | 'unsupported'
                sessionStart: Date.now(),

                get streakHint() {
                    if (this.streak.counted_today) return 'Hari ini sudah dihitung.';
                    return this.streak.current > 0
                        ? 'Capai target hari ini untuk melanjutkan.'
                        : 'Capai target hari ini untuk memulai streak.';
                },

                // ----- Jeda aktif -----
                get breakRunning() {
                    return this.breakEndsAt !== null;
                },
                get breakLeft() {            // sisa detik
                    return this.breakRunning ? Math.max(0, Math.ceil((this.breakEndsAt - this.clock) / 1000)) : 0;
                },
                get breakClock() {           // tampilan 1:59
                    const m = Math.floor(this.breakLeft / 60);
                    const sec = String(this.breakLeft % 60).padStart(2, '0');
                    return `${m}:${sec}`;
                },
                get breakPercent() {
                    return Math.min(100, Math.round((1 - this.breakLeft / this.break_cfg.seconds) * 100));
                },
                get breakTip() {             // tips berganti rata sepanjang timer
                    const elapsed = this.break_cfg.seconds - this.breakLeft;
                    const each = this.break_cfg.seconds / this.breakTips.length;
                    return this.breakTips[Math.min(this.breakTips.length - 1, Math.floor(elapsed / each))];
                },
                get breakDurationLabel() {
                    const sec = this.break_cfg.seconds;
                    return sec >= 60 ? `${Math.round(sec / 60)} menit` : `${sec} detik`;
                },
                get breakCooldownMin() {     // menit sampai jeda berikutnya boleh dicatat
                    if (!this.last_break_at) return 0;
                    const readyAt = Date.parse(this.last_break_at) + this.break_cfg.gap_min * 60000;
                    return Math.max(0, Math.ceil((readyAt - this.clock) / 60000));
                },
                get breakAvailable() {
                    return this.breaks < this.break_cfg.max_per_day && this.breakCooldownMin === 0;
                },
                get breakButtonLabel() {
                    if (this.breaks >= this.break_cfg.max_per_day) return 'Batas jeda hari ini tercapai';
                    if (this.breakCooldownMin > 0) return `Jeda berikutnya ${this.breakCooldownMin} menit lagi`;
                    return `Mulai jeda ${this.breakDurationLabel}`;
                },

                startBreak() {
                    if (this.breakRunning || !this.breakAvailable) return;
                    this.clock = Date.now();
                    this.breakEndsAt = this.clock + this.break_cfg.seconds * 1000;
                    this.breakTimer = setInterval(() => {
                        this.clock = Date.now();
                        if (this.breakLeft <= 0) this.finishBreak();
                    }, 250);
                },

                cancelBreak() {
                    clearInterval(this.breakTimer);
                    this.breakEndsAt = null;
                },

                async finishBreak() {
                    if (!this.breakRunning) return;          // cegah terpanggil dua kali
                    clearInterval(this.breakTimer);
                    this.breakEndsAt = null;

                    const data = await this.send('POST', @js(route('breaks.store')));
                    if (!data) return;

                    this.floatXp = `+${data.xp_gained} XP`;
                    setTimeout(() => this.floatXp = '', 1100);
                    this.showToast(`+${data.xp_gained} XP · Jeda aktif selesai!`);
                    this.announceMissions(data);

                    if (data.level_up) {
                        this.levelUp = true;
                        this.justGrew = true;
                        setTimeout(() => this.justGrew = false, 900);
                    }
                },

                // ----- Misi harian dan Peti Harian -----
                chestRewardText(r) {
                    const type = r.type ?? r.reward_type;
                    const value = r.value ?? r.reward_value;
                    return type === 'freeze' ? '+1 pelindung streak' : `+${value} XP`;
                },

                // Tampilkan misi dan lencana yang baru didapat, bergiliran setelah toast utama
                announceMissions(data) {
                    let delay = 2400;
                    const done = data.mission_events ?? [];

                    if (done.length || data.chest_unlocked) {
                        const parts = [];
                        if (done.length) {
                            const xp = done.reduce((sum, m) => sum + m.xp, 0);
                            parts.push(`Misi selesai: ${done.map(m => m.title).join(', ')} · +${xp} XP`);
                        }
                        if (data.chest_unlocked) parts.push('Peti Harian terbuka!');
                        setTimeout(() => this.showToast(parts.join(' · '), 3500), delay);
                        delay += 3700;
                    }

                    if (data.squad_completed) {
                        const xp = this.squad?.challenge?.xp ?? 0;
                        setTimeout(() => this.showToast(`Tantangan squad tercapai! +${xp} XP untuk semua anggota`, 3500), delay);
                        delay += 3700;
                    }

                    this.announceBadges(data.badge_events ?? [], delay);
                },

                announceBadges(badges, delay = 0) {
                    if (!badges.length) return;
                    const names = badges.map(b => b.name).join(', ');
                    setTimeout(() => this.showToast(`Lencana baru: ${names}!`, 3500), delay);
                },

                async openChest() {
                    if (!this.missions.chest) return;
                    this.chestReward = null;
                    this.chestOpen = true;

                    const data = await this.send('POST', `${this.chestBase}/${this.missions.chest.id}/open`);
                    if (!data) {
                        this.chestOpen = false;
                        return;
                    }
                    this.chestReward = data.reward;
                    if (data.level_up) this.pendingLevelUp = true;
                },

                closeChest() {
                    if (!this.chestReward) return;         // tunggu isi peti tampil dulu
                    this.chestOpen = false;
                    if (this.pendingLevelUp) {
                        this.pendingLevelUp = false;
                        this.levelUp = true;
                        this.justGrew = true;
                        setTimeout(() => this.justGrew = false, 900);
                    }
                },

                // Dipanggil otomatis oleh Alpine saat halaman siap
                init() {
                    if ('Notification' in window) this.notifPermission = Notification.permission;

                    // Awal sesi disimpan per tab, supaya reload tidak mengulang hitungan ajakan jalan
                    try {
                        const saved = Number(sessionStorage.getItem('tumbuh-session-start'));
                        if (saved) this.sessionStart = saved;
                        else sessionStorage.setItem('tumbuh-session-start', String(this.sessionStart));
                    } catch (e) { /* penyimpanan diblokir: pakai nilai di memori */ }

                    this.reminderStore();                               // catat "pertama dibuka hari ini"
                    this.announceBadges(this.new_badges ?? [], 1200);    // lencana dari data lama
                    setTimeout(() => this.checkReminder(), 4000);
                    setInterval(() => {                                 // setiap 30 detik
                        this.clock = Date.now();
                        this.checkReminder();
                    }, 30000);
                },

                get canAskNotification() {
                    const r = this.reminders;
                    return this.notifPermission === 'default' && (r.water_enabled || r.walk_enabled);
                },

                async askNotification() {
                    try {
                        this.notifPermission = await Notification.requestPermission();
                    } catch (e) {
                        this.notifPermission = 'denied';
                    }
                },

                // Catatan pengingat hari ini, disimpan di browser (bukan di server)
                reminderStore(patch = null) {
                    const key = `tumbuh-reminder-${this.user_id}`;
                    const d = new Date();
                    const today = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
                    let store = null;
                    try { store = JSON.parse(localStorage.getItem(key)); } catch (e) { /* abaikan */ }
                    if (!store || store.date !== today) {
                        store = { date: today, count: 0, firstSeenAt: Date.now(), lastWaterAt: null, lastWalkAt: null };
                        patch = patch ?? {};
                    }
                    if (patch) {
                        store = { ...store, ...patch };
                        try { localStorage.setItem(key, JSON.stringify(store)); } catch (e) { /* abaikan */ }
                    }
                    return store;
                },

                checkReminder() {
                    if (this.reminder) return;                           // masih ada pengingat yang tampil
                    const store = this.reminderStore();
                    const r = this.reminders;
                    const now = Date.now();
                    const type = tumbuhReminderDue({
                        now,
                        waterEnabled: r.water_enabled,
                        walkEnabled: r.walk_enabled,
                        goalMet: this.goal_met,
                        lastGlassAt: this.last_glass_at ? Date.parse(this.last_glass_at) : null,
                        lastWaterAt: store.lastWaterAt,
                        lastWalkAt: store.lastWalkAt,
                        firstSeenAt: store.firstSeenAt,
                        sessionStart: this.sessionStart,
                        waterEvery: r.water_every,
                        walkEvery: r.walk_every,
                        countToday: store.count,
                        maxPerDay: r.max_per_day,
                        quietStart: r.quiet_start,
                        quietEnd: r.quiet_end,
                    });
                    if (type) this.fireReminder(type, store, now);
                },

                fireReminder(type, store, now) {
                    const texts = {
                        water: [
                            'Tanamanmu mulai haus. Yuk minum segelas.',
                            'Tanamanmu menunggu disiram. Minum dulu, yuk.',
                            `Masih ${Math.max(1, this.target - this.glasses)} gelas lagi menuju target hari ini.`,
                        ],
                        walk: [
                            'Sudah lama duduk. Yuk berdiri dan jalan 5 menit.',
                            'Regangkan badan sebentar, lalu jalan-jalan kecil.',
                        ],
                    }[type];

                    this.reminder = {
                        type,
                        title: type === 'water' ? 'Waktunya minum' : 'Waktunya bergerak',
                        text: texts[store.count % texts.length],          // kalimat bergantian
                    };

                    this.reminderStore({
                        count: store.count + 1,
                        ...(type === 'water' ? { lastWaterAt: now } : { lastWalkAt: now }),
                    });

                    // Notifikasi browser hanya saat tab tidak sedang dilihat
                    if (this.notifPermission === 'granted' && document.hidden) {
                        try {
                            new Notification(`Tumbuh · ${this.reminder.title}`, {
                                body: this.reminder.text,
                                icon: this.fullUrl(this.progress.level),
                                tag: `tumbuh-${type}`,
                            });
                        } catch (e) { /* beberapa browser HP menolak cara ini: banner di halaman tetap muncul */ }
                    }
                },

                async drinkFromReminder() {
                    this.reminder = null;
                    await this.addGlass();
                },

                // Gambar tanaman dalam pot (dipakai di beranda dan modal)
                plantUrl(level) {
                    return `${this.plantBase}/level-${level}.svg`;
                },
                fullUrl(level) {
                    return `${this.plantBase}/level-${level}.svg`;
                },

                // Air di gelas: 0 = penuh (target tercapai), 50 = hampir kosong
                get waterOffset() {
                    return (1 - Math.min(1, this.glasses / this.target)) * 50;
                },

                // Suasana hati tanaman (untuk kalimat di bawah gambar)
                get mood() {
                    if (this.goal_met) return 'senang';
                    return this.glasses === 0 ? 'haus' : 'biasa';
                },
                get moodText() {
                    return {
                        haus: 'Tanamanmu haus. Yuk minum segelas pertama hari ini.',
                        biasa: 'Tanamanmu mulai segar. Terus siram dengan minum.',
                        senang: 'Tanamanmu segar dan bahagia hari ini!',
                    }[this.mood];
                },

                // Jumlah ikon gelas: sebanyak target, bertambah jika minum melebihi target
                get slots() {
                    return Math.min(this.limit, Math.max(this.target, this.glasses));
                },

                async send(method, url) {
                    this.busy = true;
                    this.error = '';
                    try {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.error = data.message ?? 'Terjadi kesalahan, coba lagi.';
                            return null;
                        }
                        // Perbarui hanya data yang dikirim server (tiap endpoint mengirim kunci berbeda)
                        const keys = ['glasses', 'target', 'goal_met', 'progress', 'last_glass_at',
                                      'streak', 'breaks', 'last_break_at', 'missions', 'squad'];
                        for (const key of keys) {
                            if (key in data) this[key] = data[key];
                        }
                        this.clock = Date.now();
                        return data;
                    } catch (e) {
                        this.error = 'Koneksi terputus. Periksa internetmu lalu coba lagi.';
                        return null;
                    } finally {
                        this.busy = false;
                    }
                },

                async addGlass() {
                    const data = await this.send('POST', @js(route('water.store')));
                    if (!data) return;

                    if (this.reminder?.type === 'water') this.reminder = null;   // sudah minum: pengingat ditutup

                    this.justWatered = true;
                    setTimeout(() => this.justWatered = false, 1000);

                    if (data.xp_gained > 0) {
                        this.floatXp = `+${data.xp_gained} XP`;
                        setTimeout(() => this.floatXp = '', 1100);
                    }

                    // Susun pesan toast dari beberapa bagian
                    const parts = [data.xp_gained > 0 ? `+${data.xp_gained} XP` : 'Gelas tercatat'];
                    if (data.goal_just_met) {
                        parts.push(`Target tercapai! Streak ${data.streak.current} hari`);
                    } else if (data.xp_gained > 0) {
                        parts.push('Tanaman disiram!');
                    } else {
                        parts.push(`XP bisa didapat lagi ${data.next_xp_in} menit lagi`);
                    }
                    if (data.freeze_earned) parts.push('+1 pelindung streak');
                    this.showToast(parts.join(' · '), parts.length > 2 || data.xp_gained === 0 ? 3500 : 2200);
                    this.announceMissions(data);

                    if (data.level_up) {
                        this.levelUp = true;
                        this.justGrew = true;
                        setTimeout(() => this.justGrew = false, 900);
                    }
                },

                async undoGlass() {
                    const data = await this.send('DELETE', @js(route('water.undo')));
                    if (!data) return;
                    this.showToast(data.xp_gained < 0
                        ? `Gelas terakhir dibatalkan · ${data.xp_gained} XP`
                        : 'Gelas terakhir dibatalkan');
                },

                showToast(text, duration = 2200) {
                    this.toast = text;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => this.toast = '', duration);
                },
            };
        }
    </script>
</x-app-layout>
