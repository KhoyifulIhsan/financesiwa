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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('ownership_status')->default('Milik PT')->after('status');
            $table->foreignId('investor_id')->nullable()->after('ownership_status')->constrained('investors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropColumn(['ownership_status', 'investor_id']);
        });
    }
};
