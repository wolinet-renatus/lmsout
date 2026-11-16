<?php

/**
 * Database migration: create campus and billing tables.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->string('event')->nullable();
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                $table->index('log_name');
            });
        }

        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->index();
                $table->string('name');
                $table->unsignedInteger('phonecode')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('branch_code', 50)->nullable()->unique();
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('capacity')->default(30);
                $table->string('equipment')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_assignments')) {
            Schema::create('course_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
                $table->string('session', 50)->default('Morning'); // Morning, Afternoon, Evening, Weekend
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->json('schedule_days')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedInteger('max_capacity')->nullable();
                $table->string('status', 30)->default('active');
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
                $table->index(['branch_id', 'status']);
                $table->index(['instructor_id', 'status']);
            });
        }

        if (! Schema::hasTable('applicants')) {
            Schema::create('applicants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('email')->index();
                $table->string('phone', 50);
                $table->string('gender', 30)->nullable();
                $table->string('education_level', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('nida_number', 50)->nullable();
                $table->boolean('is_staff_student')->default(false);
                $table->string('staff_id_number', 100)->nullable();
                $table->string('parent_staff_id_path')->nullable();
                $table->boolean('has_discount')->default(false);
                $table->string('discount_reason', 100)->nullable();
                $table->string('discount_attachment_path')->nullable();
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
                $table->index(['branch_id', 'email']);
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number', 64)->unique();
                $table->string('control_number', 32)->nullable()->unique();
                $table->timestamp('control_number_generated_at')->nullable();
                $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->string('payment_status', 30)->default('unpaid'); // unpaid, partial, paid, cancelled
                $table->string('installment_plan', 30)->default('full'); // full, quarter, half
                $table->date('due_date')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
                $table->index(['branch_id', 'payment_status']);
                $table->index(['user_id', 'payment_status']);
            });
        }

        if (! Schema::hasTable('payment_schedules')) {
            Schema::create('payment_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
                $table->unsignedSmallInteger('installment_number')->default(1);
                $table->string('installment_label', 100)->nullable();
                $table->decimal('amount_due', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->date('due_date');
                $table->string('status', 30)->default('pending'); // pending, partial, paid, overdue
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('reminder_sent_at')->nullable();
                $table->unsignedInteger('reminder_count')->default(0);
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
                $table->index(['status', 'due_date']);
            });
        }

        if (! Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('course_assignment_id')->nullable()->constrained('course_assignments')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->date('attendance_date');
                $table->string('session_token', 100)->nullable();
                $table->string('status', 20)->default('present'); // present, late, absent, excused
                $table->string('check_in_method', 30)->default('manual'); // manual, qr_self
                $table->timestamp('checked_in_at')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique();
                $table->timestamps();
                $table->unique(['enrollment_id', 'attendance_date'], 'attendance_enrollment_date_unique');
                $table->index(['course_id', 'attendance_date']);
            });
        }

        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('name');
                $table->string('channel', 20)->default('email'); // email, sms, database
                $table->string('subject')->nullable();
                $table->text('body');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('course_assignments');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('countries');
    }
};
