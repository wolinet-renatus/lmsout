<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add per-course price_currency column and backfill USD/TZS pricing provenance.
 *
 * @author Renatus Bernardo
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'price_currency')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('price_currency', 10)->default('TZS')->after('sale_price');
            });

            // Existing seeded international courses with regular_price < 1000 were entered in USD.
            DB::table('courses')
                ->where('pricing_model', 'paid')
                ->whereNotNull('regular_price')
                ->where('regular_price', '>', 0)
                ->where('regular_price', '<', 1000)
                ->update(['price_currency' => 'USD']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'price_currency')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('price_currency');
            });
        }
    }
};
