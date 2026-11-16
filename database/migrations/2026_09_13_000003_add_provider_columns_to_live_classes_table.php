<?php

/**
 * Database migration: add provider columns to live classes table.
 *
 * @author Renatus Bernardo
 */

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
        if (Schema::hasTable('live_classes')) {
            Schema::table('live_classes', function (Blueprint $table) {
                if (! Schema::hasColumn('live_classes', 'provider')) {
                    $table->string('provider', 30)->default('custom')->after('description');
                }
                if (! Schema::hasColumn('live_classes', 'meeting_id')) {
                    $table->string('meeting_id', 100)->nullable()->after('provider');
                }
                if (! Schema::hasColumn('live_classes', 'meeting_password')) {
                    $table->string('meeting_password', 100)->nullable()->after('meeting_id');
                }
                if (! Schema::hasColumn('live_classes', 'host_url')) {
                    $table->string('host_url', 2048)->nullable()->after('join_url');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_classes')) {
            Schema::table('live_classes', function (Blueprint $table) {
                $cols = array_filter(
                    ['provider', 'meeting_id', 'meeting_password', 'host_url'],
                    fn ($col) => Schema::hasColumn('live_classes', $col)
                );
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

