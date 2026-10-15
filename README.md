🌱 Tumbuh - Plant Your Healthy Habits


Tumbuh adalah sebuah aplikasi web berbasis gamifikasi yang dirancang untuk membantu mahasiswa dan pengguna umum dalam membangun serta mempertahankan kebiasaan sehat, khususnya minum air putih dan aktif bergerak (berjalan kaki).

Dengan menggunakan metafora merawat tanaman virtual, setiap kebiasaan baik di dunia nyata akan berdampak langsung pada pertumbuhan tanaman Anda di dalam aplikasi.

✨ Fitur Utama

🪴 Virtual Plant & Leveling: Rawat tanaman Anda dari sebuah 'Bibit' hingga menjadi 'Pohon Legenda' (8 Tingkatan Level). Air minum berfungsi sebagai penyiram, dan langkah kaki berfungsi sebagai pupuk (XP).

🎯 Misi Harian & Streak: Selesaikan 3-4 misi harian untuk membuka Peti Harian berisi hadiah menarik. Pertahankan Streak harian Anda (dilengkapi fitur Streak Freeze untuk hari istirahat/sakit).

🏆 Lencana Pencapaian (Badges): Kumpulkan hingga 18 jenis lencana eksklusif, seperti "Hidrasi 7 Hari" atau "Maraton Kampus".

🤝 Fitur Sosial (Squad): Bentuk kelompok (squad) beranggotakan 3-8 orang untuk menyelesaikan target langkah kolektif.

📊 Leaderboard Mingguan: Bersaing secara sehat dengan teman-teman di dalam squad melalui papan peringkat yang di-reset setiap minggunya.

🔓 Unlockables: Buka berbagai jenis tanaman baru, dekorasi pot, dan tips kesehatan seiring meningkatnya level Anda.

📉 Laporan Kesehatan Pribadi: Transisi dari motivasi gamifikasi ekstrinsik menuju kesadaran intrinsik melalui pantauan progres kesehatan yang nyata.

💻 Teknologi yang Digunakan

Proyek ini dibangun menggunakan arsitektur modern dengan pemisahan backend dan frontend:

Backend: Laravel (PHP Framework)

Frontend: React.js

Styling: Tailwind CSS

Build Tool: Vite

⚙️ Prasyarat

Sebelum menjalankan proyek ini di komputer lokal, pastikan Anda telah menginstal:

PHP (Versi 8.1 atau lebih baru)

Composer

Node.js & NPM

MySQL atau MariaDB (bisa menggunakan XAMPP/Laragon)

🚀 Cara Instalasi & Menjalankan Proyek

Ikuti langkah-langkah berikut untuk menjalankan aplikasi secara lokal:

1. Kloning Repository

git clone https://github.com/Gerhan-cmd/Tumbuh.git
cd Tumbuh


2. Setup Backend (Laravel)

Instal semua dependensi PHP melalui Composer:

composer install


Salin file pengaturan environment:

cp .env.example .env


Buka file .env dan sesuaikan konfigurasi database Anda:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_tumbuh
DB_USERNAME=root
DB_PASSWORD=


Buat Application Key dan jalankan migrasi database:

php artisan key:generate
php artisan migrate


Jalankan server backend:

php artisan serve


(Server backend akan berjalan di http://localhost:8000)

3. Setup Frontend (React & Vite)

Buka terminal baru di folder proyek yang sama, lalu instal dependensi JavaScript:

npm install


Jalankan server frontend (Vite):

npm run dev


(Klik tautan lokal yang muncul di terminal, biasanya http://localhost:5173, untuk membuka aplikasi di browser)

👥 Pengembang

Gerhan - Fullstack Developer - GitHub Profile

Dibuat dengan ❤️ untuk gaya hidup yang lebih sehat.
