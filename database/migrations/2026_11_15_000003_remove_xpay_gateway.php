<?php

/**
 * Database migration: remove XPay gateway setting row and drop XPay columns
 * from the payments table.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * XPay columns removed from the payments table.
     *
     * @var array<int, string>
     */
    private array $xpayColumns = [
        'xpay_order_id',
        'xpay_transaction_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payment_gateway_settings')
            ->where('identifier', 'xpay')
            ->delete();

        $existingColumns = array_values(array_filter(
            $this->xpayColumns,
            fn (string $column): bool => Schema::hasColumn('payments', $column)
        ));

        if (! empty($existingColumns)) {
            Schema::table('payments', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach ($this->xpayColumns as $column) {
                if (! Schema::hasColumn('payments', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });
    }
};
