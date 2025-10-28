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
        Schema::create('pinjamans', function (Blueprint $table) {
            $table->id();

            // Relasi utama
            $table->foreignId('koperasi_id')
                ->constrained('koperasis')
                ->onDelete('cascade');

            $table->string('no_pinjaman', 50)->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Detail Pinjaman
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->decimal('bunga_persen', 5, 2);
            $table->integer('tenor_bulan');
            $table->text('tujuan_pinjaman')->nullable();

            // Perhitungan
            $table->decimal('total_bunga', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->decimal('cicilan_perbulan', 15, 2);

            // Sisa
            $table->decimal('sisa_pokok', 15, 2);
            $table->decimal('sisa_bunga', 15, 2);

            // Status dan Approval
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'lunas'])->default('pending');
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('tanggal_approval')->nullable();
            $table->timestamp('tanggal_pencairan')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->text('catatan_penolakan')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign key tambahan
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Index
            $table->index('status', 'idx_status');
            $table->index(['koperasi_id', 'status'], 'idx_koperasi_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjamen');
    }
};
