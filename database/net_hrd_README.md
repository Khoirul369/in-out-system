# Database net_hrd

Database eksternal untuk referensi data HR (status karyawan, dll.). Dikonfigurasi di `.env` sebagai connection `net_hrd`.

## Penting: Sertakan karyawan aktif dan tidak aktif

Saat mengisi atau menyinkronkan data ke database **net_hrd** (khususnya tabel **employees** jika ada):

- **Sertakan juga karyawan yang tidak aktif**, jangan hanya yang aktif.
- Di tabel `employees`, **campurkan** karyawan aktif dengan tidak aktif; **ID karyawan harus tetap sama** (tidak bergeser).
- Jika hanya mengimpor karyawan aktif, urutan/ID bisa berubah dan menimbulkan **ketidaksesuaian** dengan sistem lain (misalnya API leave yang memakai `employees_id` / id karyawan).

### Ringkas

| Yang dilakukan | Dampak |
|----------------|--------|
| Import **aktif + tidak aktif** | ID tetap konsisten, integrasi (leave, dll.) aman |
| Import **hanya aktif** | ID bisa berubah, mismatch dengan sistem lain |

### Tabel yang dipakai aplikasi ini

- **employees_status** – untuk deteksi status kerja (permanen/kontrak/magang) di form resign.
- **status** – master status yang di-join ke `employees_status`.

Pastikan proses import/sync ke net_hrd mengisi data untuk **semua karyawan** (aktif dan tidak aktif) agar ID tetap sama.
