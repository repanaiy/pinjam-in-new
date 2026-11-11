<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('username', 60)->unique();
            $t->string('email', 150)->nullable()->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password', 255);
            $t->enum('role', ['siswa', 'admin'])->default('siswa');
            $t->string('nis', 30)->nullable()->unique();
            $t->string('employee_code', 30)->nullable()->unique();
            $t->string('kelas', 30)->nullable();
            $t->string('phone', 25)->nullable();
            $t->string('avatar_path', 255)->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('late_count')->default(0);
            $t->string('remember_token', 100)->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['role', 'is_active'], 'idx_users_role');
            $t->index('kelas', 'idx_users_kelas');
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email', 150)->primary();
            $t->string('token', 255);
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id', 255)->primary();
            $t->unsignedBigInteger('user_id')->nullable()->index('idx_sessions_user');
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index('idx_sessions_last');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
