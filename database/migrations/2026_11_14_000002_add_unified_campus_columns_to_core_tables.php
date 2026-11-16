<?php

/**
 * Database migration: add unified campus columns to core tables.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'registration_number')) {
                $table->string('registration_number', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id', 100)->nullable()->after('registration_number');
            }
            if (! Schema::hasColumn('users', 'force_password_reset')) {
                $table->boolean('force_password_reset')->default(false)->after('password');
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('force_password_reset');
            }
            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_approved');
            }
            if (! Schema::hasColumn('users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            }
            if (! Schema::hasColumn('users', 'blocked_reason')) {
                $table->string('blocked_reason')->nullable()->after('blocked_at');
            }
            if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedInteger('failed_login_attempts')->default(0)->after('blocked_reason');
            }
            if (! Schema::hasColumn('users', 'lockout_count')) {
                $table->unsignedInteger('lockout_count')->default(0)->after('failed_login_attempts');
            }
            if (! Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('lockout_count');
            }
            if (! Schema::hasColumn('users', 'active_session_id')) {
                $table->string('active_session_id', 255)->nullable()->after('locked_until');
            }
            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('active_session_id');
            }
            if (! Schema::hasColumn('users', 'legacy_scms_id')) {
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique()->after('remember_token');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'delivery_mode')) {
                $table->string('delivery_mode', 30)->default('online')->after('course_type'); // online, face_to_face, hybrid
            }
            if (! Schema::hasColumn('courses', 'default_branch_id')) {
                $table->foreignId('default_branch_id')->nullable()->after('delivery_mode')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('courses', 'default_venue_id')) {
                $table->foreignId('default_venue_id')->nullable()->after('default_branch_id')->constrained('venues')->nullOnDelete();
            }
            if (! Schema::hasColumn('courses', 'duration_label')) {
                $table->string('duration_label', 100)->nullable()->after('default_venue_id');
            }
            if (! Schema::hasColumn('courses', 'has_discount')) {
                $table->boolean('has_discount')->default(false)->after('sale_price');
            }
            if (! Schema::hasColumn('courses', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('has_discount');
            }
            if (! Schema::hasColumn('courses', 'attendance_threshold')) {
                $table->unsignedTinyInteger('attendance_threshold')->default(75)->after('is_live_course');
            }
            if (! Schema::hasColumn('courses', 'allow_online_f2f_payment')) {
                $table->boolean('allow_online_f2f_payment')->default(true)->after('attendance_threshold');
            }
            if (! Schema::hasColumn('courses', 'f2f_commission_enabled')) {
                $table->boolean('f2f_commission_enabled')->default(false)->after('allow_online_f2f_payment');
            }
            if (! Schema::hasColumn('courses', 'legacy_scms_id')) {
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique()->after('f2f_commission_enabled');
            }
        });

        // Allow multiple historical/cohort enrollments per student+course for Face-to-Face/Hybrid short courses
        $indexes = collect(DB::select('SHOW INDEXES FROM enrollments'))->pluck('Key_name')->all();
        Schema::table('enrollments', function (Blueprint $table) use ($indexes) {
            if (! in_array('enrollments_user_id_index', $indexes, true)) {
                $table->index('user_id', 'enrollments_user_id_index');
            }
            if (in_array('enrollments_user_id_course_id_unique', $indexes, true)) {
                $table->dropUnique('enrollments_user_id_course_id_unique');
            }

            if (! Schema::hasColumn('enrollments', 'applicant_id')) {
                $table->foreignId('applicant_id')->nullable()->after('course_id')->constrained('applicants')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('applicant_id')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'venue_id')) {
                $table->foreignId('venue_id')->nullable()->after('branch_id')->constrained('venues')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'course_assignment_id')) {
                $table->foreignId('course_assignment_id')->nullable()->after('venue_id')->constrained('course_assignments')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'delivery_mode')) {
                $table->string('delivery_mode', 30)->default('online')->after('course_assignment_id');
            }
            if (! Schema::hasColumn('enrollments', 'session')) {
                $table->string('session', 50)->nullable()->after('delivery_mode');
            }
            if (! Schema::hasColumn('enrollments', 'start_date')) {
                $table->date('start_date')->nullable()->after('session');
            }
            if (! Schema::hasColumn('enrollments', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('enrollments', 'duration_weeks')) {
                $table->unsignedSmallInteger('duration_weeks')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('enrollments', 'agreed_fee')) {
                $table->decimal('agreed_fee', 12, 2)->nullable()->after('duration_weeks');
            }
            if (! Schema::hasColumn('enrollments', 'attendance_percentage')) {
                $table->decimal('attendance_percentage', 5, 2)->default(0)->after('agreed_fee');
            }
            if (! Schema::hasColumn('enrollments', 'certificate_status')) {
                $table->string('certificate_status', 30)->default('pending')->after('attendance_percentage'); // pending, ready, issued
            }
            if (! Schema::hasColumn('enrollments', 'certificate_serial')) {
                $table->string('certificate_serial', 100)->nullable()->after('certificate_status');
            }
            if (! Schema::hasColumn('enrollments', 'certificate_ready_at')) {
                $table->timestamp('certificate_ready_at')->nullable()->after('certificate_serial');
            }
            if (! Schema::hasColumn('enrollments', 'certificate_issued_at')) {
                $table->timestamp('certificate_issued_at')->nullable()->after('certificate_ready_at');
            }
            if (! Schema::hasColumn('enrollments', 'certificate_issued_by')) {
                $table->foreignId('certificate_issued_by')->nullable()->after('certificate_issued_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'legacy_scms_id')) {
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique()->after('certificate_issued_by');
            }
        });

        // Widen payments.payment_method from ENUM to VARCHAR(50) so campus methods (control_number, mobile_money, bank_transfer, cash) work cleanly
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(50) NOT NULL");
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('enrollment_id')->constrained('invoices')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'applicant_id')) {
                $table->foreignId('applicant_id')->nullable()->after('invoice_id')->constrained('applicants')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'installment_type')) {
                $table->string('installment_type', 30)->default('full')->after('payment_method'); // full, half, quarter, balance
            }
            if (! Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number', 100)->nullable()->after('receipt_file');
            }
            if (! Schema::hasColumn('payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('receipt_number');
            }
            if (! Schema::hasColumn('payments', 'verification_status')) {
                $table->string('verification_status', 30)->default('verified')->after('status'); // pending, verified, rejected
            }
            if (! Schema::hasColumn('payments', 'legacy_scms_id')) {
                $table->unsignedBigInteger('legacy_scms_id')->nullable()->unique()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['invoice_id', 'applicant_id', 'installment_type', 'receipt_number', 'payment_date', 'verification_status', 'legacy_scms_id'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    if (in_array($col, ['invoice_id', 'applicant_id'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            foreach ([
                'applicant_id', 'branch_id', 'venue_id', 'course_assignment_id', 'certificate_issued_by',
                'delivery_mode', 'session', 'start_date', 'end_date', 'duration_weeks', 'agreed_fee',
                'attendance_percentage', 'certificate_status', 'certificate_serial', 'certificate_ready_at',
                'certificate_issued_at', 'legacy_scms_id',
            ] as $col) {
                if (Schema::hasColumn('enrollments', $col)) {
                    if (in_array($col, ['applicant_id', 'branch_id', 'venue_id', 'course_assignment_id', 'certificate_issued_by'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            foreach ([
                'default_branch_id', 'default_venue_id', 'delivery_mode', 'duration_label',
                'has_discount', 'discount_amount', 'attendance_threshold', 'allow_online_f2f_payment',
                'f2f_commission_enabled', 'legacy_scms_id',
            ] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    if (in_array($col, ['default_branch_id', 'default_venue_id'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'branch_id', 'address', 'registration_number', 'national_id', 'force_password_reset',
                'password_changed_at', 'is_blocked', 'blocked_at', 'blocked_reason',
                'failed_login_attempts', 'lockout_count', 'locked_until', 'active_session_id',
                'last_activity_at', 'legacy_scms_id',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'branch_id') {
                        $table->dropConstrainedForeignId('branch_id');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
