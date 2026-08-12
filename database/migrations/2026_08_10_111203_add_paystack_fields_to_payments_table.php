<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('provider');
            $table->text('authorization_url')->nullable()->after('provider_reference');

            $table->unique('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_reference']);
            $table->dropColumn(['provider_reference', 'authorization_url']);
        });
    }
};
