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
            $table->foreignId('koperasi_id')->constrained('koperasis')->cascadeOnDelete();
            $table->foreignId('jadwal_cicilan_id')->constrained('jadwal_cicilans')->cascadeOnDelete();
            $table->foreignId('pinjaman_id')->constrained('pinjamans')->cascadeOnDelete();

            $table->string('no_transaksi', 50)->unique(); // contoh: TRX-20250115-001
            $table->decimal('jumlah_bayar', 15, 2);
            $table->decimal('denda', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2); // jumlah_bayar + denda

            $table->timestamp('tanggal_bayar')->useCurrent();
            $table->enum('metode_bayar', ['tunai', 'transfer'])->default('tunai');
            $table->text('keterangan')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Index tambahan
            $table->index('pinjaman_id', 'idx_pinjaman');
            $table->index('tanggal_bayar', 'idx_tanggal');
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
