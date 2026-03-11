# CARA PASANG KE PROJECT LARAVEL
# ================================
# 1. Copy semua folder/file ini ke project Laravel kamu di C:\laragon\www\in-out-system
#
# 2. Daftarkan middleware di app/Http/Kernel.php
#    Tambahkan di $routeMiddleware:
#
#    'auth.user'   => \App\Http\Middleware\AuthUserMiddleware::class,
#    'guest.user'  => \App\Http\Middleware\GuestUserMiddleware::class,
#
# 3. Jalankan migration untuk tabel notifications:
#    php artisan migrate
#
# 4. Daftarkan storage link:
#    php artisan storage:link
#
# 5. Update session driver di .env (opsional tapi disarankan):
#    SESSION_DRIVER=file
#
# 6. Jalankan project:
#    php artisan serve
#
# 7. (Opsional) Database net_hrd:
#    - Digunakan untuk deteksi status karyawan (permanen/kontrak/magang) dan referensi HR.
#    - Saat mengisi/sync data ke net_hrd, sertakan karyawan AKTIF dan TIDAK AKTIF agar ID tetap konsisten.
#    - Lihat: database/net_hrd_README.md
#    - Jika perlu buat tabel employees di net_hrd: php artisan migrate --database=net_hrd
