<?php

/**
 * Database migration: add tax columns to payments table.
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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0.00)->after('subtotal');
            }
            if (!Schema::hasColumn('payments', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0.00)->after('tax_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount', 'tax_rate']);
        });
    }
};

