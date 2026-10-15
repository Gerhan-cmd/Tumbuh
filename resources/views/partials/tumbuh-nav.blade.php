{{-- Menu antarhalaman Tumbuh. Dipakai di bagian header setiap halaman. --}}
@php
    $links = [
        ['route' => 'dashboard', 'label' => 'Beranda'],
        ['route' => 'badges', 'label' => 'Lencana'],
        ['route' => 'squad', 'label' => 'Squad'],
        ['route' => 'summary', 'label' => 'Ringkasan'],
        ['route' => 'settings.edit', 'label' => 'Pengaturan'],
    ];
@endphp
<nav class="flex gap-1 overflow-x-auto text-sm" aria-label="Menu Tumbuh">
    @foreach ($links as $link)
        @php $active = request()->routeIs($link['route']); @endphp
        <a href="{{ route($link['route']) }}"
           @if ($active) aria-current="page" @endif
           class="whitespace-nowrap rounded-full px-3 py-1.5 font-medium transition
                  {{ $active ? 'bg-leaf-500 text-white' : 'text-gray-600 hover:bg-leaf-100 hover:text-leaf-800' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
