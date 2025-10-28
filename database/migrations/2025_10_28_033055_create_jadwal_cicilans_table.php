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
            $table->foreignId('koperasi_id')->constrained('koperasis')->cascadeOnDelete();
            $table->foreignId('pinjaman_id')->constrained('pinjamans')->cascadeOnDelete();

            $table->integer('cicilan_ke');
            $table->date('tanggal_jatuh_tempo');
            $table->decimal('jumlah_cicilan', 15, 2);
            $table->decimal('pokok', 15, 2);
            $table->decimal('bunga', 15, 2);

            $table->enum('status', ['belum_bayar', 'sudah_bayar', 'telat'])->default('belum_bayar');
            $table->timestamp('tanggal_bayar')->nullable();

            $table->timestamps();

            // Indexing
            $table->index(['pinjaman_id', 'cicilan_ke']);
            $table->index(['tanggal_jatuh_tempo', 'status']);
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
