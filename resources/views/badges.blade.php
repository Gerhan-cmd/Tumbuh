<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-leaf-800 leading-tight">Lencana</h2>
            @include('partials.tumbuh-nav')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 space-y-4">
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <p class="text-gray-700">
                        <span class="text-2xl font-bold text-leaf-700">{{ $earnedCount }}</span>
                        dari {{ count($badges) }} lencana terkumpul
                    </p>
                </div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-leaf-100">
                    <div class="h-full rounded-full bg-leaf-500"
                         style="width: {{ count($badges) ? round($earnedCount / count($badges) * 100) : 0 }}%"></div>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($badges as $b)
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm {{ $b['earned'] ? '' : 'opacity-90' }}">
                        <div class="flex justify-center">
                            @include('partials.badge-icon', ['code' => $b['code'], 'earned' => $b['earned'], 'size' => 'h-16 w-16'])
                        </div>
                        <p class="mt-2 text-sm font-semibold {{ $b['earned'] ? 'text-gray-800' : 'text-gray-500' }}">{{ $b['name'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $b['description'] }}</p>

                        @if ($b['earned'])
                            <p class="mt-2 text-xs font-medium text-leaf-700">
                                Didapat {{ \Illuminate\Support\Carbon::parse($b['earned_at'])->format('d/m/Y') }}
                            </p>
                        @else
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-gray-400"
                                     style="width: {{ round($b['current'] / $b['target'] * 100) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $b['current'] }}/{{ $b['target'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
