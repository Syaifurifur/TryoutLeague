# TKA Juara

Implementasi MVP platform Tryout TKA SMA/MA/SMK berdasarkan `PRD_Tryout_TKA_SMA.md` dan `TKA_ERD_Database_UserFlow_Wireframe.md`.

## Stack

- Laravel 12 / PHP 8.2
- React 19 + Inertia 3
- Tailwind CSS 4
- MySQL 8
- Redis 7 untuk session, cache, queue, rate-limit, lock submit, dan heartbeat monitoring

## Fitur yang tersedia

- Landing/event page dinamis, registrasi, login email/nomor HP multi-role, reset password, dan nomor peserta otomatis.
- Dashboard peserta, pemilihan mata uji, jadwal, serta progres.
- Exam engine dengan snapshot urutan soal/opsi, timer server, autosave idempoten, antrean offline, reconnect, flag ragu-ragu, integrity event, submit lock, dan auto-submit.
- Scoring berbobot dengan formula version lock, re-score, finalisasi, analisis domain, ranking configurable, sertifikat QR, dan verifikasi publik.
- Dashboard admin/sekolah, event, peserta, bank soal single/multiple/matriks, import DOCX/XLSX, paket manual/rule-based/multi-section, export, audit log, dan live monitoring.
- Skema MySQL lengkap untuk domain MVP, data demo, Docker Compose untuk MySQL/Redis, serta feature test lifecycle kritis.

## Menjalankan aplikasi

Prasyarat: PHP 8.2+, Composer, Node 20+, MySQL 8, dan Redis 7.

```bash
cp .env.example .env
docker compose up -d
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Jalankan worker queue di terminal lain:

```bash
php artisan queue:work --tries=3
```

Untuk pengembangan UI, gunakan `npm run dev` menggantikan build produksi.

## Akun demo

| Role | Email | Password |
|---|---|---|
| Peserta | peserta@tkajuara.id | password |
| Super Admin | admin@tkajuara.id | password |
| Operator Sekolah | sekolah@tkajuara.id | password |

Untuk test tanpa service eksternal, konfigurasi PHPUnit memakai SQLite in-memory dan cache/queue sync.

```bash
php artisan test
```

## Catatan arsitektur

- `ScoringService` terpisah dari controller dan `CalculateAttemptScore` siap dijalankan via Redis queue untuk beban besar.
- Final submit memakai distributed cache lock dan database row lock, sehingga submit manual dan timeout tidak menggandakan proses.
- Kunci jawaban disembunyikan dari payload Inertia peserta.
- Seluruh mutasi admin yang tersedia menulis `audit_logs`.
- Dashboard admin menggunakan cache KPI 60 detik agar query analitik tidak membebani write path ujian.

## Import bank soal DOCX

Pada menu **Admin → Bank Soal**, pilih **Import DOCX**, tentukan satu mata pelajaran, lalu unggah dokumen maksimal 10 MB. Sistem menampilkan pratinjau dan kesalahan per soal sebelum konfirmasi. Soal valid diimpor dengan status `review`; soal tidak valid dilewati.

Template tersedia dari dialog impor atau melalui `public/downloads/template-bank-soal-tka.docx`. Setiap blok menggunakan label `SOAL:`, opsi `A.` sampai minimal `D.`, `KUNCI:`, `MATERI:`, `LEVEL:`, dan opsional `PEMBAHASAN:`. Antarsoal dipisahkan dengan `---`.

Template Excel resmi tersedia di `public/downloads/template-import-soal.xlsx` dan `public/downloads/template-import-peserta.xlsx`. Kedua alur selalu menampilkan preview, validasi, dan error per baris sebelum data dikonfirmasi.
