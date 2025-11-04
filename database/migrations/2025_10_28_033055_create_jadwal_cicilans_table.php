<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_cicilans', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('pinjaman_id')
                ->constrained('pinjamans')
                ->onDelete('cascade');

            // Nomor Cicilan
            $table->integer('cicilan_ke')->comment('Urutan cicilan (1, 2, 3, ..., tenor)');

            // Tanggal Jatuh Tempo
            $table->date('tanggal_jatuh_tempo')->index();

            // Detail Cicilan (fixed amount per cicilan)
            $table->decimal('jumlah_cicilan', 15, 2)->comment('Total cicilan (pokok + bunga)');
            $table->decimal('pokok', 15, 2)->comment('Porsi pokok');
            $table->decimal('bunga', 15, 2)->comment('Porsi bunga');

            // Pembayaran Actual (nullable sampai dibayar)
            $table->decimal('jumlah_dibayar', 15, 2)->nullable()->comment('Jumlah yang dibayar anggota');
            $table->date('tanggal_bayar')->nullable()->comment('Tanggal pembayaran actual');

            // Denda (future feature)
            // Tracking pembayaran partial (breakdown per komponen)
            $table->decimal('jumlah_dibayar_denda', 15, 2)->default(0)->after('jumlah_dibayar');
            $table->decimal('jumlah_dibayar_bunga', 15, 2)->default(0)->after('jumlah_dibayar_denda');
            $table->decimal('jumlah_dibayar_pokok', 15, 2)->default(0)->after('jumlah_dibayar_bunga');
            $table->decimal('denda', 15, 2)->default(0)->comment('Denda keterlambatan');
            $table->integer('hari_telat')->default(0)->comment('Jumlah hari keterlambatan');

            // Status Cicilan
            $table->enum('status', ['belum_bayar', 'sudah_bayar', 'telat'])
                ->default('belum_bayar')
                ->index();

            // Audit
            $table->foreignId('dibayar_oleh')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Kasir yang input pembayaran');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            // Composite index untuk query efisien
            $table->index(['pinjaman_id', 'cicilan_ke']);
            $table->index(['pinjaman_id', 'status']);
            $table->index(['status', 'tanggal_jatuh_tempo']); // Untuk cron job cek telat

            // Unique constraint: 1 pinjaman tidak boleh ada 2 cicilan dengan nomor sama
            $table->unique(['pinjaman_id', 'cicilan_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_cicilans');
    }
};
