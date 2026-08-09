<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate_per_sms', 12, 6);
            $table->date('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['effective_from', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_rates');
    }
};
