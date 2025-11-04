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
        Schema::create('pembayaran_cicilans', function (Blueprint $table) {
             $table->id();

            // Foreign Keys
            $table->foreignId('jadwal_cicilan_id')
                ->constrained('jadwal_cicilans')
                ->onDelete('cascade')
                ->comment('Cicilan yang dibayar');

            $table->foreignId('pinjaman_id')
                ->constrained('pinjamans')
                ->onDelete('cascade')
                ->comment('Denormalized untuk query performance');

            // Detail Pembayaran
            $table->decimal('jumlah_bayar', 15, 2)->comment('Jumlah yang dibayar (bisa partial)');
            $table->date('tanggal_bayar')->comment('Tanggal pembayaran actual');

            // Breakdown Allocation (Bunga First strategy)
            $table->decimal('alokasi_denda', 15, 2)->default(0)->comment('Berapa yang masuk ke denda');
            $table->decimal('alokasi_bunga', 15, 2)->default(0)->comment('Berapa yang masuk ke bunga');
            $table->decimal('alokasi_pokok', 15, 2)->default(0)->comment('Berapa yang masuk ke pokok');

            // Sisa setelah pembayaran ini
            $table->decimal('sisa_denda', 15, 2)->default(0)->comment('Sisa denda setelah payment ini');
            $table->decimal('sisa_bunga', 15, 2)->default(0)->comment('Sisa bunga setelah payment ini');
            $table->decimal('sisa_pokok', 15, 2)->default(0)->comment('Sisa pokok setelah payment ini');

            // Metadata
            $table->enum('metode_bayar', ['tunai', 'transfer', 'lainnya'])->default('tunai');
            $table->string('nomor_referensi', 100)->nullable()->comment('No. bukti transfer, dll');
            $table->text('keterangan')->nullable();

            // Audit
            $table->foreignId('dibayar_oleh')
                ->constrained('users')
                ->onDelete('restrict')
                ->comment('Kasir yang input pembayaran');

            $table->timestamps();

            // Indexes
            $table->index(['jadwal_cicilan_id', 'tanggal_bayar']);
            $table->index(['pinjaman_id', 'tanggal_bayar']);
            $table->index('tanggal_bayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_cicilans');
    }
};
