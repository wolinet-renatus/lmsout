<?php

/**
 * Database migration: align applicants with scms form.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the applicants table with the SCMS Applicants Form (Sections 1, 1.1, 2, 3) as-is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'full_name')) {
                $table->string('full_name')->nullable()->after('country_id');
            }
            if (! Schema::hasColumn('applicants', 'home_address')) {
                $table->string('home_address')->nullable()->after('full_name');
            }
            if (! Schema::hasColumn('applicants', 'office_of_work')) {
                $table->string('office_of_work')->nullable()->after('home_address');
            }
            if (! Schema::hasColumn('applicants', 'occupation')) {
                $table->string('occupation')->nullable()->after('office_of_work');
            }
            if (! Schema::hasColumn('applicants', 'name_of_sponsor')) {
                $table->string('name_of_sponsor')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('applicants', 'sponsor_phone')) {
                $table->string('sponsor_phone', 50)->nullable()->after('name_of_sponsor');
            }
            if (! Schema::hasColumn('applicants', 'source')) {
                $table->string('source', 20)->default('admin')->after('notes'); // admin, public
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE applicants MODIFY first_name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE applicants MODIFY last_name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE applicants MODIFY email VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach (['full_name', 'home_address', 'office_of_work', 'occupation', 'name_of_sponsor', 'sponsor_phone', 'source'] as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
