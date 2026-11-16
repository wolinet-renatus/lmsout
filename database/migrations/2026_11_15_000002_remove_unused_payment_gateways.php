<?php

/**
 * Database migration: drop legacy payment gateway columns (Razorpay, Paystack, SSLCommerz, Mollie, bKash)
 * and remove their rows from payment_gateway_settings.
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
     * Legacy gateway identifiers removed from the platform.
     *
     * @var array<int, string>
     */
    private array $legacyGateways = [
        'bkash',
        'mollie',
        'paystack',
        'razorpay',
        'sslcommerz',
    ];

    /**
     * Gateway-specific columns removed from the payments table.
     *
     * @var array<int, string>
     */
    private array $legacyPaymentColumns = [
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'paystack_reference',
        'paystack_access_code',
        'sslcommerz_tran_id',
        'sslcommerz_session_key',
        'sslcommerz_val_id',
        'mollie_payment_id',
        'bkash_payment_id',
        'bkash_trx_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payment_gateway_settings')
            ->whereIn('identifier', $this->legacyGateways)
            ->delete();

        $existingColumns = array_values(array_filter(
            $this->legacyPaymentColumns,
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
            foreach ($this->legacyPaymentColumns as $column) {
                if (! Schema::hasColumn('payments', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });
    }
};
