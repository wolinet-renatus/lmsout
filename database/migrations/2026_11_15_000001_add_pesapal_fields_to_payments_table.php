<?php

/**
 * Database migration: add Pesapal API 3.0 fields to payments and seed Tanzania Merchant gateway settings.
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
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'pesapal_order_tracking_id')) {
                $table->string('pesapal_order_tracking_id')->nullable()->after('xpay_transaction_id');
            }
            if (! Schema::hasColumn('payments', 'pesapal_merchant_reference')) {
                $table->string('pesapal_merchant_reference')->nullable()->after('pesapal_order_tracking_id');
            }
            if (! Schema::hasColumn('payments', 'pesapal_confirmation_code')) {
                $table->string('pesapal_confirmation_code')->nullable()->after('pesapal_merchant_reference');
            }
            if (! Schema::hasColumn('payments', 'pesapal_payment_account')) {
                $table->string('pesapal_payment_account')->nullable()->after('pesapal_confirmation_code');
            }
        });

        // Upsert Pesapal Tanzania Merchant configuration so CLI migrations seed it automatically.
        $credentials = json_encode([
            'consumer_key'    => env('PESAPAL_CONSUMER_KEY', 'ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8'),
            'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', 'q27RChYs5UkypdcNYKzuUw460Dg='),
            'ipn_id'          => env('PESAPAL_IPN_ID', ''),
            'mode'            => env('PESAPAL_MODE', 'sandbox'),
        ]);

        $exists = DB::table('payment_gateway_settings')
            ->where('identifier', 'pesapal')
            ->exists();

        if ($exists) {
            DB::table('payment_gateway_settings')
                ->where('identifier', 'pesapal')
                ->update([
                    'name'        => 'Pesapal',
                    'type'        => 'online',
                    'credentials' => $credentials,
                    'is_enabled'  => true,
                    'updated_at'  => now(),
                ]);
        } else {
            DB::table('payment_gateway_settings')->insert([
                'identifier'           => 'pesapal',
                'name'                 => 'Pesapal',
                'type'                 => 'online',
                'credentials'          => $credentials,
                'is_enabled'           => true,
                'offline_instructions' => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_gateway_settings')->where('identifier', 'pesapal')->delete();

        $columns = array_values(array_filter([
            Schema::hasColumn('payments', 'pesapal_order_tracking_id') ? 'pesapal_order_tracking_id' : null,
            Schema::hasColumn('payments', 'pesapal_merchant_reference') ? 'pesapal_merchant_reference' : null,
            Schema::hasColumn('payments', 'pesapal_confirmation_code') ? 'pesapal_confirmation_code' : null,
            Schema::hasColumn('payments', 'pesapal_payment_account') ? 'pesapal_payment_account' : null,
        ]));

        if (! empty($columns)) {
            Schema::table('payments', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
