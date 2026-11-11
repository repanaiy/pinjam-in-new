<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_inspections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loan_id')->constrained('loans', 'id', 'fk_insp_loan')->cascadeOnDelete();
            $t->enum('type', ['handover', 'return']);
            $t->json('checklist');
            $t->enum('final_condition', ['baik', 'rusak', 'hilang'])->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('inspected_by')->constrained('users', 'id', 'fk_insp_user');
            $t->timestamp('inspected_at')->nullable();
            $t->timestamps();
            $t->unique(['loan_id', 'type'], 'uq_loan_type');
        });

        Schema::create('inspection_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inspection_id')->constrained('loan_inspections', 'id', 'fk_media_insp')->cascadeOnDelete();
            $t->enum('media_type', ['photo', 'video']);
            $t->string('path', 255);
            $t->string('caption', 150)->nullable();
            $t->timestamps();
        });

        Schema::create('damage_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loan_id')->constrained('loans', 'id', 'fk_dr_loan');
            $t->foreignId('item_unit_id')->constrained('item_units', 'id', 'fk_dr_unit');
            $t->enum('type', ['rusak', 'hilang']);
            $t->text('description');
            $t->enum('status', ['open', 'in_repair', 'resolved', 'written_off'])->default('open');
            $t->foreignId('reported_by')->constrained('users', 'id', 'fk_dr_user');
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution_note')->nullable();
            $t->timestamps();
            $t->index('status', 'idx_dr_status');
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('loan_id')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('action', 60);
            $t->string('description', 255)->nullable();
            $t->json('meta')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->foreign('loan_id', 'fk_log_loan')->references('id')->on('loans')->nullOnDelete();
            $t->foreign('actor_id', 'fk_log_actor')->references('id')->on('users')->nullOnDelete();
            $t->index('loan_id', 'idx_log_loan');
            $t->index('actor_id', 'idx_log_actor');
            $t->index('created_at', 'idx_log_created');
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->char('id', 36)->primary();
            $t->string('type', 255);
            $t->string('notifiable_type', 255);
            $t->unsignedBigInteger('notifiable_id');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['notifiable_type', 'notifiable_id'], 'idx_notif_notifiable');
            $t->index('read_at', 'idx_notif_read');
        });

        Schema::create('holidays', function (Blueprint $t) {
            $t->id();
            $t->date('date')->unique();
            $t->string('name', 120);
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->string('value', 255);
            $t->string('description', 255)->nullable();
            $t->timestamp('updated_at')->nullable();
        });

        Schema::create('jobs', function (Blueprint $t) {
            $t->id();
            $t->string('queue', 255)->index('idx_jobs_queue');
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        foreach (['jobs', 'settings', 'holidays', 'notifications', 'activity_logs', 'damage_reports', 'inspection_media', 'loan_inspections'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
