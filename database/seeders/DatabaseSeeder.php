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
        $this->call(KoperasiSeeder::class);
        $this->command->info('');
        $this->command->info('-- LOGIN CREDENTIALS:');
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
