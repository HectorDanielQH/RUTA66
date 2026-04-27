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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')->nullable()->after('customer_id')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('estimated_delivery_time_minutes')->nullable()->after('delivery_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn('estimated_delivery_time_minutes');
        });
    }
};
