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
                  ->nullable()
                  ->constrained('koperasis')
                  ->onDelete('cascade');
            
            // Data anggota
            $table->string('no_anggota')->unique()->nullable();
            $table->string('nik')->nullable();
            
            // Data umum user
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            
            // Role & status
            $table->enum('role', ['anggota', 'kasir', 'manajer', 'admin'])->default('anggota');
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Siapa yang daftarin (kasir/admin)
            $table->foreignId('registered_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            
            $table->timestamps();
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
