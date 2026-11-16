<?php

/**
 * Database migration: add iso columns to countries table.
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
        Schema::table('countries', function (Blueprint $table) {
            if (! Schema::hasColumn('countries', 'iso')) {
                $table->string('iso', 10)->nullable()->index()->after('code');
            }
            if (! Schema::hasColumn('countries', 'nicename')) {
                $table->string('nicename')->nullable()->after('name');
            }
            if (! Schema::hasColumn('countries', 'iso3')) {
                $table->string('iso3', 10)->nullable()->after('nicename');
            }
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            foreach (['iso', 'nicename', 'iso3'] as $col) {
                if (Schema::hasColumn('countries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
