{{--
    Bagian <head> untuk PWA (aplikasi yang bisa dipasang di HP).
    Dipasang di layouts/app.blade.php dan layouts/guest.blade.php dengan:
        @include('partials.pwa-head')
--}}
<meta name="theme-color" content="#3fa968">
<meta name="description" content="Rawat tanaman virtual dengan minum air yang cukup dan rutin bergerak.">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Tumbuh">

<script>
    // Daftarkan service worker (hanya berjalan di https atau localhost)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}'));
    }

    // Simpan tawaran "pasang aplikasi" dari browser, dipakai tombol di halaman Pengaturan
    window.tumbuhInstallPrompt = null;
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.tumbuhInstallPrompt = event;
        window.dispatchEvent(new Event('tumbuh-installable'));
    });
</script>
