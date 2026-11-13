# Product Requirements Document (PRD)
## Platform Tryout TKA Tingkat SMA/MA/SMK

**Nama Produk Sementara:** TKA Tryout Platform  
**Versi Dokumen:** Versi 1.0  
**Tanggal:** 3 September 2026  
**Status:** Draft untuk Perencanaan & Pengembangan

---

## 1. Ringkasan Eksekutif

TKA Tryout Platform adalah aplikasi berbasis web untuk mengelola proses tryout Tes Kemampuan Akademik (TKA) tingkat SMA/MA/SMK secara end-to-end, mulai dari publikasi program, pendaftaran peserta, pengelolaan sekolah, pemilihan mata uji, pelaksanaan ujian, pemantauan, penilaian, analisis hasil, ranking, hingga penerbitan sertifikat.

Platform dirancang agar dapat digunakan pada skala sekolah, kota/kabupaten, provinsi, maupun nasional. Sistem harus mendukung pelaksanaan ujian serentak dengan jumlah peserta besar, menyediakan pengalaman ujian yang sederhana bagi siswa, serta memberikan data analitik yang berguna bagi sekolah dan penyelenggara.

Struktur mata uji, durasi, jumlah soal, bobot penilaian, dan aturan hasil **tidak di-hardcode**. Semua parameter utama harus dapat dikonfigurasi oleh admin agar platform dapat menyesuaikan kebijakan TKA dan kebutuhan tryout penyelenggara.

### 1.1 Acuan Konseptual TKA

Per 3 September 2026, informasi resmi Kemendikdasmen menjelaskan bahwa TKA pada jenjang SMA/MA/sederajat dan SMK/MAK memiliki tiga mata uji wajib—Bahasa Indonesia, Matematika, dan Bahasa Inggris—serta mata uji pilihan. Bentuk soal yang dipublikasikan mencakup pilihan ganda sederhana dan pilihan ganda kompleks. Platform ini menggunakan acuan tersebut sebagai baseline, tetapi tetap dibuat configurable agar mudah menyesuaikan perubahan kebijakan.

### 1.2 Sasaran Produk

- Menyederhanakan pendaftaran dan pelaksanaan tryout TKA.
- Menyediakan exam engine yang stabil, aman, responsif, dan scalable.
- Memberikan hasil yang lebih bermakna daripada sekadar nilai akhir.
- Menyediakan dashboard sekolah untuk evaluasi akademik peserta.
- Memberikan dashboard penyelenggara untuk memantau operasional dan performa kegiatan.
- Menjadi fondasi untuk fitur lanjutan seperti rekomendasi belajar, AI Tutor, rekomendasi program studi, dan integrasi CRM/PMB.

---

## 2. Latar Belakang dan Permasalahan

Penyelenggaraan tryout berskala besar sering menghadapi beberapa masalah:

1. Pendaftaran peserta tersebar di berbagai form dan spreadsheet.
2. Data sekolah tidak terstandar sehingga menghasilkan duplikasi.
3. Pengaturan paket ujian dan bank soal dilakukan secara manual.
4. Risiko jawaban hilang akibat koneksi tidak stabil.
5. Monitoring peserta ketika ujian berlangsung terbatas.
6. Rekap dan penilaian membutuhkan waktu setelah ujian.
7. Peserta hanya memperoleh nilai tanpa analisis kompetensi.
8. Sekolah tidak memperoleh laporan agregat yang cukup berguna.
9. Penyelenggara sulit mengukur partisipasi, completion rate, distribusi nilai, dan performa per wilayah.
10. Sistem yang tidak dirancang untuk concurrency tinggi berisiko gagal saat peserta login atau submit secara bersamaan.

Produk ini bertujuan menyatukan proses tersebut dalam satu platform.

---

## 3. Tujuan Produk

### 3.1 Tujuan Utama

Membangun platform tryout TKA yang mampu menangani seluruh siklus peserta dari pendaftaran sampai hasil dan analisis secara terukur, mudah dioperasikan, serta dapat digunakan untuk kegiatan berskala besar.

### 3.2 Indikator Keberhasilan

| Indikator | Target Awal |
|---|---:|
| Pendaftaran berhasil tanpa bantuan operator | >= 95% |
| Peserta terverifikasi dapat memulai ujian | >= 99% |
| Jawaban tersimpan otomatis | >= 99,9% |
| Completion rate peserta yang memulai ujian | >= 95% |
| Waktu tampil halaman soal normal | < 2 detik pada kondisi target |
| Waktu publikasi hasil setelah periode ditutup | < 15 menit |
| Kesalahan penghitungan skor | 0 kasus terverifikasi |
| Dashboard sekolah tersedia | Maks. 15 menit setelah hasil final |
| Ketersediaan layanan saat sesi ujian | >= 99,9% |

> Target performa final harus disesuaikan dengan jumlah concurrent users dan kapasitas infrastruktur yang disepakati.

---

## 4. Ruang Lingkup Produk

### 4.1 In Scope — MVP

- Landing page program tryout.
- Pendaftaran individu.
- Pendaftaran kolektif sekolah.
- Login peserta.
- Verifikasi data peserta.
- Pemilihan mata uji.
- Dashboard peserta.
- Bank soal.
- Import soal.
- Pembuatan paket ujian.
- Penjadwalan sesi ujian.
- Exam engine.
- Timer.
- Autosave jawaban.
- Randomisasi soal dan opsi.
- Navigasi soal.
- Penandaan soal ragu-ragu.
- Submit otomatis ketika waktu habis.
- Monitoring ujian oleh admin/pengawas.
- Scoring otomatis.
- Hasil individual.
- Ranking configurable.
- Dashboard sekolah.
- Dashboard admin.
- Export Excel/CSV.
- Sertifikat digital dengan QR verification.
- Audit log.
- Role & permission management.

### 4.2 Out of Scope — MVP

- AI Tutor generatif.
- Proctoring webcam otomatis.
- Facial recognition.
- Browser lockdown native.
- Aplikasi mobile native.
- Pembayaran online.
- Adaptive testing berbasis IRT.
- Integrasi langsung ke sistem TKA pemerintah.
- Prediksi resmi kelulusan atau seleksi pendidikan.
- Rekomendasi program studi sebagai keputusan final.

### 4.3 Future Scope

- AI performance analysis.
- AI Tutor berbasis pembahasan soal.
- Personalized learning path.
- Adaptive testing.
- Integrasi CRM/PMB.
- Rekomendasi program studi.
- SSO sekolah/institusi.
- Mobile application.
- Advanced proctoring.
- Payment gateway untuk tryout berbayar.
- Multi-tenant white-label.

---

## 5. Persona dan Role Pengguna

| Role | Deskripsi | Kebutuhan Utama |
|---|---|---|
| Peserta | Siswa SMA/MA/SMK yang mengikuti tryout | Daftar, memilih mata uji, ujian, melihat hasil |
| Operator Sekolah | PIC/guru dari sekolah | Import peserta, melihat progres dan laporan sekolah |
| Pengawas | Petugas monitoring sesi | Melihat status peserta dan kejadian selama ujian |
| Content Editor | Tim pembuat soal | Membuat, mengedit, mengelompokkan, review soal |
| Admin Event | Pengelola satu event/periode | Menentukan jadwal, paket, peserta, hasil |
| Super Admin | Pengelola platform | Konfigurasi global, role, keamanan, seluruh event |
| Analyst/Management | Pihak manajemen | Melihat dashboard agregat dan laporan |

---

## 6. User Journey Utama

### 6.1 Journey Peserta

```text
Landing Page
    ↓
Pilih Event Tryout
    ↓
Registrasi / Login
    ↓
Lengkapi Profil
    ↓
Verifikasi Nomor HP/Email (opsional configurable)
    ↓
Pilih Mata Uji
    ↓
Dashboard Peserta
    ↓
Simulasi / Tutorial Ujian
    ↓
Check-in Sesi
    ↓
Mulai Tryout
    ↓
Autosave Jawaban
    ↓
Submit
    ↓
Menunggu Publikasi Hasil
    ↓
Hasil + Analisis + Ranking
    ↓
Sertifikat
```

### 6.2 Journey Sekolah

```text
Registrasi / Akun Sekolah
    ↓
Verifikasi Sekolah
    ↓
Tambah / Import Peserta
    ↓
Pantau Status Registrasi
    ↓
Pantau Kehadiran dan Penyelesaian
    ↓
Lihat Hasil Agregat
    ↓
Unduh Laporan Sekolah
```

### 6.3 Journey Admin

```text
Buat Event
    ↓
Atur Periode & Sesi
    ↓
Atur Mata Uji
    ↓
Kelola Bank Soal
    ↓
Generate / Publish Paket
    ↓
Kelola Peserta & Sekolah
    ↓
Monitor Ujian
    ↓
Finalisasi Scoring
    ↓
Publikasi Hasil
    ↓
Dashboard & Export
```

---

## 7. Informasi yang Dikumpulkan

### 7.1 Data Peserta

Field minimal:

- ID peserta internal.
- Nomor peserta.
- Nama lengkap.
- NISN (configurable wajib/tidak).
- Jenis satuan pendidikan.
- Nama sekolah.
- NPSN bila tersedia.
- Kelas.
- Kota/kabupaten.
- Provinsi.
- Nomor WhatsApp.
- Email.
- Tanggal lahir bila diperlukan.
- Mata uji pilihan.
- Event/periode yang diikuti.
- Status pendaftaran.
- Status verifikasi.
- Status kehadiran.
- Status ujian.
- Persetujuan kebijakan privasi.
- Waktu pendaftaran.

### 7.2 Normalisasi Data Sekolah

Sekolah harus menjadi master data tersendiri dan tidak hanya berupa free-text. Sistem menyediakan:

- Nama sekolah resmi.
- NPSN.
- Jenjang.
- Status negeri/swasta.
- Provinsi.
- Kota/kabupaten.
- Kecamatan (opsional).
- PIC sekolah.
- Kontak PIC.

Fuzzy matching dapat ditambahkan pada versi lanjutan untuk mendeteksi duplikasi nama sekolah.

---

## 8. Modul dan Functional Requirements

### 8.1 Landing Page & Event

| ID | Requirement | Prioritas |
|---|---|---|
| EVT-001 | Admin dapat membuat lebih dari satu event tryout | Must |
| EVT-002 | Setiap event memiliki judul, deskripsi, banner, tanggal, status, kuota | Must |
| EVT-003 | Event dapat berstatus Draft, Published, Closed, Archived | Must |
| EVT-004 | Landing page menampilkan CTA daftar/login | Must |
| EVT-005 | Admin dapat mengaktifkan pendaftaran individu/sekolah | Must |
| EVT-006 | Admin dapat mengatur periode pendaftaran | Must |
| EVT-007 | Admin dapat mengatur syarat dan ketentuan event | Should |
| EVT-008 | Event dapat menggunakan subdomain/slug unik | Should |

### 8.2 Registrasi & Authentication

| ID | Requirement | Prioritas |
|---|---|---|
| REG-001 | Peserta dapat registrasi mandiri | Must |
| REG-002 | Sistem mencegah duplikasi berdasarkan aturan configurable | Must |
| REG-003 | Sistem menghasilkan nomor peserta unik | Must |
| REG-004 | Login dapat menggunakan email/nomor HP + password/OTP sesuai konfigurasi | Must |
| REG-005 | Peserta dapat reset password | Must |
| REG-006 | Admin dapat import peserta melalui Excel/CSV | Must |
| REG-007 | Sistem menampilkan preview dan validasi sebelum import | Must |
| REG-008 | Import menampilkan error per baris | Must |
| REG-009 | Operator sekolah hanya dapat melihat peserta sekolahnya | Must |
| REG-010 | Peserta menyetujui privacy notice dan ketentuan | Must |

### 8.3 Pemilihan Mata Uji

| ID | Requirement | Prioritas |
|---|---|---|
| SUB-001 | Admin dapat membuat mata uji wajib dan pilihan | Must |
| SUB-002 | Jumlah mata uji pilihan dapat dikonfigurasi | Must |
| SUB-003 | Peserta hanya dapat memilih mata uji selama periode yang ditentukan | Must |
| SUB-004 | Admin dapat mengunci pilihan setelah deadline | Must |
| SUB-005 | Sistem menyimpan histori perubahan pilihan | Should |
| SUB-006 | Admin dapat memberikan aturan kombinasi mata uji | Should |

### 8.4 Bank Soal

| ID | Requirement | Prioritas |
|---|---|---|
| QBN-001 | Content Editor dapat membuat soal | Must |
| QBN-002 | Mendukung pilihan ganda satu jawaban | Must |
| QBN-003 | Mendukung multiple correct answers | Must |
| QBN-004 | Mendukung soal kategori/matriks | Must |
| QBN-005 | Mendukung teks, tabel, gambar, rumus matematika | Must |
| QBN-006 | Soal memiliki mata uji, domain, subdomain, tingkat kesulitan, tag | Must |
| QBN-007 | Soal memiliki kunci dan pembahasan | Must |
| QBN-008 | Soal memiliki status Draft, Review, Approved, Retired | Must |
| QBN-009 | Import soal dari template Excel | Must |
| QBN-010 | Versioning soal | Should |
| QBN-011 | Reviewer dapat memberi komentar sebelum approve | Should |
| QBN-012 | Sistem mencatat siapa membuat/mengubah soal | Must |

### 8.5 Paket Ujian

| ID | Requirement | Prioritas |
|---|---|---|
| EXM-001 | Admin dapat membuat paket dari bank soal | Must |
| EXM-002 | Paket dapat mengambil soal secara manual atau rule-based | Must |
| EXM-003 | Admin dapat menentukan jumlah soal per domain | Must |
| EXM-004 | Admin dapat menentukan distribusi tingkat kesulitan | Should |
| EXM-005 | Paket dapat memiliki beberapa section | Must |
| EXM-006 | Durasi dapat ditentukan per ujian atau section | Must |
| EXM-007 | Urutan soal dapat diacak per peserta | Must |
| EXM-008 | Urutan opsi jawaban dapat diacak bila tipe soal mendukung | Must |
| EXM-009 | Paket memiliki preview sebelum publish | Must |
| EXM-010 | Paket yang sudah digunakan tidak dapat diubah tanpa membuat versi baru | Must |

### 8.6 Exam Engine

| ID | Requirement | Prioritas |
|---|---|---|
| RUN-001 | Peserta hanya dapat memulai pada jendela waktu yang diizinkan | Must |
| RUN-002 | Sistem menampilkan countdown timer | Must |
| RUN-003 | Jawaban disimpan otomatis setelah perubahan | Must |
| RUN-004 | Autosave juga dilakukan berkala | Must |
| RUN-005 | Peserta dapat berpindah nomor soal | Must |
| RUN-006 | Peserta dapat menandai soal ragu-ragu | Must |
| RUN-007 | Sistem menunjukkan answered/unanswered/flagged | Must |
| RUN-008 | Saat koneksi terputus, jawaban lokal diantrekan dan disinkronkan kembali | Must |
| RUN-009 | Server menjadi sumber kebenaran waktu ujian | Must |
| RUN-010 | Ujian auto-submit ketika waktu habis | Must |
| RUN-011 | Peserta melihat halaman konfirmasi sebelum submit manual | Must |
| RUN-012 | Setelah final submit, jawaban terkunci | Must |
| RUN-013 | Reload halaman tidak menghilangkan progres | Must |
| RUN-014 | Sistem mendukung desktop dan mobile browser | Must |
| RUN-015 | Admin dapat menentukan apakah ujian wajib fullscreen | Should |

### 8.7 Integrity / Anti-Cheat Ringan

Fitur ini berfungsi sebagai indikator perilaku, bukan bukti otomatis kecurangan.

| ID | Requirement | Prioritas |
|---|---|---|
| INT-001 | Catat perpindahan tab/window | Should |
| INT-002 | Catat keluar dari fullscreen | Should |
| INT-003 | Catat login perangkat baru | Should |
| INT-004 | Batas concurrent session configurable | Must |
| INT-005 | Catat IP dan user-agent pada event keamanan | Must |
| INT-006 | Admin dapat melihat integrity event log | Should |
| INT-007 | Sistem tidak otomatis mendiskualifikasi hanya berdasarkan satu event | Must |
| INT-008 | Rules untuk warning/diskualifikasi dapat dikonfigurasi | Could |

### 8.8 Monitoring Pengawas

Pengawas melihat data near-real-time:

- Belum login.
- Sudah login.
- Siap ujian.
- Sedang mengerjakan.
- Koneksi bermasalah.
- Keluar fullscreen.
- Pindah tab.
- Submit.
- Auto-submit.
- Sisa waktu.
- Jumlah soal terjawab.

### 8.9 Scoring

| ID | Requirement | Prioritas |
|---|---|---|
| SCR-001 | Sistem menghitung raw score otomatis | Must |
| SCR-002 | Mendukung bobot per soal | Must |
| SCR-003 | Mendukung partial scoring bila diperlukan | Should |
| SCR-004 | Formula skor dapat dikonfigurasi per event | Must |
| SCR-005 | Skor dapat dinormalisasi ke skala tertentu | Should |
| SCR-006 | Sistem menyimpan versi formula scoring | Must |
| SCR-007 | Re-score dapat dilakukan tanpa mengubah jawaban asli | Must |
| SCR-008 | Admin harus melakukan finalisasi sebelum publikasi hasil | Must |

### 8.10 Hasil Peserta

Hasil minimal berisi:

- Skor total.
- Skor per mata uji.
- Skor per domain/kompetensi.
- Jumlah benar/salah/tidak dijawab jika diizinkan.
- Posisi percentile bila diaktifkan.
- Ranking bila diaktifkan.
- Kekuatan kompetensi.
- Area yang perlu ditingkatkan.
- Riwayat tryout.
- Tautan sertifikat.

Admin dapat menentukan kapan pembahasan soal ditampilkan.

### 8.11 Ranking

Ranking dapat diaktifkan/nonaktifkan pada level:

- Nasional.
- Provinsi.
- Kota/kabupaten.
- Sekolah.
- Mata uji.
- Event.

Opsi privasi:

- Nama lengkap.
- Nama disamarkan.
- Nomor peserta.
- Ranking hanya terlihat oleh peserta sendiri.

Tie-breaking harus configurable, misalnya:
1. Skor total.
2. Skor mata uji prioritas.
3. Jumlah benar.
4. Waktu penyelesaian, hanya jika kebijakan mengizinkan.

### 8.12 Dashboard Sekolah

Dashboard menampilkan:

- Total peserta terdaftar.
- Total verified.
- Hadir.
- Memulai ujian.
- Selesai.
- Completion rate.
- Rata-rata skor.
- Median.
- Distribusi nilai.
- Skor per mata uji.
- Skor per domain.
- Top performers.
- Area kompetensi terlemah.
- Perbandingan dengan agregat event jika diizinkan.

Fitur export:

- Rekap peserta.
- Rekap skor.
- Laporan PDF sekolah (future/MVP+).
- Excel hasil sekolah.

### 8.13 Dashboard Admin

KPI utama:

- Total registrasi.
- Total sekolah.
- Peserta verified.
- Check-in.
- Active examinees.
- Completed.
- Failed/abandoned sessions.
- Average score.
- Distribution score.
- Peserta per wilayah.
- Peserta per sekolah.
- Mata uji populer.
- Error rate.
- Autosave failure.
- Peak concurrency.

### 8.14 Sertifikat

| ID | Requirement | Prioritas |
|---|---|---|
| CRT-001 | Sertifikat dibuat otomatis untuk peserta eligible | Must |
| CRT-002 | Template sertifikat dapat dikelola admin | Should |
| CRT-003 | Sertifikat memiliki kode verifikasi unik | Must |
| CRT-004 | Sertifikat memiliki QR menuju halaman verifikasi | Must |
| CRT-005 | Halaman verifikasi bersifat publik dengan data minimum | Must |
| CRT-006 | Admin dapat revoke sertifikat | Should |

---

## 9. State Machine Peserta

```text
REGISTERED
   ↓
PROFILE_COMPLETE
   ↓
VERIFIED
   ↓
SUBJECT_SELECTED
   ↓
ELIGIBLE
   ↓
CHECKED_IN
   ↓
IN_PROGRESS
   ├── INTERRUPTED
   │      ↓
   │   IN_PROGRESS
   ↓
SUBMITTED
   ↓
SCORED
   ↓
RESULT_PUBLISHED
   ↓
CERTIFICATE_ISSUED
```

Status `DISQUALIFIED`, `CANCELLED`, dan `ABSENT` harus tersedia sebagai state khusus.

---

## 10. Desain Exam Session

Setiap attempt minimal menyimpan:

- attempt_id.
- participant_id.
- exam_package_version_id.
- started_at_server.
- expires_at_server.
- submitted_at.
- submit_type: manual/auto/admin.
- status.
- device fingerprint ringan.
- IP history.
- last_activity_at.
- last_autosave_at.
- question ordering snapshot.
- option ordering snapshot.
- integrity flags.
- score_status.

**Prinsip penting:** ordering snapshot harus disimpan sehingga peserta selalu menerima urutan soal yang sama setelah refresh atau reconnect.

---

## 11. Autosave & Resilience

### 11.1 Strategi Autosave

1. Ketika peserta memilih/mengubah jawaban, UI menyimpan state secara lokal.
2. Client mengirim perubahan ke server.
3. Server membalas acknowledgement.
4. UI menandai jawaban sebagai tersinkron.
5. Jika request gagal, jawaban tetap berada di queue lokal.
6. Client melakukan retry dengan idempotency key.
7. Server menolak update jika attempt sudah final-submit.

### 11.2 Ketentuan

- Tidak boleh terjadi duplicate answer version karena retry.
- Timestamp client tidak digunakan sebagai sumber waktu ujian.
- Jika koneksi hilang, timer tetap dihitung berdasarkan waktu server.
- Peserta mendapat indikator `Tersimpan`, `Menyimpan...`, atau `Belum tersinkron`.
- Sebelum submit, sistem memastikan seluruh queue lokal sudah dikirim sejauh mungkin.

---

## 12. Arsitektur Teknis Rekomendasi

### 12.1 Stack

**Frontend**
- Next.js / React.
- Tailwind CSS.
- PWA optional untuk resilience.

**Backend**
- Laravel sebagai rekomendasi utama bila tim dominan PHP.
- REST API atau hybrid server-render/API.
- Queue worker untuk scoring, import, export, sertifikat.

**Database**
- PostgreSQL direkomendasikan.
- MySQL dapat digunakan bila sesuai standar tim.

**Cache / Queue**
- Redis.

**Object Storage**
- S3-compatible storage untuk gambar soal, file import, laporan, sertifikat.

**Realtime**
- WebSocket/SSE untuk dashboard monitoring, atau polling terkontrol pada MVP.

**Observability**
- Centralized logging.
- Error tracking.
- Metrics & alerting.

### 12.2 Diagram Logis

```text
[Browser Peserta]
       |
       v
[Load Balancer / CDN]
       |
       v
[Web / API Application]
   |        |        |
   v        v        v
[Postgres] [Redis] [Object Storage]
               |
               v
          [Queue Worker]
           |    |    |
           v    v    v
        Scoring Export Certificate
```

---

## 13. Data Model Inti

Entitas utama:

```text
users
roles
permissions
schools
school_users
participants
events
event_registrations
subjects
event_subjects
participant_subjects
question_banks
questions
question_options
question_tags
exam_packages
exam_package_versions
exam_sections
exam_package_questions
exam_sessions
exam_attempts
attempt_questions
answers
answer_revisions
integrity_events
scores
score_components
rankings
certificates
audit_logs
imports
exports
notifications
```

### 13.1 Relasi Ringkas

```text
School 1---N Participant
Event 1---N Registration
Participant N---N Event
Event N---N Subject
Question N---1 Subject
Exam Package 1---N Package Version
Package Version N---N Question
Participant 1---N Attempt
Attempt 1---N Answer
Attempt 1---N Integrity Event
Attempt 1---1 Score
Participant 1---N Certificate
```

---

## 14. API Domain Utama

Contoh grouping endpoint:

```text
/auth/*
/events/*
/registrations/*
/schools/*
/participants/*
/subjects/*
/questions/*
/exam-packages/*
/exam-sessions/*
/attempts/*
/attempts/<built-in function id>/answers
/attempts/<built-in function id>/submit
/results/*
/rankings/*
/certificates/*
/reports/*
/admin/*
```

Endpoint penyimpanan jawaban harus idempotent dan aman terhadap retry.

---

## 15. Permission Matrix

| Capability | Peserta | Operator Sekolah | Pengawas | Content Editor | Admin Event | Super Admin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Ubah profil sendiri | ✓ |  |  |  | ✓ | ✓ |
| Kerjakan ujian | ✓ |  |  |  |  |  |
| Lihat hasil sendiri | ✓ |  |  |  | ✓ | ✓ |
| Kelola peserta sekolah |  | ✓ |  |  | ✓ | ✓ |
| Lihat laporan sekolah |  | ✓ |  |  | ✓ | ✓ |
| Monitor sesi |  |  | ✓ |  | ✓ | ✓ |
| Kelola bank soal |  |  |  | ✓ | ✓ | ✓ |
| Publish paket |  |  |  |  | ✓ | ✓ |
| Finalisasi scoring |  |  |  |  | ✓ | ✓ |
| Konfigurasi global |  |  |  |  |  | ✓ |

---

## 16. Non-Functional Requirements

### 16.1 Performance

- API read umum: target p95 < 500 ms pada kapasitas normal.
- Save answer: target p95 < 750 ms.
- Halaman soal dapat dibuka < 2 detik pada koneksi wajar.
- Sistem harus menjalani load test sebelum event besar.
- Arsitektur harus mendukung horizontal scaling.

### 16.2 Scalability

Target harus ditentukan sebelum production launch:

| Level | Concurrent Peserta | Catatan |
|---|---:|---|
| Pilot | 500 | 5-10 sekolah |
| Regional | 5.000 | Puluhan/ratusan sekolah |
| Large | 20.000 | Multi-region |
| National-scale target | 50.000+ | Membutuhkan capacity planning khusus |

### 16.3 Availability

- Target availability sesi ujian >= 99,9%.
- Database backup otomatis.
- Point-in-time recovery direkomendasikan.
- Redis bukan sumber data jawaban permanen.
- Graceful degradation untuk fitur non-kritis.

### 16.4 Security

- TLS/HTTPS.
- Password hashing modern.
- Rate limiting.
- CSRF/XSS/SQL injection protection.
- RBAC.
- Audit trail admin.
- Session invalidation.
- Secure secret management.
- Enkripsi data sensitif bila dibutuhkan.
- Backup terenkripsi.
- Principle of least privilege.

### 16.5 Privacy

- Hanya mengumpulkan data yang diperlukan.
- Privacy notice pada registrasi.
- Retention policy configurable.
- Data sekolah dan peserta dibatasi berdasarkan role.
- Public certificate verification hanya menampilkan data minimum.
- Export data dicatat di audit log.

### 16.6 Accessibility & UX

- Responsive.
- Font dan kontras mudah dibaca.
- Keyboard navigation untuk bagian penting.
- Status warna tidak boleh menjadi satu-satunya indikator.
- Tombol submit jelas dan membutuhkan konfirmasi.
- Mobile browser tetap usable, meskipun desktop/laptop dapat direkomendasikan.

---

## 17. Business Rules

1. Satu peserta dapat mengikuti lebih dari satu event jika diizinkan.
2. Nomor peserta unik minimal pada scope event.
3. Peserta tidak dapat memulai sebelum eligible.
4. Satu attempt aktif per peserta per paket, kecuali retake diaktifkan.
5. Waktu server adalah sumber kebenaran.
6. Paket ujian published bersifat immutable; perubahan dilakukan melalui versi baru.
7. Jawaban tidak boleh diubah setelah final submit.
8. Re-score tidak mengubah jawaban peserta.
9. Hasil hanya terlihat setelah `result_published_at`.
10. Ranking dapat dihitung ulang jika scoring berubah.
11. Sertifikat hanya diterbitkan berdasarkan rule eligibility.
12. Event integrity tidak otomatis berarti kecurangan.
13. Admin override wajib menghasilkan audit log.

---

## 18. Notifikasi

Channel MVP:

- Email.
- WhatsApp melalui provider resmi sebagai integrasi opsional.
- In-app notification.

Trigger:

- Pendaftaran berhasil.
- Verifikasi berhasil.
- Pengingat jadwal.
- H-1 tryout.
- Sesi segera dimulai.
- Ujian berhasil disubmit.
- Hasil telah terbit.
- Sertifikat tersedia.

Pengiriman notifikasi dilakukan asynchronous melalui queue.

---

## 19. Import & Export

### 19.1 Import Peserta

Template minimal:

```text
nama
nisn
sekolah
npsn
kelas
kota
provinsi
no_whatsapp
email
mata_uji_1
mata_uji_2
```

Flow:

```text
Upload
→ Validasi Format
→ Preview
→ Mapping Kolom
→ Validasi Data
→ Tampilkan Error/Warning
→ Confirm Import
→ Queue Import
→ Report Sukses/Gagal
```

### 19.2 Import Soal

Mendukung template terstruktur. Untuk konten kompleks seperti gambar atau rumus, sistem dapat menggunakan file referensi/ZIP atau editor setelah import.

### 19.3 Export

- Peserta.
- Kehadiran.
- Status ujian.
- Nilai.
- Ranking.
- Rekap sekolah.
- Integrity event.
- Audit log untuk admin tertentu.

---

## 20. Analytics

### 20.1 Funnel Peserta

```text
Landing Visitor
→ Started Registration
→ Registered
→ Verified
→ Subject Selected
→ Checked-in
→ Started Exam
→ Submitted
→ Viewed Result
→ Downloaded Certificate
```

### 20.2 Akademik

- Mean.
- Median.
- Standard deviation.
- Percentile.
- Distribusi skor.
- Difficulty analysis per soal.
- Correct response rate.
- Distractor analysis.
- Domain mastery.
- Perbandingan kelompok sekolah/wilayah jika diizinkan.

### 20.3 Operasional

- Peak concurrent users.
- Login success rate.
- API error rate.
- Autosave success/failure.
- Reconnect count.
- Submit latency.
- Queue delay.
- Page load performance.

---

## 21. Rekomendasi UX Halaman Ujian

Layout desktop:

```text
┌─────────────────────────────────────────────────────────┐
│ TKA Tryout | Matematika                    00:42:18     │
├───────────────┬─────────────────────────────────────────┤
│ Nomor Soal    │ Soal 18 dari 40                         │
│ 01 02 03 ...  │                                         │
│               │ [Stimulus / Pertanyaan]                  │
│ Legend        │                                         │
│ ● Terjawab    │ ○ A. ...                                 │
│ ◐ Ragu-ragu   │ ○ B. ...                                 │
│ ○ Kosong      │ ○ C. ...                                 │
│               │ ○ D. ...                                 │
│               │                                         │
│               │ [ Tandai Ragu-ragu ]                    │
│               │                                         │
│               │ [Sebelumnya]            [Selanjutnya]   │
├───────────────┴─────────────────────────────────────────┤
│ Status: Tersimpan • Koneksi Stabil                      │
└─────────────────────────────────────────────────────────┘
```

UX rules:

- Timer selalu terlihat.
- Status save selalu terlihat.
- Nomor soal memiliki legend.
- Tidak membuat peserta kehilangan jawaban ketika navigasi.
- Submit ditempatkan terpisah dari tombol next.
- Warning waktu dapat ditampilkan pada 10 dan 5 menit terakhir.

---

## 22. Admin Workflow Penyusunan Ujian

```text
Create Event
    ↓
Configure Subjects
    ↓
Create / Import Question Bank
    ↓
Review & Approve Questions
    ↓
Define Blueprint
    ↓
Generate Exam Package
    ↓
Preview
    ↓
QA Package
    ↓
Publish Version
    ↓
Assign to Session
```

Blueprint contoh:

| Domain | Easy | Medium | Hard | Total |
|---|---:|---:|---:|---:|
| Domain A | 3 | 5 | 2 | 10 |
| Domain B | 3 | 5 | 2 | 10 |
| Domain C | 2 | 5 | 3 | 10 |
| Domain D | 2 | 4 | 4 | 10 |
| **Total** | **10** | **19** | **11** | **40** |

---

## 23. Acceptance Criteria Kritis MVP

### AC-01 Registrasi

**Given** peserta belum terdaftar  
**When** peserta mengisi seluruh field wajib dengan data valid  
**Then** registrasi berhasil, nomor peserta dibuat, dan dashboard dapat diakses.

### AC-02 Duplicate Registration

**Given** kombinasi identitas yang ditetapkan sudah terdaftar pada event  
**When** peserta mencoba registrasi kembali  
**Then** sistem mencegah duplikasi dan memberikan jalur login/recovery.

### AC-03 Start Exam

**Given** peserta eligible dan jadwal telah dimulai  
**When** peserta menekan Mulai Ujian  
**Then** server membuat attempt, mengunci package version dan ordering snapshot, lalu timer dimulai berdasarkan server.

### AC-04 Autosave

**Given** peserta sedang mengerjakan  
**When** jawaban berubah  
**Then** jawaban tersimpan lokal dan dikirim ke server tanpa reload halaman.

### AC-05 Reconnect

**Given** koneksi terputus setelah peserta menjawab  
**When** koneksi kembali  
**Then** pending answers tersinkron tanpa membuat duplicate revision.

### AC-06 Time Expired

**Given** sisa waktu mencapai nol  
**When** attempt belum disubmit  
**Then** server menandai attempt selesai dan sistem melakukan auto-submit.

### AC-07 Final Submit

**Given** peserta melakukan final submit  
**When** submit berhasil  
**Then** seluruh jawaban terkunci dan tidak dapat diubah peserta.

### AC-08 Scoring

**Given** attempt submitted  
**When** scoring dijalankan  
**Then** sistem menghitung skor dengan formula version yang terkait pada event.

### AC-09 Publish Result

**Given** scoring sudah difinalisasi  
**When** admin mempublikasikan hasil  
**Then** peserta dapat melihat hasil sesuai konfigurasi event.

### AC-10 School Isolation

**Given** operator Sekolah A login  
**When** membuka dashboard sekolah  
**Then** operator tidak dapat melihat data individual Sekolah B.

---

## 24. Testing Strategy

### 24.1 Functional Test

- Registration.
- Login/reset.
- Import.
- Subject selection.
- Question management.
- Package generation.
- Start/reconnect/submit.
- Scoring.
- Ranking.
- Certificate.
- Export.

### 24.2 Load Test

Skenario minimum:

1. 10.000 user login dalam window pendek.
2. 5.000+ user memulai ujian hampir bersamaan.
3. Autosave berulang dari seluruh active users.
4. Lonjakan submit pada menit terakhir.
5. Admin dashboard tetap usable saat ujian.

### 24.3 Failure Test

- Redis restart.
- Satu application node gagal.
- Object storage lambat.
- Network client putus.
- Database read replica gagal bila digunakan.
- Queue backlog.

### 24.4 Security Test

- Authorization bypass.
- IDOR.
- Rate limit.
- XSS.
- SQL injection.
- CSRF.
- File upload validation.
- Session fixation.
- Export permission.

---

## 25. Deployment & Environment

Minimal tersedia:

```text
Local
Development
Staging
Production
```

Staging harus dapat digunakan untuk rehearsal event.

Checklist production:

- Environment secrets.
- Backup.
- Monitoring.
- Alerting.
- Domain/TLS.
- CDN.
- Database indexes.
- Queue worker count.
- Autoscaling rule.
- Load test result.
- Rollback strategy.
- Incident contact.
- Status page internal.

---

## 26. Operational Runbook Event

### H-7

- Freeze major feature.
- Finalisasi bank soal.
- Load test.
- Verifikasi data peserta.
- Validasi jadwal.
- Simulasi pengawas.

### H-1

- Recheck infrastructure.
- Final backup.
- Verify queue.
- Verify monitoring.
- Test login sample.
- Publish reminder.

### Hari H

- War room aktif.
- Monitor concurrency.
- Monitor error.
- Monitor database.
- Monitor queue.
- Catat incident.
- Jangan deploy fitur non-kritis saat sesi berlangsung.

### H+0

- Validasi submission count.
- Run scoring.
- QA sampel hasil.
- Finalisasi.
- Publish.

### H+1

- Laporan event.
- Incident review.
- Analisis item.
- Feedback peserta/sekolah.

---

## 27. Roadmap

### Phase 0 — Discovery & Prototype

- Finalisasi kebutuhan.
- User flow.
- Prototype UI.
- Model data.
- Proof-of-concept autosave.
- Performance baseline.

### Phase 1 — MVP

- Event.
- Registration.
- School.
- Participant dashboard.
- Question bank.
- Exam package.
- Exam engine.
- Autosave.
- Scoring.
- Results.
- Ranking.
- Admin dashboard.
- School dashboard.
- Certificate.
- Export.

### Phase 2 — Operational Enhancement

- Enhanced monitoring.
- WhatsApp notification.
- Detailed item analysis.
- PDF school report.
- Better integrity dashboard.
- Advanced import tools.
- White-label event.

### Phase 3 — AI & Recommendation

- AI result narrative.
- Personalized learning plan.
- AI Tutor dari pembahasan yang disetujui.
- Recommendation engine program studi.
- CRM/PMB integration.
- Lead consent management.

### Phase 4 — Scale & Adaptive

- Multi-tenant.
- Mobile app.
- Adaptive testing.
- IRT-based analytics.
- Advanced proctoring bila benar-benar diperlukan.

---

## 28. Integrasi PMB / CRM (Opsional)

Jika platform digunakan sebagai bagian dari program kampus, integrasi dilakukan setelah hasil tryout dan harus berbasis consent.

Flow:

```text
Tryout
   ↓
Hasil Akademik
   ↓
Analisis Minat/Kemampuan
   ↓
Rekomendasi Eksploratif Program Studi
   ↓
Peserta memilih "Saya ingin konsultasi"
   ↓
Consent
   ↓
Lead dikirim ke CRM
   ↓
Follow-up konselor
```

Data tidak boleh otomatis dipakai untuk marketing di luar consent yang diberikan peserta.

---

## 29. AI Performance Analysis — Future Requirement

AI tidak menghitung skor. Skor harus berasal dari scoring engine deterministik.

Input AI:

- skor per domain;
- correct/incorrect pattern;
- daftar kompetensi;
- pembahasan resmi internal;
- level kesulitan.

Output:

- ringkasan kekuatan;
- area yang perlu diperbaiki;
- saran prioritas belajar;
- learning plan.

Guardrail:

- tidak menyatakan hasil sebagai diagnosis kemampuan permanen;
- tidak menjamin kelulusan/seleksi;
- tidak mengubah skor;
- rekomendasi harus dapat ditinjau dan dimatikan admin.

---

## 30. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Lonjakan login | Sistem lambat/down | CDN, scaling, load test |
| Autosave gagal | Kehilangan jawaban | Local queue, retry, idempotency |
| DB bottleneck | Ujian terganggu | Indexing, connection pool, capacity plan |
| Paket soal bocor | Integritas tryout turun | Versioning, access control, randomization |
| Salah kunci jawaban | Skor salah | Review workflow, versioned scoring, re-score |
| Import data buruk | Peserta gagal login | Validation preview, error report |
| Penyalahgunaan data | Risiko privasi | RBAC, audit log, consent, retention |
| Ranking menimbulkan isu privasi | Keluhan peserta/sekolah | Opt-in/configurable anonymization |
| False-positive anti-cheat | Peserta dirugikan | Jadikan indikator, review manual |
| Kebijakan TKA berubah | Fitur tidak relevan | Semua struktur ujian configurable |

---

## 31. Definition of Done MVP

MVP dianggap siap digunakan jika:

- Seluruh acceptance criteria kritis lulus.
- Tidak ada bug severity critical/high yang belum ditangani.
- Load test mencapai target concurrency yang disepakati.
- Autosave dan reconnect berhasil pada skenario jaringan tidak stabil.
- Scoring tervalidasi menggunakan test cases independen.
- Role isolation tervalidasi.
- Backup dan restore sudah diuji.
- Admin dapat mengoperasikan event tanpa akses developer.
- Rehearsal end-to-end berhasil.
- Runbook dan SOP incident tersedia.

---

## 32. Rekomendasi Prioritas Pengembangan

Urutan implementasi yang disarankan:

1. Data model + authentication + RBAC.
2. Event + school + participant registration.
3. Question bank + package versioning.
4. Exam attempt + server timer.
5. Autosave + reconnect.
6. Submit + scoring.
7. Results.
8. Admin monitoring.
9. School dashboard.
10. Ranking + certificate.
11. Export/report.
12. Integrity indicators.
13. AI/CRM setelah core exam stabil.

Bagian **exam attempt, autosave, timer, submit, dan scoring** harus dianggap sebagai core critical path. Fitur kosmetik atau marketing tidak boleh mengurangi prioritas stabilitas modul tersebut.

---

## 33. Open Questions untuk Finalisasi Sebelum Development

- Berapa target peserta total dan concurrent users?
- Apakah tryout gratis atau berbayar?
- Apakah satu peserta hanya boleh mengikuti satu kali?
- Apakah peserta wajib berasal dari sekolah yang telah terdaftar?
- Metode login: password, OTP, atau keduanya?
- Apakah NISN wajib?
- Apakah mata uji mengikuti konfigurasi TKA penuh atau event khusus?
- Berapa sesi dan durasi target?
- Kapan hasil boleh dibuka?
- Apakah pembahasan langsung dibuka?
- Apakah ranking publik digunakan?
- Apakah sertifikat menampilkan skor?
- Apakah operator sekolah boleh melihat nilai individual?
- Apakah WhatsApp API akan digunakan?
- Apakah integrasi CRM/PMB termasuk MVP atau fase berikutnya?
- Target deployment: cloud provider apa?
- Apakah diperlukan domain/branding terpisah per event?

---

## 34. Deliverable Pengembangan

### Product

- PRD final.
- User flow.
- Wireframe.
- UI design.
- Design system mini.
- Acceptance criteria.

### Engineering

- Architecture document.
- ERD.
- API specification.
- Migration.
- Test plan.
- Load test report.
- Security checklist.
- Deployment runbook.
- Incident runbook.

### Operations

- Manual admin.
- Manual operator sekolah.
- Manual pengawas.
- Tutorial peserta.
- Template import peserta.
- Template import soal.
- FAQ.

---

## 35. Referensi Acuan

Dokumen ini menggunakan informasi publik TKA yang tersedia pada 3 September 2026 sebagai baseline produk, terutama:

1. Portal Tes Kemampuan Akademik 2026, Pusat Asesmen Pendidikan, Kemendikdasmen.
2. FAQ Tes Kemampuan Akademik, Rumah Pendidikan Kemendikdasmen.
3. Kerangka Asesmen TKA SMA/MA/Sederajat dan SMK/MAK.
4. Petunjuk teknis TKA yang berlaku pada saat implementasi.

**Catatan:** tim produk wajib memeriksa kembali aturan resmi sebelum setiap periode tryout. Konfigurasi aplikasi harus menjadi sumber fleksibilitas, bukan asumsi yang di-hardcode.

---

# Lampiran A — Suggested MVP Backlog

| Epic | Fitur | Priority |
|---|---|---|
| Identity | Auth & RBAC | P0 |
| Event | Event management | P0 |
| Registration | Individual registration | P0 |
| Registration | School bulk import | P0 |
| Content | Question bank | P0 |
| Content | Question import | P0 |
| Exam | Package/versioning | P0 |
| Exam | Attempt/timer | P0 |
| Exam | Autosave/reconnect | P0 |
| Exam | Submit/lock | P0 |
| Result | Scoring engine | P0 |
| Result | Individual result | P0 |
| Operation | Live monitoring | P1 |
| School | School dashboard | P1 |
| Result | Ranking | P1 |
| Certificate | QR certificate | P1 |
| Reporting | Excel export | P1 |
| Integrity | Tab/fullscreen logging | P2 |
| AI | Performance narrative | Future |
| CRM | PMB integration | Future |

# Lampiran B — Suggested Sprint Breakdown

### Sprint 1
Foundation, auth, roles, school master, event.

### Sprint 2
Registration, participant dashboard, import peserta.

### Sprint 3
Question bank, editor, import soal, approval workflow.

### Sprint 4
Package builder, versioning, schedule/session.

### Sprint 5
Exam UI, attempt lifecycle, timer, navigation.

### Sprint 6
Autosave, offline queue/reconnect, submit locking.

### Sprint 7
Scoring, result, ranking.

### Sprint 8
School/admin dashboard, certificate, export.

### Sprint 9
Load test, security hardening, bug fixing, rehearsal.

---

**End of PRD**
