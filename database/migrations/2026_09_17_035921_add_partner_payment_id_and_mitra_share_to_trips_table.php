<?php

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
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('partner_payment_id')->nullable()->after('invoice_id')->constrained('partner_payments')->nullOnDelete();
            $table->decimal('mitra_share_amount', 15, 2)->default(0)->after('volume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['partner_payment_id']);
            $table->dropColumn(['partner_payment_id', 'mitra_share_amount']);
        });
    }
};
