<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestDbConnectionsCommand extends Command
{
    protected $signature = 'db:test-connections';

    protected $description = 'Tes koneksi database (mysql + net_hrd)';

    public function handle(): int
    {
        $this->info('Mengetes koneksi database...');
        $this->newLine();

        $defaultOk = $this->testConnection('mysql', 'Database utama (in-out-system)');
        $netHrdOk = $this->testConnection('net_hrd', 'Database net_hrd');

        $this->newLine();
        if ($defaultOk && $netHrdOk) {
            $this->info('Semua koneksi berhasil.');
            if (Schema::connection('net_hrd')->hasTable('employees')) {
                $count = DB::connection('net_hrd')->table('employees')->count();
                $this->info("Tabel net_hrd.employees ada, jumlah baris: {$count}");
            } else {
                $this->warn('Tabel net_hrd.employees belum ada. Jalankan: php artisan migrate --database=net_hrd');
            }
            return self::SUCCESS;
        }

        $this->error('Ada koneksi yang gagal. Cek .env (DB_* dan DB_NET_HRD_*).');
        return self::FAILURE;
    }

    private function testConnection(string $name, string $label): bool
    {
        try {
            DB::connection($name)->getPdo();
            $db = DB::connection($name)->getDatabaseName();
            $this->line("  [<info>OK</info>] {$label}: <comment>{$db}</comment>");
            return true;
        } catch (\Throwable $e) {
            $this->line("  [<error>GAGAL</error>] {$label}: " . $e->getMessage());
            return false;
        }
    }
}
