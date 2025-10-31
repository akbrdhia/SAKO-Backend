<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Koperasi;
use App\Models\User;
use App\Models\Simpanan;
use Illuminate\Support\Facades\Hash;

class KoperasiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CREATE KOPERASI
        $koperasiJakarta = Koperasi::create([
            'kode_koperasi' => 'JKT001',
            'nama' => 'Koperasi Sejahtera Jakarta',
            'alamat' => 'Jl. Sudirman No. 123, Jakarta Pusat',
            'kelurahan' => 'Karet Tengsin',
            'kecamatan' => 'Tanah Abang',
            'kota' => 'Jakarta Pusat',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '10220',
            'no_telp' => '021-12345678',
            'email' => 'info@sejahtera-jkt.id',
            'bunga_default' => 1.5,
            'max_pinjaman_multiplier' => 10,
            'status' => 'active',
        ]);

        // 2. CREATE ADMIN KEMENKOP
        User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'nama' => 'Admin Kemenkop',
            'email' => 'admin@kemenkop.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // 3. CREATE MANAJER KOPERASI JAKARTA
        User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'nama' => 'Agus Prasetyo',
            'email' => 'manajer@sejahtera-jkt.id',
            'password' => Hash::make('password'),
            'role' => 'manajer',
            'status' => 'active',
        ]);

        // 4. CREATE KASIR KOPERASI JAKARTA
        $kasirJkt = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'nama' => 'Siti Nurhaliza',
            'email' => 'kasir@sejahtera-jkt.id',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'status' => 'active',
        ]);

        // 5. CREATE ANGGOTA
        $anggota1 = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'no_anggota' => 'JKT001-0001',
            'nik' => '3174012501850001',
            'nama' => 'Budi Santoso',
            'alamat' => 'Jl. Merdeka No. 10, Jakarta',
            'no_hp' => '081234567890',
            'email' => 'budi@email.com',
            'password' => Hash::make('password'),
            'role' => 'anggota',
            'status' => 'active',
            'registered_by' => $kasirJkt->id,
        ]);

        $anggota2 = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'no_anggota' => 'JKT001-0002',
            'nik' => '3174012501850002',
            'nama' => 'Ani Widiastuti',
            'alamat' => 'Jl. Gatot Subroto No. 25, Jakarta',
            'no_hp' => '081234567891',
            'email' => 'ani@email.com',
            'password' => Hash::make('password'),
            'role' => 'anggota',
            'status' => 'active',
            'registered_by' => $kasirJkt->id,
        ]);

        // 6. CREATE SIMPANAN ANGGOTA 1 (Budi)

        // Simpanan Pokok
        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonths(6),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirJkt->id,
        ]);

        // Simpanan Wajib (beberapa bulan)
        for ($i = 5; $i >= 1; $i--) {
            Simpanan::create([
                'koperasi_id' => $koperasiJakarta->id,
                'user_id' => $anggota1->id,
                'jenis' => 'wajib',
                'jumlah' => 100000,
                'tanggal' => now()->subMonths($i),
                'keterangan' => 'Simpanan Wajib Bulan ' . now()->subMonths($i)->format('F Y'),
                'created_by' => $kasirJkt->id,
            ]);
        }

        // Simpanan Sukarela
        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'sukarela',
            'jumlah' => 1500000,
            'tanggal' => now()->subMonths(3),
            'keterangan' => 'Simpanan Sukarela',
            'created_by' => $kasirJkt->id,
        ]);

        // Total Simpanan Budi: 500.000 + (5 x 100.000) + 1.500.000 = 2.500.000

        // 7. CREATE SIMPANAN ANGGOTA 2 (Ani)

        // Simpanan Pokok
        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota2->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonths(4),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirJkt->id,
        ]);

        // Simpanan Wajib (3 bulan)
        for ($i = 3; $i >= 1; $i--) {
            Simpanan::create([
                'koperasi_id' => $koperasiJakarta->id,
                'user_id' => $anggota2->id,
                'jenis' => 'wajib',
                'jumlah' => 50000,
                'tanggal' => now()->subMonths($i),
                'keterangan' => 'Simpanan Wajib Bulan ' . now()->subMonths($i)->format('F Y'),
                'created_by' => $kasirJkt->id,
            ]);
        }

        // Total Simpanan Ani: 500.000 + (3 x 50.000) = 650.000

        $this->command->info('-- Koperasi, Users & Simpanan seeded successfully!');
        $this->command->info('');
        $this->command->info('-- SIMPANAN SUMMARY:');
        $this->command->info('  Budi: Rp 2.500.000');
        $this->command->info('  Ani: Rp 650.000');
    }
}
