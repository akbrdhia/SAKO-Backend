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

            // Foreign Keys
            $table->foreignId('koperasi_id')
                ->constrained('koperasis')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Anggota yang mengajukan pinjaman');

            // Nomor Pinjaman (unique identifier)
            $table->string('no_pinjaman', 50)->unique();

            // Detail Pinjaman
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->decimal('bunga_persen', 5, 2)->comment('Persentase bunga (misal: 1.5 untuk 1.5%)');
            $table->integer('tenor_bulan')->comment('Tenor dalam bulan (6, 12, 24)');
            $table->text('tujuan_pinjaman')->nullable();

            // Perhitungan (auto-calculated)
            $table->decimal('total_bunga', 15, 2)->comment('Total bunga keseluruhan');
            $table->decimal('total_bayar', 15, 2)->comment('Jumlah pinjaman + total bunga');
            $table->decimal('cicilan_perbulan', 15, 2)->comment('Cicilan per bulan (rounded up)');

            // Tracking Saldo (updated setiap pembayaran cicilan)
            $table->decimal('sisa_pokok', 15, 2)->comment('Sisa pokok yang belum dibayar');
            $table->decimal('sisa_bunga', 15, 2)->comment('Sisa bunga yang belum dibayar');

            // Status & Workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'lunas'])
                ->default('pending')
                ->index();

            // Timestamps Workflow
            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('tanggal_approval')->nullable()->comment('Waktu approved/rejected');
            $table->timestamp('tanggal_pencairan')->nullable()->comment('Waktu pencairan (status jadi active)');

            // Approval Data
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Manajer yang approve/reject');

            $table->text('catatan_approval')->nullable()->comment('Catatan dari manajer saat approve');
            $table->text('catatan_penolakan')->nullable()->comment('Alasan penolakan');

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User yang mengajukan (bisa kasir atas nama anggota)');

            $table->timestamps();

            // Indexes untuk performa
            $table->index(['koperasi_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('tanggal_pengajuan');
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
