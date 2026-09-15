<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'date')) {
                $table->date('date')->nullable()->change();
            }
            if (! Schema::hasColumn('bills', 'bill_date')) {
                $table->date('bill_date')->nullable()->after('vendor_id');
            }
            if (! Schema::hasColumn('bills', 'notes')) {
                $table->text('notes')->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('bills', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            }
        });

        if (Schema::hasColumn('bills', 'date') && Schema::hasColumn('bills', 'bill_date')) {
            DB::statement('UPDATE bills SET bill_date = date WHERE bill_date IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'bill_date')) {
                $table->dropColumn('bill_date');
            }
            if (Schema::hasColumn('bills', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('bills', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
