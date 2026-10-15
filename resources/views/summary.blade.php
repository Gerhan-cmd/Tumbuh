<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-leaf-800 leading-tight">Ringkasan 7 Hari</h2>
            @include('partials.tumbuh-nav')
        </div>
    </x-slot>

    @php
        $t = $summary['totals'];
        $scale = max(1, $summary['scale']);
        $targetPct = round($summary['target'] / $scale * 100);
    @endphp

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 space-y-4">

            {{-- Angka utama --}}
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500">Total gelas</p>
                    <p class="text-2xl font-bold text-water-500">{{ $t['glasses'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500">Target tercapai</p>
                    <p class="text-2xl font-bold text-leaf-700">{{ $t['goal_days'] }}<span class="text-base font-medium text-gray-500">/7 hari</span></p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500">Jeda aktif</p>
                    <p class="text-2xl font-bold text-leaf-700">{{ $t['breaks'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500">XP didapat</p>
                    <p class="text-2xl font-bold text-xp-500">{{ $t['xp'] }}</p>
                </div>
            </section>

            {{-- Grafik gelas per hari --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-800">Gelas per hari</h3>
                    <span class="text-xs text-gray-500">Garis putus-putus = target {{ $summary['target'] }} gelas</span>
                </div>

                <div class="relative mt-6 h-48">
                    {{-- garis target --}}
                    <div class="absolute inset-x-0 border-t-2 border-dashed border-xp-500"
                         style="bottom: {{ $targetPct }}%"></div>

                    <div class="relative flex h-full items-end gap-2">
                        @foreach ($summary['days'] as $d)
                            <div class="flex h-full flex-1 flex-col items-center justify-end"
                                 title="{{ $d['name'] }}: {{ $d['glasses'] }} gelas">
                                <span class="mb-1 text-xs font-semibold text-gray-700">{{ $d['glasses'] }}</span>
                                <div class="w-full max-w-[2.5rem] rounded-t-lg {{ $d['goal_met'] ? 'bg-water-500' : 'bg-water-300' }}"
                                     style="height: {{ max(2, round($d['glasses'] / $scale * 100)) }}%"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-2 flex gap-2">
                    @foreach ($summary['days'] as $d)
                        <span class="flex-1 text-center text-xs {{ $d['is_today'] ? 'font-bold text-leaf-700' : 'text-gray-500' }}">
                            {{ $d['is_today'] ? 'Hari ini' : $d['label'] }}
                        </span>
                    @endforeach
                </div>

                <p class="mt-3 text-xs text-gray-500">
                    Batang biru tua: target tercapai. Batang biru muda: belum mencapai target.
                </p>
            </section>

            {{-- Umpan balik --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-800">Catatan minggu ini</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($summary['insights'] as $insight)
                        <li class="flex gap-2 text-sm text-gray-700">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-leaf-500"></span>
                            <span>{{ $insight }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
