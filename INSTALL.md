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
