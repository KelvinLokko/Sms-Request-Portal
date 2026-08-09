<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_requests', function (Blueprint $table) {
            $table->decimal('provider_rate_per_sms', 12, 6)->nullable()->after('quoted_cost_pesewas');
            $table->unsignedBigInteger('provider_cost_pesewas')->nullable()->after('provider_rate_per_sms');
        });
    }

    public function down(): void
    {
        Schema::table('sms_requests', function (Blueprint $table) {
            $table->dropColumn(['provider_rate_per_sms', 'provider_cost_pesewas']);
        });
    }
};
