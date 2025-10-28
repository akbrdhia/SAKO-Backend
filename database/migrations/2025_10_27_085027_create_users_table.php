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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Relasi ke koperasi
            $table->foreignId('koperasi_id')
                ->constrained('koperasis')
                ->onDelete('cascade');

            // Info anggota
            $table->string('no_anggota', 50)->unique()->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('nama', 255);
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();

            // Login dan otentikasi
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);

            // Role & status
            $table->enum('role', ['anggota', 'kasir', 'manajer', 'admin']);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            // Relasi ke user yang mendaftarkan (kasir)
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->foreign('registered_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Tambahan
            $table->string('foto_profile', 255)->nullable();

            $table->timestamps();

            // Index
            $table->index(['koperasi_id', 'role'], 'idx_koperasi_role');
            $table->index('no_anggota', 'idx_no_anggota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
