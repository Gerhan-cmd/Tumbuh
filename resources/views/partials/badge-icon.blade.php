{{--
    Ikon lencana.
    Pemakaian: @include('partials.badge-icon', ['code' => 'tetes_pertama', 'earned' => true, 'size' => 'h-14 w-14'])
    Lencana yang belum didapat digambar abu-abu.
--}}
@php
    $colors = [
        'tetes_pertama' => '#2f8fd6',
        'hidrasi_penuh' => '#1d5f91',
        'seratus_gelas' => '#0e7490',
        'beruntun_3' => '#e79b2c',
        'beruntun_7' => '#d9622b',
        'mulai_bergerak' => '#3fa968',
        'rajin_regang' => '#286e45',
        'pemburu_misi' => '#7c5cc4',
    ];
    $bg = ($earned ?? false) ? ($colors[$code] ?? '#3fa968') : '#d1d5db';
    $fg = ($earned ?? false) ? '#ffffff' : '#9ca3af';
@endphp
<svg viewBox="0 0 48 48" class="{{ $size ?? 'h-14 w-14' }}" aria-hidden="true">
    <circle cx="24" cy="24" r="22" fill="{{ $bg }}"/>
    <circle cx="24" cy="24" r="18.5" fill="none" stroke="{{ $fg }}" stroke-opacity=".35" stroke-width="1.5"/>
    @switch($code)
        @case('tetes_pertama')
            {{-- tetes air --}}
            <path d="M24 12c4 5.5 7.5 9.5 7.5 14a7.5 7.5 0 0 1-15 0c0-4.5 3.5-8.5 7.5-14z" fill="{{ $fg }}"/>
            @break
        @case('hidrasi_penuh')
            {{-- gelas penuh --}}
            <path d="M16 13h16l-2 22h-12z" fill="none" stroke="{{ $fg }}" stroke-width="2.4" stroke-linejoin="round"/>
            <path d="M17.2 19h13.6l-1.5 15h-10.6z" fill="{{ $fg }}"/>
            @break
        @case('seratus_gelas')
            <text x="24" y="29" text-anchor="middle" font-size="13" font-weight="800" font-family="sans-serif" fill="{{ $fg }}">100</text>
            @break
        @case('beruntun_3')
        @case('beruntun_7')
            {{-- api + angka hari --}}
            <path d="M25 10c1 5-2 7.5-4.5 10.5C18.5 23 17 25.5 17 28.5a7 7 0 0 0 14 0c0-3.5-1.8-6-3.5-8.5-.5 2.3-1.8 3.6-3.2 4 1-4.8.5-9.5-.3-14z" fill="{{ $fg }}"/>
            <text x="24" y="33" text-anchor="middle" font-size="9" font-weight="800" font-family="sans-serif" fill="{{ $bg }}">{{ $code === 'beruntun_3' ? '3' : '7' }}</text>
            @break
        @case('mulai_bergerak')
            {{-- orang merentangkan tangan --}}
            <circle cx="24" cy="14" r="3.2" fill="{{ $fg }}"/>
            <path d="M24 19v9M24 28l-5 8M24 28l5 8M15 15l9 6 9-6" fill="none" stroke="{{ $fg }}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('rajin_regang')
            {{-- stopwatch --}}
            <circle cx="24" cy="26" r="9" fill="none" stroke="{{ $fg }}" stroke-width="2.6"/>
            <path d="M24 26l4-4M21 13h6M24 13v4" fill="none" stroke="{{ $fg }}" stroke-width="2.6" stroke-linecap="round"/>
            @break
        @case('pemburu_misi')
            {{-- sasaran --}}
            <circle cx="24" cy="24" r="10" fill="none" stroke="{{ $fg }}" stroke-width="2.4"/>
            <circle cx="24" cy="24" r="5.5" fill="none" stroke="{{ $fg }}" stroke-width="2.4"/>
            <circle cx="24" cy="24" r="1.8" fill="{{ $fg }}"/>
            @break
        @default
            <circle cx="24" cy="24" r="6" fill="{{ $fg }}"/>
    @endswitch
</svg>
