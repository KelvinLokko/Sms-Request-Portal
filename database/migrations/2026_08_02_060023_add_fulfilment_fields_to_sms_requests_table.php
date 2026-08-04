<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_requests', function (Blueprint $table) {
            $table->timestamp('fulfilled_at')->nullable()->after('cancelled_at');
            $table->foreignId('fulfilled_by')->nullable()->after('fulfilled_at')
                ->constrained('users')->nullOnDelete();
            $table->string('deywuro_job_reference')->nullable()->after('fulfilled_by');

            $table->index('fulfilled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sms_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fulfilled_by');
            $table->dropColumn(['fulfilled_at', 'deywuro_job_reference']);
        });
    }
};
