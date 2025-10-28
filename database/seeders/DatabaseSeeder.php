<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Koperasi;
use App\Models\User;
use App\Models\Simpanan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. BUAT KOPERASI
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

        $koperasiBandung = Koperasi::create([
            'kode_koperasi' => 'BDG001',
            'nama' => 'Koperasi Makmur Bandung',
            'alamat' => 'Jl. Asia Afrika No. 45, Bandung',
            'kelurahan' => 'Braga',
            'kecamatan' => 'Sumur Bandung',
            'kota' => 'Bandung',
            'provinsi' => 'Jawa Barat',
            'kode_pos' => '40111',
            'no_telp' => '022-87654321',
            'email' => 'info@makmur-bdg.id',
            'bunga_default' => 1.8,
            'max_pinjaman_multiplier' => 8,
            'status' => 'active',
        ]);

        // 2. BUAT ADMIN KEMENKOP
        $admin = User::create([
            'koperasi_id' => $koperasiJakarta->id, // assign ke salah satu koperasi (atau bisa null)
            'nama' => 'Admin Kemenkop',
            'email' => 'admin@kemenkop.go.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
                        'status' => 'active',
        ]);

        // 3. BUAT STAFF KOPERASI JAKARTA
        $manajerJkt = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'nama' => 'Agus Prasetyo',
            'email' => 'manajer@sejahtera-jkt.id',
            'password' => Hash::make('password'),
            'role' => 'manajer',
            'status' => 'active',
        ]);

        $kasirJkt = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'nama' => 'Siti Nurhaliza',
            'email' => 'kasir@sejahtera-jkt.id',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'status' => 'active',
        ]);

        // 4. BUAT ANGGOTA KOPERASI JAKARTA
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

        $anggota3 = User::create([
            'koperasi_id' => $koperasiJakarta->id,
            'no_anggota' => 'JKT001-0003',
            'nik' => '3174012501850003',
            'nama' => 'Dadang Hermawan',
            'alamat' => 'Jl. Thamrin No. 88, Jakarta',
            'no_hp' => '081234567892',
            'email' => 'dadang@email.com',
            'password' => Hash::make('password'),
            'role' => 'anggota',
            'status' => 'active',
            'registered_by' => $kasirJkt->id,
        ]);

        // 5. BUAT SIMPANAN ANGGOTA
        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonths(6),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirJkt->id,
        ]);

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'wajib',
            'jumlah' => 100000,
            'tanggal' => now()->subMonths(5),
            'keterangan' => 'Simpanan Wajib Bulan ' . now()->subMonths(5)->format('F Y'),
            'created_by' => $kasirJkt->id,
        ]);

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'wajib',
            'jumlah' => 100000,
            'tanggal' => now()->subMonths(4),
            'keterangan' => 'Simpanan Wajib Bulan ' . now()->subMonths(4)->format('F Y'),
            'created_by' => $kasirJkt->id,
        ]);

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota1->id,
            'jenis' => 'sukarela',
            'jumlah' => 1500000,
            'tanggal' => now()->subMonths(3),
            'keterangan' => 'Simpanan Sukarela',
            'created_by' => $kasirJkt->id,
        ]);

        // Total simpanan Budi: 2.200.000

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota2->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonths(4),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirJkt->id,
        ]);

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota2->id,
            'jenis' => 'wajib',
            'jumlah' => 50000,
            'tanggal' => now()->subMonths(3),
            'keterangan' => 'Simpanan Wajib',
            'created_by' => $kasirJkt->id,
        ]);

        // Total simpanan Ani: 550.000

        Simpanan::create([
            'koperasi_id' => $koperasiJakarta->id,
            'user_id' => $anggota3->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonths(2),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirJkt->id,
        ]);

        // Total simpanan Dadang: 500.000

        // 6. BUAT STAFF & ANGGOTA KOPERASI BANDUNG
        $manajerBdg = User::create([
            'koperasi_id' => $koperasiBandung->id,
            'nama' => 'Rina Sari',
            'email' => 'manajer@makmur-bdg.id',
            'password' => Hash::make('password'),
            'role' => 'manajer',
            'status' => 'active',
        ]);

        $kasirBdg = User::create([
            'koperasi_id' => $koperasiBandung->id,
            'nama' => 'Dedi Kurniawan',
            'email' => 'kasir@makmur-bdg.id',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'status' => 'active',
        ]);

        $anggota4 = User::create([
            'koperasi_id' => $koperasiBandung->id,
            'no_anggota' => 'BDG001-0001',
            'nik' => '3273012501850001',
            'nama' => 'Eko Prasetyo',
            'alamat' => 'Jl. Dago No. 15, Bandung',
            'no_hp' => '082234567890',
            'email' => 'eko@email.com',
            'password' => Hash::make('password'),
            'role' => 'anggota',
            'status' => 'active',
            'registered_by' => $kasirBdg->id,
        ]);

        Simpanan::create([
            'koperasi_id' => $koperasiBandung->id,
            'user_id' => $anggota4->id,
            'jenis' => 'pokok',
            'jumlah' => 500000,
            'tanggal' => now()->subMonth(),
            'keterangan' => 'Simpanan Pokok',
            'created_by' => $kasirBdg->id,
        ]);

        $this->command->info('✅ Seeder completed!');
        $this->command->info('');
        $this->command->info('📋 LOGIN CREDENTIALS:');
        $this->command->info('');
        $this->command->info('Admin Kemenkop:');
        $this->command->info('  Email: admin@kemenkop.go.id');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Koperasi Jakarta:');
        $this->command->info('  Manajer: manajer@sejahtera-jkt.id / password');
        $this->command->info('  Kasir: kasir@sejahtera-jkt.id / password');
        $this->command->info('  Anggota: budi@email.com / password (Simpanan: Rp 2.200.000)');
        $this->command->info('  Anggota: ani@email.com / password (Simpanan: Rp 550.000)');
        $this->command->info('');
        $this->command->info('Koperasi Bandung:');
        $this->command->info('  Manajer: manajer@makmur-bdg.id / password');
        $this->command->info('  Kasir: kasir@makmur-bdg.id / password');
        $this->command->info('  Anggota: eko@email.com / password (Simpanan: Rp 500.000)');
    }
}
