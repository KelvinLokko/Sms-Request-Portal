<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('status', 32)->default('draft');
            $table->foreignId('sender_id_id')->nullable()->constrained('sender_ids')->nullOnDelete();
            $table->string('name')->nullable();
            $table->text('message_body')->nullable();
            $table->string('encoding', 16)->default('text');
            $table->string('flash_type', 16)->default('text');
            $table->boolean('is_personalised')->default(false);
            $table->unsignedTinyInteger('pages')->default(0);
            $table->boolean('exceeds_621_warning')->default(false);
            $table->boolean('requires_manual_cost_review')->default(false);
            $table->unsignedInteger('billable_recipients')->nullable();
            $table->decimal('rate_per_sms', 12, 6)->nullable();
            $table->unsignedBigInteger('estimated_cost_pesewas')->nullable();
            $table->unsignedBigInteger('quoted_cost_pesewas')->nullable();
            $table->timestamp('requested_send_at')->nullable();
            $table->timestamp('hard_deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('changes_requested_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('requested_send_at');
            $table->index('hard_deadline_at');
        });

        Schema::create('recipient_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_request_id')->constrained('sms_requests')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('original_path');
            $table->string('original_filename');
            $table->string('mime_type', 128)->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('headers')->nullable();
            $table->string('phone_column')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('billable_count')->default(0);
            $table->string('rejected_export_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['sms_request_id', 'status']);
        });

        Schema::create('sms_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sms_request_id')->constrained('sms_requests')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('raw_value', 64);
            $table->string('normalised_msisdn', 20)->nullable();
            $table->string('status', 16);
            $table->string('rejection_reason')->nullable();
            $table->json('personalisation_data')->nullable();
            $table->timestamps();

            $table->index(['recipient_list_id', 'status']);
            $table->index(['recipient_list_id', 'normalised_msisdn']);
            $table->index(['sms_request_id', 'status']);
        });

        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_request_id')->constrained('sms_requests')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('sms_recipients');
        Schema::dropIfExists('recipient_lists');
        Schema::dropIfExists('sms_requests');
    }
};
