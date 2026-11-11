<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $t) {
            $t->id();
            $t->string('request_code', 30)->unique();
            $t->foreignId('user_id')->constrained('users', 'id', 'fk_loans_user');
            $t->foreignId('item_id')->constrained('items', 'id', 'fk_loans_item');
            $t->unsignedTinyInteger('quantity')->default(1);
            $t->date('start_date');
            $t->date('end_date');
            $t->text('purpose');
            $t->string('student_card_path', 255);
            $t->string('attachment_path', 255)->nullable();
            $t->boolean('agreement_accepted')->default(false);

            $t->enum('status', [
                'pending', 'reviewing', 'approved', 'ready_pickup', 'borrowed',
                'returned', 'returned_damaged', 'returned_lost',
                'rejected', 'cancelled', 'expired', 'overdue',
            ])->default('pending');

            $t->foreignId('reviewed_by')->nullable()->constrained('users', 'id', 'fk_loans_reviewer');
            $t->timestamp('reviewed_at')->nullable();
            $t->text('admin_note')->nullable();
            $t->text('reject_reason')->nullable();
            $t->date('pickup_date')->nullable();
            $t->time('pickup_time_start')->nullable();
            $t->time('pickup_time_end')->nullable();
            $t->string('pickup_location', 150)->nullable();

            $t->timestamp('approved_at')->nullable();
            $t->timestamp('borrowed_at')->nullable();
            $t->timestamp('returned_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->string('cancel_reason', 255)->nullable();
            $t->timestamp('expired_at')->nullable();

            $t->boolean('is_late')->default(false);
            $t->unsignedSmallInteger('late_days')->default(0);
            $t->timestamp('due_reminder_sent_at')->nullable();
            $t->timestamp('pending_reminder_sent_at')->nullable();

            $t->unsignedBigInteger('resubmitted_from_id')->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->foreign('resubmitted_from_id', 'fk_loans_resubmit')->references('id')->on('loans');
            $t->index('status', 'idx_loans_status');
            $t->index(['user_id', 'status'], 'idx_loans_user');
            $t->index(['item_id', 'start_date', 'end_date'], 'idx_loans_item_dates');
            $t->index(['start_date', 'end_date'], 'idx_loans_dates');
            $t->index('created_at', 'idx_loans_created');
        });

        // CHECK constraint sesuai SQL; SQLite (dipakai test) tidak mendukung ADD CONSTRAINT.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE loans ADD CONSTRAINT chk_loans_dates CHECK (end_date >= start_date)');
        }

        Schema::create('loan_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loan_id')->constrained('loans', 'id', 'fk_lu_loan')->cascadeOnDelete();
            $t->foreignId('item_unit_id')->constrained('item_units', 'id', 'fk_lu_unit');
            $t->enum('condition_out', ['baik', 'rusak'])->default('baik');
            $t->enum('condition_in', ['baik', 'rusak', 'hilang'])->nullable();
            $t->timestamp('returned_at')->nullable();
            $t->timestamps();
            $t->unique(['loan_id', 'item_unit_id'], 'uq_loan_unit');
            $t->index('item_unit_id', 'idx_lu_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_units');
        Schema::dropIfExists('loans');
    }
};
