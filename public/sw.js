/*
 * Service worker Tumbuh.
 *
 * - Halaman (HTML): selalu coba ambil dari internet dulu. Kalau offline, tampilkan /offline.html.
 * - Gambar, ikon, dan file CSS/JS hasil build: ambil dari cache dulu supaya cepat,
 *   lalu perbarui cache di belakang layar.
 * - Permintaan selain GET (catat gelas, buka peti, dll.) tidak pernah di-cache.
 *
 * Ubah VERSION setiap kali isi file ini diubah, supaya cache lama dibersihkan.
 */
const VERSION = 'tumbuh-v1';
const PRECACHE = [
  '/offline.html',
  '/manifest.webmanifest',
  '/favicon.svg',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECACHE)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Hanya GET dari situs sendiri
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Halaman: internet dulu, cadangannya halaman offline
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    return;
  }

  // Aset statis: cache dulu, sambil memperbarui di belakang layar
  const isAsset = url.pathname.startsWith('/build/')
    || url.pathname.startsWith('/img/')
    || url.pathname.startsWith('/icons/')
    || url.pathname === '/favicon.svg';

  if (isAsset) {
    event.respondWith(
      caches.open(VERSION).then(async (cache) => {
        const cached = await cache.match(request);
        const network = fetch(request)
          .then((response) => {
            if (response.ok) cache.put(request, response.clone());
            return response;
          })
          .catch(() => cached);
        return cached || network;
      })
    );
  }
});
